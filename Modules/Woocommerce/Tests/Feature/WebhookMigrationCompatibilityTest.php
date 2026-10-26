<?php

namespace Modules\Woocommerce\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WebhookMigrationCompatibilityTest extends TestCase
{
    public function test_receipt_timestamp_has_database_default_on_fresh_mysql_install(): void
    {
        $original = config('database.connections.mysql.database');
        $database = 'salepro_woo_migration_'.bin2hex(random_bytes(4));
        self::assertContains($original, ['salepro_testing', 'salepro_webhook_clean']);

        DB::statement("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        try {
            config(['database.connections.mysql.database' => $database]);
            DB::purge('mysql');
            Schema::create('woocommerce_settings', function ($table): void {
                $table->bigIncrements('id');
                $table->text('webhook_secret_order_created')->nullable();
            });

            $migration = require module_path('Woocommerce', 'Database/Migrations/2026_09_12_000003_create_webhook_trust_boundary.php');
            $migration->up();

            $column = DB::selectOne("SELECT COLUMN_DEFAULT AS column_default FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'woocommerce_webhook_receipts' AND COLUMN_NAME = 'received_at'", [$database]);
            self::assertNotNull($column);
            self::assertStringContainsString('current_timestamp', strtolower((string) $column->column_default));
        } finally {
            config(['database.connections.mysql.database' => $original]);
            DB::purge('mysql');
            DB::statement("DROP DATABASE `{$database}`");
        }
    }
}
