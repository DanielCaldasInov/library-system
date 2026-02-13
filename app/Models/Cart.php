<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $casts = [
        'last_activity_at' => 'datetime',
        'help_email_sent_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function touchActivity(): void
    {
        $this->update([
            'last_activity_at' => now(),
        ]);
    }
}

