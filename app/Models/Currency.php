<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = ["name", "code", "symbol", "exchange_rate", "show_in_pos", "rounding", "is_active"];

    protected $casts = [
        'show_in_pos' => 'boolean',
        'rounding' => 'float',
    ];

    /**
     * Round an amount in this currency: to a multiple of `rounding` (e.g. 100 riel)
     * when set, otherwise to the app's decimal places.
     */
    public function roundAmount($amount)
    {
        if ($this->rounding > 0) {
            return round($amount / $this->rounding) * $this->rounding;
        }
        return round($amount, (int) (config('decimal') ?? 2));
    }
}
