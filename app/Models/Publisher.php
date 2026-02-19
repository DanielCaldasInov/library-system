<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Publisher extends Model
{
    /** @use HasFactory<\Database\Factories\PublisherFactory> */
    use HasFactory;
    use LogsActivity;

    protected $casts = [
        //'name' => 'encrypted',
    ];

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }
}
