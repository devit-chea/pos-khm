<?php

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

if(config('database.connections.saleprosaas_landlord')) {
    Route::group(['prefix' => 'woocommerce'], function () {
        Route::middleware(['common', 'auth', 'active', 'module.access:woocommerce', InitializeTenancyByDomain::class,PreventAccessFromCentralDomains::class])->group(function () {
            Route::get('/index', 'WoocommerceController@index')->middleware('woocommerce.permission:woocommerce.sync')->name('woocommerce.index');
            Route::get('/sync-log', 'WoocommerceController@syncLog')->middleware('woocommerce.permission:woocommerce.sync')->name('woocommerce.sync-log');
            Route::get('/settings', 'WoocommerceController@settings')->middleware('woocommerce.permission:woocommerce.manage')->name('woocommerce.settings');
            Route::post('/store', 'WoocommerceController@store')->middleware('woocommerce.permission:woocommerce.manage')->name('woocommerce.store');
            Route::post('/webhook-secret', 'WoocommerceController@webhookSecret')->middleware('woocommerce.permission:woocommerce.manage')->name('woocommerce.webhook-secret');
            Route::post('/sync-categories', 'WoocommerceController@syncCategories')->middleware('woocommerce.permission:woocommerce.sync')->name('woocommerce.syncCategories');
            Route::post('/reset-sync-category', 'WoocommerceController@resetSyncedCategory')->middleware('woocommerce.permission:woocommerce.manage')->name('woocommerce.resetSyncedCategory');
            Route::post('/sync-product', 'WoocommerceController@syncProducts')->middleware('woocommerce.permission:woocommerce.sync')->name('woocommerce.syncProducts');
            Route::post('/reset-sync-product', 'WoocommerceController@resetSyncedProduct')->middleware('woocommerce.permission:woocommerce.manage')->name('woocommerce.resetSyncedProduct');
            Route::post('/map-tax-rates', 'WoocommerceController@mapTaxRates')->middleware('woocommerce.permission:woocommerce.manage')->name('woocommerce.mapTaxRates');
            Route::post('/sync-orders', 'WoocommerceController@syncOrders')->middleware('woocommerce.permission:woocommerce.sync')->name('woocommerce.syncOrders');
        });
    });
}else{
    Route::group(['prefix' => 'woocommerce'], function () {
        Route::group(['middleware' => ['common', 'auth', 'active', 'module.access:woocommerce']], function() {
            Route::get('/index', 'WoocommerceController@index')->middleware('woocommerce.permission:woocommerce.sync')->name('woocommerce.index');
            Route::get('/sync-log', 'WoocommerceController@syncLog')->middleware('woocommerce.permission:woocommerce.sync')->name('woocommerce.sync-log');
            Route::get('/settings', 'WoocommerceController@settings')->middleware('woocommerce.permission:woocommerce.manage')->name('woocommerce.settings');
            Route::post('/store', 'WoocommerceController@store')->middleware('woocommerce.permission:woocommerce.manage')->name('woocommerce.store');
            Route::post('/webhook-secret', 'WoocommerceController@webhookSecret')->middleware('woocommerce.permission:woocommerce.manage')->name('woocommerce.webhook-secret');
            Route::post('/sync-categories', 'WoocommerceController@syncCategories')->middleware('woocommerce.permission:woocommerce.sync')->name('woocommerce.syncCategories');
            Route::post('/reset-sync-category', 'WoocommerceController@resetSyncedCategory')->middleware('woocommerce.permission:woocommerce.manage')->name('woocommerce.resetSyncedCategory');
            Route::post('/sync-product', 'WoocommerceController@syncProducts')->middleware('woocommerce.permission:woocommerce.sync')->name('woocommerce.syncProducts');
            Route::post('/reset-sync-product', 'WoocommerceController@resetSyncedProduct')->middleware('woocommerce.permission:woocommerce.manage')->name('woocommerce.resetSyncedProduct');

            Route::post('/map-tax-rates', 'WoocommerceController@mapTaxRates')->middleware('woocommerce.permission:woocommerce.manage')->name('woocommerce.mapTaxRates');

            Route::post('/sync-orders', 'WoocommerceController@syncOrders')->middleware('woocommerce.permission:woocommerce.sync')->name('woocommerce.syncOrders');
        });
    });
}
