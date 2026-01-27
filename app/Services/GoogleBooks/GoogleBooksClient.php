<?php

namespace App\Services\GoogleBooks;

use Illuminate\Support\Facades\Http;

class GoogleBooksClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly ?string $apiKey,
    ) {}

    public static function make(): self
    {
        return new self(
            config('services.google_books.base_url', 'https://www.googleapis.com/books/v1'),
            config('services.google_books.key'),
        );
    }

    public function ping(): bool
    {
        try {
            $response = Http::baseUrl($this->baseUrl)
                ->timeout(10)
                ->retry(1, 250)
                ->get('/volumes', [
                    'q' => 'test',
                    'maxResults' => 1,
                    'key' => $this->apiKey,
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function search(
        string $q,
        int $maxResults = 10,
        int $startIndex = 0,
        ?string $country = null
    ): array {
        $response = Http::baseUrl($this->baseUrl)
            ->timeout(20)
            ->retry(2, 300)
            ->get('/volumes', array_filter([
                'q' => $q,
                'printType' => 'books',
                'maxResults' => min(max($maxResults, 1), 40),
                'startIndex' => max($startIndex, 0),
                'country' => $country,
                'key' => $this->apiKey,
            ], fn ($v) => $v !== null && $v !== ''));

        $response->throw();

        return $response->json();
    }

    public function normalizeVolume(array $item): array
    {
        $info = $item['volumeInfo'] ?? [];
        $sale = $item['saleInfo'] ?? [];

        $title = $this->cleanText($info['title'] ?? null);
        $publisher = $this->cleanText($info['publisher'] ?? null);

        $authors = array_values(array_filter(array_map(
            fn ($a) => $this->cleanText($a),
            $info['authors'] ?? []
        )));

        $isbn = $this->extractIsbn($info);

        $imageLinks = $info['imageLinks'] ?? [];
        $cover = $imageLinks['thumbnail'] ?? ($imageLinks['smallThumbnail'] ?? null);

        $bibliography = $this->cleanText($info['description'] ?? null) ?: '—';

        $price = $this->extractPrice($sale);
        if ($price === null) {
            $price = (float) random_int(15, 200);
        }

        return [
            'google_volume_id' => $item['id'] ?? null,

            'name' => $title ?: 'Untitled',
            'ISBN' => $isbn,
            'bibliography' => $bibliography,
            'cover' => $cover,
            'price' => round((float) $price, 2),

            'publisher_name' => $publisher ?: 'Unknown',
            'authors' => $authors,
        ];
    }

    private function extractIsbn(array $volumeInfo): ?string
    {
        $industry = $volumeInfo['industryIdentifiers'] ?? [];
        if (!is_array($industry)) {
            return null;
        }

        $isbn13 = null;
        $isbn10 = null;

        foreach ($industry as $id) {
            $type = $id['type'] ?? null;
            $identifier = $id['identifier'] ?? null;

            if (!is_string($identifier)) {
                continue;
            }

            $digits = $this->digitsOnly($identifier);

            if ($type === 'ISBN_13' && strlen($digits) === 13) {
                $isbn13 = $digits;
            }

            if ($type === 'ISBN_10' && strlen($digits) === 10) {
                $isbn10 = $digits;
            }
        }

        return $isbn13 ?? $isbn10;
    }

    private function extractPrice(array $saleInfo): ?float
    {
        $amount = $saleInfo['retailPrice']['amount'] ?? null;
        if (!is_numeric($amount)) {
            $amount = $saleInfo['listPrice']['amount'] ?? null;
        }
        return is_numeric($amount) ? (float) $amount : null;
    }

    private function cleanText(?string $text): ?string
    {
        if ($text === null) return null;

        $text = trim($text);
        if ($text === '') return null;

        $text = preg_replace("/\s+/", " ", $text);

        return $text ?: null;
    }

    private function digitsOnly(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }
}
