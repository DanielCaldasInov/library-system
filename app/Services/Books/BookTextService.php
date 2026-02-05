<?php

namespace App\Services\Books;

use App\Models\Book;

class BookTextService
{
    public function build(Book $book): string
    {
        $parts = [];

        $parts[] = (string) $book->name;
        $parts[] = (string) $book->bibliography;

        if ($book->relationLoaded('authors')) {
            $parts[] = $book->authors->pluck('name')->implode(' ');
        }

        if ($book->relationLoaded('publisher') && $book->publisher) {
            $parts[] = (string) $book->publisher->name;
        }

        return trim(implode(' ', array_filter($parts)));
    }
}
