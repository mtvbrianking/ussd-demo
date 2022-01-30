<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'balance' => 'float',
        'is_loan' => 'boolean',
    ];

    /**
     * Get formatted account balance.
     *
     * @return string
     */
    public function getFormattedBalanceAttribute()
    {
        return number_format($this->balance, 2, '.', ',');
    }

    public function scopeLoans(Builder $query): Builder
    {
        return $query->where('is_loan', true);
    }

    public function scopeSavings(Builder $query): Builder
    {
        return $query->where('is_loan', false);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function transactions(): hasMany
    {
        return $this->hasMany(Transaction::class, 'account_id', 'id');
    }

    public function recentTransactions(): hasMany
    {
        return $this->hasMany(Transaction::class, 'account_id', 'id')->orderBy('created_at', 'desc');
    }

    public function debitTransactions(): hasMany
    {
        return $this->hasMany(Transaction::class, 'account_id', 'id')->debits();
    }

    public function creditTransactions(): hasMany
    {
        return $this->hasMany(Transaction::class, 'account_id', 'id')->credits();
    }
}
