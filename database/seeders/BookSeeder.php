<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\Publisher;
use App\Services\GoogleBooks\GoogleBooksClient;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        $client = GoogleBooksClient::make();

        if (! $client->ping()) {
            Book::factory(20)->create();
            $this->command?->warn('Google Books API unavailable. Seeded 20 books using factories.');
            return;
        }

        $target = 40;
        $created = 0;

        $q = 'subject:fiction';

        $startIndex = 0;
        $maxResults = 40;

        while ($created < $target) {
            $json = $client->search($q, $maxResults, $startIndex, 'PT');
            $items = $json['items'] ?? [];

            if (empty($items)) {
                break;
            }

            foreach ($items as $item) {
                if ($created >= $target) {
                    break;
                }

                $data = $client->normalizeVolume($item);

                $isbn = $data['ISBN'] ?? null;
                if (!is_string($isbn) || $isbn === '' || (strlen($isbn) !== 10 && strlen($isbn) !== 13)) {
                    continue;
                }

                if (Book::where('ISBN', $isbn)->exists()) {
                    continue;
                }

                $publisher = Publisher::firstOrCreate(
                    ['name' => $data['publisher_name'] ?: 'Unknown'], //TODO: Remove unknown, put a placeholder or generate a random name
                    ['logo' => 'http://picsum.photos/seed/' . random_int(0, 999) . '/100']
                );

                $cover = $data['cover'] ?: ('http://picsum.photos/seed/' . random_int(0, 999) . '/100');

                $bibliography = $data['bibliography'] ?: '—';

                $price = is_numeric($data['price'] ?? null) ? (float) $data['price'] : (float) random_int(15, 200);

                $book = Book::create([
                    'ISBN' => $isbn,
                    'name' => $data['name'] ?: 'Untitled',
                    'publisher_id' => $publisher->id,
                    'bibliography' => $bibliography,
                    'cover' => $cover,
                    'price' => round($price, 2),
                ]);

                $authorNames = $data['authors'] ?? [];
                if (empty($authorNames)) {
                    $authorNames = ['Unknown Author']; //TODO: Remove unknown, put a placeholder or generate a random name
                }

                foreach ($authorNames as $authorName) {
                    $authorName = trim((string) $authorName);
                    if ($authorName === '') continue;

                    $author = Author::firstOrCreate(
                        ['name' => $authorName],
                        ['photo' => 'http://picsum.photos/seed/' . random_int(0, 999) . '/100']
                    );

                    $book->authors()->syncWithoutDetaching([$author->id]);
                }

                $created++;
            }

            $startIndex += $maxResults;

            if ($startIndex > 200) {
                break;
            }
        }

        $this->command?->info("Seeded {$created} books from Google Books API.");
    }
}
