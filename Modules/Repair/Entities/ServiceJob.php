<?php

namespace Modules\Repair\Entities;

use App\Models\Payment;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;

class ServiceJob extends Model
{
    protected $table = 'service_jobs';

    protected $fillable = [
        'reference_no',
        'customer_id',
        'service_type',
        'title',
        'description',
        'status',
        'priority',
        'assigned_to',
        'created_by',
        'expected_delivery_date',
        'delivery_date',
        'service_charge',
        'discount',
        'tax',
        'total_amount',
        'paid_amount',
        'due_amount',
        'warehouse_id',
        'note',
    ];

    protected $casts = [
        'expected_delivery_date' => 'date',
        'delivery_date'          => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class, 'customer_id');
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function device()
    {
        return $this->hasOne(ServiceDevice::class, 'service_job_id');
    }
    public function vehicle()
    {
        return $this->hasOne(ServiceVehicle::class, 'service_job_id');
    }
    public function items()
    {
        return $this->hasMany(ServiceJobItem::class, 'service_job_id');
    }
    public function updates()
    {
        return $this->hasMany(ServiceJobUpdate::class, 'service_job_id')->latest();
    }
    public function payments()
    {
        return $this->hasMany(Payment::class, 'service_job_id');
    }

    public function getStatusBadgeAttribute(): string
    {
        $map = ['pending' => 'warning', 'diagnosed' => 'info', 'in_progress' => 'primary', 'completed' => 'success', 'delivered' => 'secondary', 'cancelled' => 'danger'];
        $color = $map[$this->status] ?? 'secondary';
        return '<span class="badge badge-' . $color . '">' . ucfirst(str_replace('_', ' ', $this->status)) . '</span>';
    }

    public function getPriorityBadgeAttribute(): string
    {
        $map = ['low' => 'success', 'medium' => 'warning', 'high' => 'danger'];
        $color = $map[$this->priority] ?? 'secondary';
        return '<span class="badge badge-' . $color . '">' . ucfirst($this->priority) . '</span>';
    }



    // Modules/Repair/Entities/ServiceJob.php এ যোগ করুন:

    /** এই job এর linked Sale */
    public function sale()
    {
        return $this->hasOne(\App\Models\Sale::class, 'repair_id');
    }

    /**
     * Sale create বা update করে — parts/charges যেকোনো change এ call হবে
     */
    public function syncToSale(): \App\Models\Sale
{
    $this->refresh();

    $parts_total = $this->items()->sum('total');

    // Payment status
    $paid  = $this->paid_amount;
    $total = $this->total_amount;
    $due   = $this->due_amount;

    if ($paid <= 0)         $payment_status = 1; // Pending
    elseif ($due > 0.001)   $payment_status = 3; // Partial
    else                    $payment_status = 4; // Paid

    // Sale status
    $sale_status = in_array($this->status, ['completed', 'delivered']) ? 1 : 2;

    $saleData = [
        'repair_id'      => $this->id,
        'customer_id'    => $this->customer_id,
        'warehouse_id'   => $this->warehouse_id,
        'user_id'        => $this->created_by,
        'sale_type'      => 'repair',
        'sale_status'    => $sale_status,
        'payment_status' => $payment_status,
        'item'           => $this->items()->count(),
        'total_qty'      => $this->items()->sum('quantity'),
        'total_discount' => 0,
        'total_tax'      => 0,
        'total_price'    => $parts_total,
        'service_charge' => $this->service_charge,
        'order_tax_rate' => 0,
        'order_tax'      => $this->tax,
        'order_discount' => $this->discount,
        'shipping_cost'  => 0,
        'grand_total'    => $this->total_amount,
        'paid_amount'    => $this->paid_amount,
        'biller_id'      => \App\Models\Biller::where('is_active', true)->value('id') ?? 1,
        'currency_id'    => \App\Models\GeneralSetting::value('id') ?? 1,
        'exchange_rate'  => 1,
        'reference_no'   => $this->reference_no,
        'sale_note'      => $this->note,
    ];

    $sale = \App\Models\Sale::updateOrCreate(
        ['repair_id' => $this->id],
        $saleData
    );

    // ─── Sync product_sales ───────────────────────────────────────────────
    // Delete old product_sale rows for this sale, then re-insert fresh ones.
    \App\Models\Product_Sale::where('sale_id', $sale->id)->delete();

    foreach ($this->items as $item) {
        \App\Models\Product_Sale::create([
            'sale_id'        => $sale->id,
            'product_id'     => $item->product_id,
            'qty'            => $item->quantity,
            'sale_unit_id'   => 0,
            'net_unit_price' => $item->unit_price,
            'discount'       => $item->discount ?? 0,
            'tax_rate'       => $item->tax ?? 0,
            'tax'            => 0,
            'total'          => $item->total,
        ]);
    }
    // ─────────────────────────────────────────────────────────────────────

    return $sale;
}

public function recalculateTotals(): void
{
    $parts_total        = $this->items()->sum('total');
    $this->total_amount = $parts_total + $this->service_charge - $this->discount + $this->tax;
    $this->paid_amount  = $this->payments()->sum('amount');
    $this->due_amount   = $this->total_amount - $this->paid_amount;
    $this->saveQuietly();

    // Reload items relation so syncToSale() sees fresh data
    $this->load('items');

    $this->syncToSale();
}
}
