<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $casts = [
        'amount' => 'float',
    ];

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2, '.', ',');
    }

    public function scopeDebits(Builder $query): Builder
    {
        return $query->where('type', 'debit');
    }

    public function scopeCredits(Builder $query): Builder
    {
        return $query->where('type', 'credit');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }
}
