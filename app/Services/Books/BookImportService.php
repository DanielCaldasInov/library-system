<?php

namespace App\Services\Books;

use App\Models\Author;
use App\Models\Book;
use App\Models\Publisher;

class BookImportService
{
    public function importNormalized(array $data): array
    {
        $isbn = $data['ISBN'] ?? null;

        if (!is_string($isbn) || $isbn === '' || (strlen($isbn) !== 10 && strlen($isbn) !== 13)) {
            return ['status' => 'skipped', 'reason' => 'missing_or_invalid_isbn'];
        }

        $isbn = preg_replace('/\D+/', '', $isbn) ?? '';

        if (Book::where('ISBN', $isbn)->exists()) {
            return ['status' => 'skipped', 'reason' => 'isbn_already_exists'];
        }

        $publisherName = trim((string)($data['publisher_name'] ?? 'Unknown'));
        if ($publisherName === '') {
            $publisherName = 'Unknown';
        }

        $publisher = Publisher::firstOrCreate(
            ['name' => $publisherName],
            ['logo' => 'http://picsum.photos/seed/' . random_int(0, 999) . '/100']
        );

        $cover = $data['cover'] ?? null;
        if (!is_string($cover) || trim($cover) === '') {
            $cover = 'http://picsum.photos/seed/' . random_int(0, 999) . '/100';
        }

        $bibliography = $data['bibliography'] ?? '—';
        if (!is_string($bibliography) || trim($bibliography) === '') {
            $bibliography = '—';
        }

        $price = $data['price'] ?? null;
        if (!is_numeric($price)) {
            $price = (float) random_int(15, 200);
        }

        $name = trim((string)($data['name'] ?? 'Untitled'));
        if ($name === '') {
            $name = 'Untitled';
        }

        $book = Book::create([
            'ISBN' => $isbn,
            'name' => $name,
            'publisher_id' => $publisher->id,
            'bibliography' => $bibliography,
            'cover' => $cover,
            'price' => round((float)$price, 2),
        ]);

        $authorNames = $data['authors'] ?? [];
        if (!is_array($authorNames) || empty($authorNames)) {
            $authorNames = ['Unknown Author'];
        }

        foreach ($authorNames as $authorName) {
            $authorName = trim((string)$authorName);
            if ($authorName === '') continue;

            $author = Author::firstOrCreate(
                ['name' => $authorName],
                ['photo' => 'http://picsum.photos/seed/' . random_int(0, 999) . '/100']
            );

            $book->authors()->syncWithoutDetaching([$author->id]);
        }

        return ['status' => 'imported', 'book_id' => $book->id];
    }
}
