<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Book extends Model
{
    /** @use HasFactory<\Database\Factories\BookFactory> */
    use HasFactory;

    protected $casts = [
        //'name' => 'encrypted',
        //'cover' => 'encrypted',
        //'ISBN' => 'encrypted',
        //'Price' => 'encrypted',
        //'Bibliography' => 'encrypted',
    ];

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class);
    }

    public function authors(): belongsToMany
    {
        return $this->belongsToMany(Author::class)
            ->withTimestamps();
        //If the relation generates a problem
        //Assign a foreign pivot key and add a primary key to the pivot table if needed
    }
}
