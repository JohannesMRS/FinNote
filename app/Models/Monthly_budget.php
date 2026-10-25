<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Monthly_budget extends Model
{
    protected $fillable = [
        'user_id',
        'month',
        'year',
        'amount'
    ];

    protected function casts(): array
    {
        return [
                'month'=>'integer',
                'year'=>'integer',
                'amount'=>'integer'
        ];
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function getRemainingBalanceAttribute():int
    {
        $totalIncome = $this->transactions()->where('type', 'income')->sum('amount');
        $totalExpense = $this->transactions()->where('type', 'expense')->sum('amount');

        return (int)$this->amount + $totalIncome-$totalExpense;


    }
}
