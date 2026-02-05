<?php

namespace App\Services\Books;

use App\Models\Book;
use Illuminate\Support\Facades\Cache;

class RelatedBooksService
{
    public function __construct(
        private BookTextService $textService,
        private KeywordExtractor $extractor
    ) {}

    public function getRelated(Book $book, int $limit = 6): array
    {
        return Cache::remember(
            "related_books:$book->id",
            now()->addHours(12),
            fn () => $this->compute($book, $limit)
        );
    }

    private function compute(Book $book, int $limit): array
    {
        $book->loadMissing(['authors:id,name', 'publisher:id,name']);

        $baseText = $this->textService->build($book);
        $keywords = $this->extractor->extract($baseText, 25);

        if (count($keywords) === 0) {
            return [];
        }

        $candidates = Book::query()
            ->where('id', '!=', $book->id)
            ->select(['id', 'name', 'cover', 'publisher_id', 'bibliography'])
            ->with(['authors:id,name', 'publisher:id,name'])
            ->limit(200)
            ->get();

        $baseAuthorIds = $book->authors->pluck('id')->all();
        $basePublisherId = $book->publisher_id;

        $scored = [];

        foreach ($candidates as $cand) {
            $candText = $this->textService->build($cand);
            $candKeywords = $this->extractor->extract($candText, 25);

            $common = array_intersect($keywords, $candKeywords);
            if (count($common) === 0) {
                continue;
            }

            $score = count($common);

            $candAuthorIds = $cand->authors->pluck('id')->all();
            if (count(array_intersect($baseAuthorIds, $candAuthorIds)) > 0) {
                $score += 3;
            }

            if ($basePublisherId && $cand->publisher_id === $basePublisherId) {
                $score += 1;
            }

            $scored[] = [
                'score' => $score,
                'common' => array_values(array_slice($common, 0, 6)),
                'book' => [
                    'id' => $cand->id,
                    'name' => $cand->name,
                    'cover' => $cand->cover,
                    'publisher' => $cand->publisher ? [
                        'id' => $cand->publisher->id,
                        'name' => $cand->publisher->name,
                    ] : null,
                    'authors' => $cand->authors->map(fn ($a) => [
                        'id' => $a->id,
                        'name' => $a->name,
                    ])->values(),
                ],
            ];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $limit);
    }
}
