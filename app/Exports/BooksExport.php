<?php

namespace App\Exports;

use App\Models\Book;
use App\Models\Author;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BooksExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        protected array $filters = [],
        protected ?string $sort = null,
        protected ?string $direction = 'asc',
    ) {}

    public function query()
    {
        return Book::query()
            ->with(['authors', 'publisher'])
            ->when($this->filters['search'] ?? null, function (Builder $query) {
                $filter = $this->filters['filter'] ?? 'name';
                $search = $this->filters['search'];

                match ($filter) {
                    'author' => $query->whereHas('authors',
                        fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ),
                    'publisher' => $query->whereHas('publisher',
                        fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ),
                    default => $query->where('name', 'like', "%{$search}%"),
                };
            })
            ->when(in_array($this->sort, ['name', 'price']), fn ($q) =>
            $q->orderBy($this->sort, $this->direction)
            )
            ->when($this->sort === 'publisher', fn ($q) =>
            $q->join('publishers', 'publishers.id', '=', 'books.publisher_id')
                ->orderBy('publishers.name', $this->direction)
                ->select('books.*')
            )
            ->when($this->sort === 'author', fn ($q) =>
            $q->orderBy(
                Author::select('name')
                    ->join('author_book', 'authors.id', '=', 'author_book.author_id')
                    ->whereColumn('author_book.book_id', 'books.id')
                    ->orderBy('name')
                    ->limit(1),
                $this->direction
            )
            );
    }

    public function headings(): array
    {
        return [
            'Name',
            'Publisher',
            'Authors',
            'Price',
        ];
    }

    public function map($book): array
    {
        return [
            $book->name,
            $book->publisher?->name,
            $book->authors->pluck('name')->join(', '),
            number_format($book->price, 2),
        ];
    }
}
