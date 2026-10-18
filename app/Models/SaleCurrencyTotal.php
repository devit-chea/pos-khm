<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleCurrencyTotal extends Model
{
    protected $fillable = ['sale_id', 'currency_id', 'exchange_rate', 'grand_total'];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Write the sale's grand total in every currency shown in POS. A currency
     * already recorded for this sale keeps its original rate, so editing an old
     * sale doesn't pick up today's rate.
     */
    public static function syncForSale(Sale $sale): void
    {
        $sale_rate = (float) $sale->exchange_rate ?: 1;
        $existing = static::where('sale_id', $sale->id)->get()->keyBy('currency_id');

        $currencies = Currency::where('is_active', true)
            ->where('show_in_pos', true)
            ->where('id', '!=', $sale->currency_id)
            ->get();

        foreach ($currencies as $currency) {
            $rate = isset($existing[$currency->id])
                ? $existing[$currency->id]->exchange_rate
                : $currency->exchange_rate / $sale_rate;

            static::updateOrCreate(
                ['sale_id' => $sale->id, 'currency_id' => $currency->id],
                ['exchange_rate' => $rate, 'grand_total' => $currency->roundAmount($sale->grand_total * $rate)]
            );
        }
    }
}
