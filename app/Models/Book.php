<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Request as BookRequest;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    /** @use HasFactory<\Database\Factories\BookFactory> */
    use HasFactory;
    use LogsActivity;

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
    }

    public function requests()
    {
        return $this->hasMany(BookRequest::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
