<?php

use Illuminate\Support\Facades\Route;

Route::post('/woocommerce/webhooks/{publicId}', 'WooCommerceWebhookController@store')
    ->middleware('throttle:woocommerce-webhook')
    ->whereUuid('publicId')
    ->name('woocommerce.webhooks.receive');
