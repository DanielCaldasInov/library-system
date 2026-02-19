<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Author extends Model
{
    /** @use HasFactory<\Database\Factories\AuthorFactory> */
    use HasFactory;
    use LogsActivity;

    protected $casts = [
        //'name' => 'encrypted',
    ];

    public function books():belongsToMany
    {
        return $this->belongsToMany(Book::class)
            ->withTimestamps();
    }
}
