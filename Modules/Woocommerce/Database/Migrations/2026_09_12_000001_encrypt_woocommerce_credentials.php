<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('woocommerce_settings')) {
            return;
        }

        $secretColumns = [
            'woocomerce_consumer_key',
            'woocomerce_consumer_secret',
            'webhook_secret_order_created',
            'webhook_secret_order_updated',
            'webhook_secret_order_deleted',
            'webhook_secret_order_restored',
        ];

        Schema::table('woocommerce_settings', function (Blueprint $table) use ($secretColumns): void {
            foreach ($secretColumns as $column) {
                $table->text($column)->nullable()->change();
            }
        });

        DB::table('woocommerce_settings')->orderBy('id')->each(function ($row) use ($secretColumns): void {
            $updates = [];
            foreach ($secretColumns as $column) {
                $value = $row->{$column} ?? null;
                if ($value === null || $value === '') {
                    continue;
                }
                try {
                    Crypt::decryptString($value);
                } catch (Throwable) {
                    $updates[$column] = Crypt::encryptString($value);
                }
            }
            if ($updates !== []) {
                DB::table('woocommerce_settings')->where('id', $row->id)->update($updates);
            }
        });
    }

    public function down(): void
    {
        // Deliberately irreversible: credentials must never be restored to plaintext.
    }
};
