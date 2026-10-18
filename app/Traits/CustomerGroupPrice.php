<?php
namespace App\Traits;

use DB;

trait CustomerGroupPrice {

    /**
     * Fixed price of the product for the customer's (active) group, plus the variant's
     * additional price, or null when the group has no price for this product.
     */
    public function groupPriceFor($customerId, $productId, $productVariantId = null)
    {
        if(!$customerId || !$productId)
            return null;

        $price = DB::table('customers')
            ->join('customer_groups', 'customer_groups.id', '=', 'customers.customer_group_id')
            ->join('customer_group_product_prices', 'customer_group_product_prices.customer_group_id', '=', 'customer_groups.id')
            ->where('customers.id', $customerId)
            ->where('customer_groups.is_active', true)
            ->where('customer_group_product_prices.product_id', $productId)
            ->value('customer_group_product_prices.price');

        if(is_null($price))
            return null;

        if($productVariantId)
            $price += (float) DB::table('product_variants')->where('id', $productVariantId)->value('additional_price');

        return (float) $price;
    }
}
