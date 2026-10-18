<?php

use App\Http\Controllers\ProductController;
use Modules\Manufacturing\Http\Controllers\ProductionController;
use Modules\Manufacturing\Http\Controllers\RecipeController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

if(config('database.connections.saleprosaas_landlord')) {
    Route::middleware([InitializeTenancyByDomain::class,PreventAccessFromCentralDomains::class, 'common', 'auth', 'active'])->group(function () {
        Route::prefix('manufacturing')
        ->name('manufacturing.')
        ->group(function () {
            // Production (extra endpoints)
            Route::controller(ProductionController::class)->prefix('productions')->group(function () {
                Route::post('production-data', 'productionData')->name('productions.data');
                Route::get('product_production/{id}', 'productProductionData')->name('productions.product-production');
            });

            // Resource routes
            Route::resource('productions', ProductionController::class)->except(['show']);
            Route::resource('recipes', RecipeController::class)->except(['show']);

            // Product related
            Route::post('products/product-data', [ProductController::class, 'productData'])
                ->name('products.data');

            Route::post('product-data', [RecipeController::class, 'productData'])
                ->name('recipes.product-data');

            Route::get('recipes/lims_product_search', [ProductController::class, 'limsProductSearch'])
                ->name('products.search');

            Route::post('get-ingredients', [ProductionController::class, 'getIngredients'])
                ->name('productions.get-ingredients');

            Route::post('products/getdata/{id}/{variant_id}', [ProductController::class, 'getData'])
                ->name('products.getdata');

            // Dashboard / index
            Route::get('/', [\Modules\Manufacturing\Http\Controllers\ManufacturingController::class, 'index'])
                ->name('dashboard');
        });
    });
}
else {
    Route::middleware(['common', 'auth', 'active'])->group(function () {
        //production routes
        Route::prefix('manufacturing')
        ->name('manufacturing.')
        ->group(function () {

            // Production (extra endpoints)
            Route::controller(ProductionController::class)->prefix('productions')->group(function () {
                Route::post('production-data', 'productionData')->name('productions.data');
                Route::get('product_production/{id}', 'productProductionData')->name('productions.product-production');
            });

            // Resource routes
            Route::resource('productions', ProductionController::class)->except(['show']);
            Route::resource('recipes', RecipeController::class)->except(['show']);

            // Product related
            Route::post('products/product-data', [ProductController::class, 'productData'])
                ->name('products.data');

            Route::post('product-data', [RecipeController::class, 'productData'])
                ->name('recipes.product-data');

            Route::get('recipes/lims_product_search', [ProductController::class, 'limsProductSearch'])
                ->name('products.search');

            Route::post('get-ingredients', [ProductionController::class, 'getIngredients'])
                ->name('productions.get-ingredients');

            Route::post('products/getdata/{id}/{variant_id}', [ProductController::class, 'getData'])
                ->name('products.getdata');

            // Dashboard / index
            Route::get('/', [\Modules\Manufacturing\Http\Controllers\ManufacturingController::class, 'index'])
                ->name('dashboard');
        });
    });
}