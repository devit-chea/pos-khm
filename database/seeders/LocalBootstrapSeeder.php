<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Minimal starting data for a fresh local install, in place of the vendor
 * installer's remote DB dump. Run after migrations and TenantDatabaseSeeder:
 *
 *   php artisan db:seed --class='Database\Seeders\LocalBootstrapSeeder' --force
 *
 * Safe to re-run (upserts by id / name). Login: admin / admin123
 */
class LocalBootstrapSeeder extends Seeder
{
    public function run()
    {
        $now = now();

        DB::table('currencies')->updateOrInsert(['id' => 1], [
            'name' => 'US Dollar', 'code' => 'USD', 'symbol' => '$', 'exchange_rate' => 1,
            'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        // Rate is per 1 USD (the base currency); adjust in Settings > Currency
        DB::table('currencies')->updateOrInsert(['id' => 2], [
            'name' => 'Cambodian Riel', 'code' => 'KHR', 'symbol' => '៛', 'exchange_rate' => 4100,
            'show_in_pos' => 1, 'rounding' => 100, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('general_settings')->updateOrInsert(['id' => 1], [
            'site_title' => 'SalePro', 'company_name' => 'SalePro', 'developed_by' => 'SalePro',
            'currency' => '1', 'currency_position' => 'prefix', 'decimal' => 2,
            'staff_access' => 'all', 'without_stock' => 'no', 'date_format' => 'd-m-Y',
            'theme' => 'default.css', 'invoice_format' => 'standard', 'timezone' => 'UTC',
            'is_rtl' => 0, 'is_zatca' => 0, 'is_packing_slip' => 0, 'modules' => '',
            'expiry_alert_days' => 0, 'created_at' => $now, 'updated_at' => $now,
        ]);

        // Role ids are hard-coded across the app: 1 = Admin, <= 2 = full access, 5 = Customer
        foreach ([1 => 'Admin', 2 => 'Owner', 4 => 'staff', 5 => 'Customer'] as $id => $name) {
            DB::table('roles')->updateOrInsert(['id' => $id], [
                'name' => $name, 'description' => $name, 'guard_name' => 'web',
                'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        foreach ($this->permissions() as $name) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name, 'guard_name' => 'web'],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }
        $permissionIds = DB::table('permissions')->pluck('id');
        foreach ([1, 2] as $roleId) {
            DB::table('role_has_permissions')->where('role_id', $roleId)->delete();
            DB::table('role_has_permissions')->insert(
                $permissionIds->map(fn ($id) => ['permission_id' => $id, 'role_id' => $roleId])->all()
            );
        }
        $this->grantCashierPermissions();

        DB::table('warehouses')->updateOrInsert(['id' => 1], [
            'name' => 'Main Warehouse', 'address' => 'Main Street', 'is_active' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('billers')->updateOrInsert(['id' => 1], [
            'name' => 'Main Biller', 'company_name' => 'SalePro', 'email' => 'biller@example.com',
            'phone_number' => '0000000000', 'address' => 'Main Street', 'city' => 'City',
            'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('customer_groups')->updateOrInsert(['id' => 1], [
            'name' => 'General', 'percentage' => '0', 'is_active' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('customers')->updateOrInsert(['id' => 1], [
            'customer_group_id' => 1, 'name' => 'Walk-in Customer', 'phone_number' => '0000000000',
            'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('accounts')->updateOrInsert(['id' => 1], [
            'account_no' => '11111', 'name' => 'Cash Account', 'initial_balance' => 0,
            'total_balance' => 0, 'is_default' => 1, 'is_active' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('pos_setting')->updateOrInsert(['id' => 1], [
            'customer_id' => 1, 'warehouse_id' => 1, 'biller_id' => 1, 'product_number' => 4,
            'keybord_active' => 0, 'payment_options' => 'cash,card,cheque,gift_card,deposit',
            'invoice_option' => 'A4', 'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('hrm_settings')->updateOrInsert(['id' => 1], [
            'checkin' => '9:00am', 'checkout' => '5:00pm', 'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('reward_point_settings')->updateOrInsert(['id' => 1], [
            'per_point_amount' => 100, 'minimum_amount' => 1000, 'duration' => 1, 'type' => 'Year',
            'is_active' => 0, 'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('invoice_settings')->updateOrInsert(['id' => 1], [
            'template_name' => 'Default', 'invoice_name' => 'Invoice', 'prefix' => 'INV',
            'number_of_digit' => '6', 'numbering_type' => 'sequential', 'start_number' => 1,
            'size' => 'a4', 'primary_color' => '#7c5cc4', 'secondary_color' => '#f5f5f5',
            'text_color' => '#000000', 'is_default' => 1, 'status' => 1,
            'show_column' => json_encode(array_fill_keys([
                'show_barcode', 'show_qr_code', 'show_description', 'show_in_words',
                'active_primary_color', 'show_warehouse_info', 'show_bill_to_info', 'show_footer_text',
                'show_biller_info', 'show_paid_info', 'show_payment_note', 'show_ref_number',
                'active_date_format', 'active_generat_settings', 'active_logo_height_width',
                'hide_total_due', 'show_vat_registration_number', 'show_sale_note', 'show_customer_name',
            ], 1)),
            'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('users')->updateOrInsert(['id' => 1], [
            'name' => 'admin', 'email' => 'admin@example.com', 'password' => Hash::make('admin123'),
            'phone' => '0000000000', 'company_name' => 'SalePro', 'role_id' => 1,
            'biller_id' => null, 'warehouse_id' => null, 'is_active' => 1, 'is_deleted' => 0,
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    /**
     * Staff (role 4) = cashier: POS and line/order discounts, but the unit price
     * stays read-only (no price_edit_in_sale), no editing/deleting sales.
     * Safe to call on its own against an existing database.
     */
    public function grantCashierPermissions(): void
    {
        $names = ['sales-index', 'sales-add', 'sidebar_sale', 'cart-product-update', 'handle_discount'];
        foreach ($names as $name) {
            DB::table('permissions')->updateOrInsert(['name' => $name, 'guard_name' => 'web'], ['updated_at' => now()]);
        }
        $ids = DB::table('permissions')->whereIn('name', $names)->pluck('id');
        DB::table('role_has_permissions')->where('role_id', 4)->delete();
        DB::table('role_has_permissions')->insert($ids->map(fn ($id) => ['permission_id' => $id, 'role_id' => 4])->all());
        cache()->forget('role_has_permissions_list4');
    }

    /**
     * Collected from hasPermissionTo()/permission: middleware/@can checks and the
     * role permission form. Missing names get created when a role is saved in Role > Permission.
     */
    private function permissions(): array
    {
        return array_filter(array_map('trim', file(__DIR__.'/data/permissions.txt')));
    }
}
