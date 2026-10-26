<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['woocommerce.sync','woocommerce.manage'] as $name) {
            DB::table('permissions')->updateOrInsert(['name'=>$name,'guard_name'=>'web'],['created_at'=>now(),'updated_at'=>now()]);
        }
        $permissions=DB::table('permissions')->whereIn('name',['woocommerce.sync','woocommerce.manage'])->pluck('id');
        foreach(DB::table('roles')->whereIn('id',[1,2])->pluck('id') as $role) foreach($permissions as $permission) DB::table('role_has_permissions')->updateOrInsert(['role_id'=>$role,'permission_id'=>$permission]);
        app('cache')->forget('spatie.permission.cache');
    }
    public function down(): void { throw new RuntimeException('WooCommerce action permissions require explicit downgrade review.'); }
};
