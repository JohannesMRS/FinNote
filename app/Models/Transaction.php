<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'description',
        'type',
        'amount',
        'transaction_date'
    ];

    protected function casts(): array
    {
        return [
            'amount'=>'int',
            'transaction_date'=>'date'
        ];
    }

    public function monthly_budget(): BelongsTo
    {
        return $this->belongsTo(Monthly_budget::class);
    }
}
