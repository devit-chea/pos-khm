<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['category_id', 'is_active', 'is_online'], 'products_category_storefront_idx');
            $table->index(['brand_id', 'is_active', 'is_online'], 'products_brand_storefront_idx');
            $table->index('slug', 'products_slug_idx');
        });
        Schema::table('categories', fn (Blueprint $table) => $table->index(['slug', 'is_active'], 'categories_slug_active_idx'));
        Schema::table('pages', fn (Blueprint $table) => $table->index(['slug', 'status'], 'pages_slug_status_idx'));
        Schema::table('collections', fn (Blueprint $table) => $table->index(['slug', 'status'], 'collections_slug_status_idx'));
        Schema::table('product_reviews', fn (Blueprint $table) => $table->index(['product_id', 'approved'], 'reviews_product_approved_idx'));
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_category_storefront_idx');
            $table->dropIndex('products_brand_storefront_idx');
            $table->dropIndex('products_slug_idx');
        });
        Schema::table('categories', fn (Blueprint $table) => $table->dropIndex('categories_slug_active_idx'));
        Schema::table('pages', fn (Blueprint $table) => $table->dropIndex('pages_slug_status_idx'));
        Schema::table('collections', fn (Blueprint $table) => $table->dropIndex('collections_slug_status_idx'));
        Schema::table('product_reviews', fn (Blueprint $table) => $table->dropIndex('reviews_product_approved_idx'));
    }
};
