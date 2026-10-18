@extends('backend.layout.main')

@push('css')
<style>
    :root { --theme:#7c5cc4; --theme-dk:#6548b0; --theme-lt:#f3f0fb; }

    .ps-header { background:var(--theme); color:#fff; padding:10px 16px; }
    .ps-header h6 { margin:0; font-weight:600; font-size:.93rem; }
    .ps-section { border:1px solid #e2ddf5; border-radius:8px; overflow:hidden; margin-bottom:20px; }
    .ps-body { padding:16px; }

    /* search */
    #product-search { border-color:var(--theme); }
    #product-search:focus { box-shadow:0 0 0 .15rem rgba(124,92,196,.25); border-color:var(--theme); }
    .ui-autocomplete { z-index:9999; max-height:240px; overflow-y:auto; }
    .ui-menu-item .ui-menu-item-wrapper.ui-state-active { background:var(--theme); border-color:var(--theme); color:#fff; }

    /* parts table */
    #parts-table thead th { background:var(--theme-lt); color:var(--theme-dk); font-size:.82rem; border-color:#e2ddf5; }
    #parts-table td { vertical-align:middle; border-color:#ede9f8; font-size:.88rem; }
    #parts-table tbody tr:hover { background:#faf8ff; }

    /* qty stepper */
    .qty-wrap { display:flex; align-items:center; gap:4px; }
    .qty-btn  { width:26px; height:26px; padding:0; border:1px solid #c9bfee; border-radius:4px;
                background:#fff; color:var(--theme); font-size:1rem; line-height:1; cursor:pointer;
                display:flex; align-items:center; justify-content:center; transition:.15s; }
    .qty-btn:hover { background:var(--theme); color:#fff; border-color:var(--theme); }
    .qty-input { width:58px; height:26px; border:1px solid #c9bfee; border-radius:4px;
                 text-align:center; font-size:.85rem; }
    .qty-input:focus { outline:none; border-color:var(--theme); }

    /* price edit */
    .price-input { width:88px; height:26px; border:1px solid #c9bfee; border-radius:4px;
                   text-align:right; font-size:.85rem; padding:0 6px; }
    .price-input:focus { outline:none; border-color:var(--theme); }

    /* summary */
    .sum-wrap  { border:1px solid #e2ddf5; border-radius:8px; overflow:hidden; margin-bottom:18px; }
    .sum-head  { background:var(--theme); color:#fff; padding:10px 16px; }
    .sum-head h6 { margin:0; font-weight:600; }
    .sum-row   { display:flex; justify-content:space-between; align-items:center;
                 padding:8px 16px; border-bottom:1px solid #f0ecfb; font-size:.9rem; }
    .sum-row:last-child { border-bottom:none; }
    .sum-row.grand { font-weight:700; font-size:1rem; background:var(--theme-lt); }
    .sum-row.paid-r { color:#28a745; font-weight:600; }
    .sum-row.due-r  { color:#dc3545; font-weight:700; font-size:1.02rem; }
    .sum-row.due-r.ok { color:#28a745; }
    .pill { display:inline-block; padding:5px 16px; border-radius:20px; font-size:.85rem; font-weight:600; }
    .pill.due   { background:#fde8e8; color:#dc3545; border:1px solid #f5c2c2; }
    .pill.clear { background:#e8f8ed; color:#28a745; border:1px solid #b8e8c6; }

    /* payment form */
    .pay-form { background:#faf8ff; border:1px dashed #c9bfee; border-radius:8px; padding:14px; margin-bottom:14px; }
    #payments-table thead th { background:var(--theme-lt); color:var(--theme-dk); font-size:.82rem; border-color:#e2ddf5; }
    #payments-table td { vertical-align:middle; border-color:#ede9f8; font-size:.88rem; }

    /* charge inputs */
    .charge-inp { height:32px; border-color:#c9bfee; font-size:.88rem; }
    .charge-inp:focus { border-color:var(--theme); box-shadow:0 0 0 .12rem rgba(124,92,196,.2); }

    /* btns */
    .btn-theme   { background:var(--theme); color:#fff; border:none; }
    .btn-theme:hover { background:var(--theme-dk); color:#fff; }
    .btn-theme-ol { background:#fff; color:var(--theme); border:1px solid var(--theme); }
    .btn-theme-ol:hover { background:var(--theme); color:#fff; }

    /* job info */
    .ji-table td,.ji-table th { font-size:.83rem; padding:5px 8px; border:none; border-bottom:1px solid #f0ecfb; }
    .ji-table th { color:#888; font-weight:500; width:110px; }

    /* spinner */
    .sp-ov { display:none; position:fixed; inset:0; background:rgba(0,0,0,.22); z-index:9999; justify-content:center; align-items:center; }
    .sp-ov.on { display:flex; }

    /* saving indicator on rows */
    tr.saving { opacity:.6; pointer-events:none; }

    /* ══════════════════════════════════════════════
       PRINT INVOICE STYLES  — only applies on print
       ══════════════════════════════════════════════ */
    @media print {
        body * { visibility: hidden !important; }
        #invoice-print-area, #invoice-print-area * { visibility: visible !important; }
        #invoice-print-area {
            position: fixed !important;
            inset: 0 !important;
            width: 100% !important;
            height: 100% !important;
            z-index: 99999 !important;
            background: #fff !important;
        }
        .inv-no-print { display: none !important; }
    }

    /* Invoice design variables */
    .invoice-wrap {
        font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
        background: #fff;
        color: #1a1a2e;
        padding: 0;
        margin: 0;
    }

    /* Header bar */
    .inv-header {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%);
        color: #fff;
        padding: 32px 40px 24px;
        position: relative;
        overflow: hidden;
    }
    .inv-header::before {
        content: '';
        position: absolute;
        top: -40px; right: -40px;
        width: 200px; height: 200px;
        background: rgba(124,92,196,.18);
        border-radius: 50%;
    }
    .inv-header::after {
        content: '';
        position: absolute;
        bottom: -60px; right: 80px;
        width: 140px; height: 140px;
        background: rgba(124,92,196,.1);
        border-radius: 50%;
    }
    .inv-company-name {
        font-size: 26px;
        font-weight: 800;
        letter-spacing: -0.5px;
        margin: 0 0 2px;
        position: relative;
        z-index: 1;
    }
    .inv-company-name span { color: #a78bfa; }
    .inv-company-sub {
        font-size: 11px;
        color: #94a3b8;
        letter-spacing: 2px;
        text-transform: uppercase;
        position: relative;
        z-index: 1;
    }
    .inv-badge {
        display: inline-block;
        background: #a78bfa;
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 2px;
        text-transform: uppercase;
        padding: 4px 14px;
        border-radius: 20px;
        margin-bottom: 8px;
    }
    .inv-number {
        font-size: 28px;
        font-weight: 900;
        letter-spacing: -1px;
        color: #fff;
        margin: 0;
    }
    .inv-date-label { font-size: 10px; color: #94a3b8; letter-spacing: 1px; text-transform: uppercase; }
    .inv-date-val { font-size: 13px; color: #e2e8f0; font-weight: 600; margin-top: 1px; }

    /* Info section */
    .inv-info-row {
        display: flex;
        gap: 0;
        border-bottom: 2px solid #f1f5f9;
    }
    .inv-bill-box {
        flex: 1;
        padding: 20px 28px;
        border-right: 1px solid #f1f5f9;
    }
    .inv-bill-box:last-child { border-right: none; }
    .inv-box-label {
        font-size: 9px;
        font-weight: 700;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: #7c5cc4;
        margin-bottom: 8px;
        padding-bottom: 6px;
        border-bottom: 2px solid #7c5cc4;
        display: inline-block;
    }
    .inv-cust-name { font-size: 15px; font-weight: 700; color: #1e293b; margin: 0 0 2px; }
    .inv-cust-detail { font-size: 12px; color: #64748b; line-height: 1.7; }

    /* Status chips */
    .inv-chip {
        display: inline-block;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .5px;
        padding: 3px 10px;
        border-radius: 4px;
        text-transform: uppercase;
    }
    .inv-chip-purple { background: #ede9fe; color: #6d28d9; }
    .inv-chip-green  { background: #dcfce7; color: #166534; }
    .inv-chip-orange { background: #fff7ed; color: #9a3412; }
    .inv-chip-blue   { background: #dbeafe; color: #1e40af; }

    /* Items table */
    .inv-table-wrap { padding: 0 28px; }
    .inv-table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }
    .inv-table thead tr {
        background: #1a1a2e;
    }
    .inv-table thead th {
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        padding: 12px 14px;
        text-align: left;
    }
    .inv-table thead th:last-child { text-align: right; }
    .inv-table thead th.num { text-align: right; }
    .inv-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .1s; }
    .inv-table tbody tr:nth-child(even) { background: #fafaff; }
    .inv-table tbody td {
        padding: 11px 14px;
        font-size: 12.5px;
        color: #1e293b;
        vertical-align: middle;
    }
    .inv-table tbody td.num { text-align: right; font-variant-numeric: tabular-nums; }
    .inv-table tbody td .prod-code { font-size: 10px; color: #94a3b8; margin-top: 1px; }

    /* Totals */
    .inv-totals-wrap {
        display: flex;
        justify-content: flex-end;
        padding: 0 28px 24px;
    }
    .inv-totals {
        width: 300px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        overflow: hidden;
    }
    .inv-tot-row {
        display: flex;
        justify-content: space-between;
        padding: 9px 16px;
        font-size: 12.5px;
        border-bottom: 1px solid #f1f5f9;
    }
    .inv-tot-row:last-child { border-bottom: none; }
    .inv-tot-row .lbl { color: #64748b; }
    .inv-tot-row .val { font-weight: 600; color: #1e293b; font-variant-numeric: tabular-nums; }
    .inv-tot-row.grand-row {
        background: #1a1a2e;
    }
    .inv-tot-row.grand-row .lbl { color: #94a3b8; font-weight: 700; font-size: 13px; }
    .inv-tot-row.grand-row .val { color: #fff; font-weight: 800; font-size: 15px; }
    .inv-tot-row.paid-row .val  { color: #16a34a; }
    .inv-tot-row.due-row  .val  { color: #dc2626; font-weight: 800; font-size: 14px; }
    .inv-tot-row.due-row.ok .val { color: #16a34a; }
    .inv-disc-val { color: #dc2626 !important; }

    /* Payments section */
    .inv-pay-section { padding: 0 28px 20px; }
    .inv-pay-title {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: #7c5cc4;
        margin-bottom: 10px;
        padding-bottom: 6px;
        border-bottom: 2px solid #7c5cc4;
        display: inline-block;
    }
    .inv-pay-table { width: 100%; border-collapse: collapse; }
    .inv-pay-table th {
        font-size: 10px;
        letter-spacing: 1px;
        text-transform: uppercase;
        color: #64748b;
        font-weight: 600;
        padding: 6px 10px;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
    }
    .inv-pay-table td {
        font-size: 12px;
        padding: 7px 10px;
        border-bottom: 1px solid #f8f9fa;
        color: #1e293b;
    }
    .inv-method-chip {
        background: #f1f5f9;
        color: #475569;
        font-size: 10px;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 4px;
    }

    /* Due stamp */
    .inv-stamp-wrap { padding: 0 28px 20px; display: flex; justify-content: space-between; align-items: flex-end; }
    .inv-stamp {
        width: 130px;
        height: 130px;
        border: 4px solid #16a34a;
        border-radius: 50%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        transform: rotate(-18deg);
        opacity: .85;
        flex-shrink: 0;
    }
    .inv-stamp.due-stamp { border-color: #dc2626; }
    .inv-stamp-text { font-size: 15px; font-weight: 900; letter-spacing: 2px; color: #16a34a; text-transform: uppercase; }
    .inv-stamp.due-stamp .inv-stamp-text { color: #dc2626; }

    /* Notes */
    .inv-notes { flex: 1; padding-right: 40px; }
    .inv-notes-label { font-size: 10px; font-weight: 700; color: #94a3b8; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 4px; }
    .inv-notes-text { font-size: 12px; color: #475569; line-height: 1.6; }

    /* Footer */
    .inv-footer {
        background: #f8fafc;
        border-top: 2px solid #e2e8f0;
        padding: 14px 28px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .inv-footer-brand { font-size: 11px; color: #94a3b8; }
    .inv-footer-brand strong { color: #64748b; }
    .inv-footer-sig { text-align: right; }
    .inv-footer-sig-line { border-top: 1px solid #cbd5e1; width: 140px; margin-bottom: 4px; }
    .inv-footer-sig-label { font-size: 10px; color: #94a3b8; letter-spacing: 1px; text-transform: uppercase; }

    /* Divider accent */
    .inv-accent-bar {
        height: 4px;
        background: linear-gradient(90deg, #7c5cc4, #a78bfa, #7c5cc4);
    }
</style>
@endpush

@section('content')

@if(session('message'))
    <div class="alert alert-success alert-dismissible text-center">
        <button class="close" data-dismiss="alert">&times;</button>{{ session('message') }}
    </div>
@endif

<div class="sp-ov" id="sp">
    <div class="spinner-border text-light" style="width:3rem;height:3rem"></div>
</div>

{{-- ══ HIDDEN PRINT INVOICE AREA ══ --}}
<div id="invoice-print-area" style="display:none; background:#fff; min-height:100vh;">
    <div class="invoice-wrap">

        {{-- Accent bar --}}
        <div class="inv-accent-bar"></div>

        {{-- Header --}}
        <div class="inv-header">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; position:relative; z-index:1;">
                <div>
                    <div class="inv-badge">Service Invoice</div>
                    <div class="inv-company-name">{{ $general_setting->site_title }}</div>
                    <div class="inv-company-sub">Professional Service &amp; Repair</div>
                </div>
                <div style="text-align:right;">
                    <div class="inv-date-label">Invoice No.</div>
                    <div class="inv-number">#{{ $job->reference_no }}</div>
                    <div style="margin-top:10px;">
                        <div class="inv-date-label">Date Issued</div>
                        <div class="inv-date-val">{{ date(config('date_format'), strtotime($job->created_at)) }}</div>
                    </div>
                    @if($job->expected_delivery_date)
                    <div style="margin-top:6px;">
                        <div class="inv-date-label">Expected Delivery</div>
                        <div class="inv-date-val">{{ $job->expected_delivery_date->format(config('date_format')) }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Info Row --}}
        <div class="inv-info-row">
            <div class="inv-bill-box">
                <div class="inv-box-label">Bill To</div>
                <div class="inv-cust-name">{{ optional($job->customer)->name ?? 'N/A' }}</div>
                <div class="inv-cust-detail">
                    @if(optional($job->customer)->phone) {{ $job->customer->phone }}<br>@endif
                    @if(optional($job->customer)->email) {{ $job->customer->email }}<br>@endif
                    @if(optional($job->customer)->address) {{ $job->customer->address }}@endif
                </div>
            </div>
            <div class="inv-bill-box">
                <div class="inv-box-label">Job Details</div>
                <div class="inv-cust-name">{{ $job->title }}</div>
                <div class="inv-cust-detail">
                    Type: <strong>{{ ucfirst($job->service_type) }}</strong><br>
                    Warehouse: <strong>{{ optional($job->warehouse)->name ?? 'N/A' }}</strong><br>
                    @if($job->assignedTo) Technician: <strong>{{ $job->assignedTo->name }}</strong>@endif
                </div>
            </div>
            <div class="inv-bill-box" style="flex:0 0 170px; min-width:170px;">
                <div class="inv-box-label">Status</div>
                <div style="margin-bottom:6px;">
                    <span class="inv-chip inv-chip-purple">{{ ucfirst(str_replace('_',' ',$job->status)) }}</span>
                </div>
                <div class="inv-box-label" style="margin-top:10px;">Priority</div>
                <span class="inv-chip inv-chip-orange">{{ ucfirst($job->priority ?? 'Medium') }}</span>
            </div>
        </div>

        {{-- Items Table --}}
        <div class="inv-table-wrap">
            <table class="inv-table" id="inv-items-table">
                <thead>
                    <tr>
                        <th width="36">#</th>
                        <th>Product / Part</th>
                        <th class="num" width="90">Qty</th>
                        <th class="num" width="110">Unit Price</th>
                        <th class="num" width="110">Total</th>
                    </tr>
                </thead>
                <tbody id="inv-items-tbody">
                    {{-- Populated by JS on open --}}
                </tbody>
            </table>
        </div>

        {{-- Totals --}}
        <div class="inv-totals-wrap">
            <div class="inv-totals">
                <div class="inv-tot-row">
                    <span class="lbl">Parts Total</span>
                    <span class="val" id="inv-s-parts">{{ number_format($job->items->sum('total'), config('decimal')) }}</span>
                </div>
                <div class="inv-tot-row">
                    <span class="lbl">Service Charge</span>
                    <span class="val" id="inv-s-sc">{{ number_format($job->service_charge, config('decimal')) }}</span>
                </div>
                <div class="inv-tot-row">
                    <span class="lbl">Discount</span>
                    <span class="val inv-disc-val" id="inv-s-dc">− {{ number_format($job->discount, config('decimal')) }}</span>
                </div>
                <div class="inv-tot-row">
                    <span class="lbl">Tax</span>
                    <span class="val" id="inv-s-tx">{{ number_format($job->tax, config('decimal')) }}</span>
                </div>
                <div class="inv-tot-row grand-row">
                    <span class="lbl">Grand Total</span>
                    <span class="val" id="inv-s-gt">{{ number_format($job->total_amount, config('decimal')) }}</span>
                </div>
                <div class="inv-tot-row paid-row">
                    <span class="lbl" style="color:#64748b;">Paid Amount</span>
                    <span class="val" id="inv-s-paid">{{ number_format($job->paid_amount, config('decimal')) }}</span>
                </div>
                <div class="inv-tot-row due-row {{ $job->due_amount<=0?'ok':'' }}" id="inv-due-row">
                    <span class="lbl" style="color:#64748b;">Due Amount</span>
                    <span class="val" id="inv-s-due">{{ number_format($job->due_amount, config('decimal')) }}</span>
                </div>
            </div>
        </div>

        {{-- Payments --}}
        @if($job->payments->count())
        <div class="inv-pay-section">
            <div class="inv-pay-title">Payment History</div>
            <table class="inv-pay-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($job->payments as $idx => $p)
                    <tr>
                        <td>{{ $idx+1 }}</td>
                        <td>{{ $p->payment_at ? $p->payment_at->format(config('date_format')) : '—' }}</td>
                        <td style="font-weight:700; color:#16a34a;">{{ number_format($p->amount, config('decimal')) }}</td>
                        <td><span class="inv-method-chip">{{ $p->paying_method }}</span></td>
                        <td>{{ $p->payment_note ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- Stamp + Notes --}}
        <div class="inv-stamp-wrap">
            <div class="inv-notes">
                <div class="inv-notes-label">Notes &amp; Terms</div>
                <div class="inv-notes-text">
                    @if($job->note)
                        {{ $job->note }}
                    @else
                        Thank you for choosing our service. Please retain this invoice for your records.<br>
                        For inquiries, contact us at {{ $general_setting->site_title }}.
                    @endif
                </div>
            </div>
            <div class="inv-stamp {{ $job->due_amount<=0?'':'due-stamp' }}">
                <div class="inv-stamp-text">{{ $job->due_amount<=0 ? 'PAID' : 'DUE' }}</div>
                <div style="font-size:10px; font-weight:600; color:{{ $job->due_amount<=0?'#16a34a':'#dc2626' }}; margin-top:3px; letter-spacing:1px;">
                    {{ $job->due_amount<=0 ? 'IN FULL' : number_format($job->due_amount,config('decimal')) }}
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="inv-footer">
            <div class="inv-footer-brand">
                <strong>{{ $general_setting->site_title }}</strong><br>
                Generated: {{ now()->format(config('date_format')) }} &nbsp;·&nbsp;
                Ref: {{ $job->reference_no }}<br>
                @if(optional($job->createdBy)->name) Prepared by: {{ $job->createdBy->name }}@endif
            </div>
            <div class="inv-footer-sig">
                <div class="inv-footer-sig-line"></div>
                <div class="inv-footer-sig-label">Authorized Signature</div>
            </div>
        </div>

        <div class="inv-accent-bar"></div>

    </div>
</div>
{{-- ══ END PRINT AREA ══ --}}


<section><div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex align-items-start justify-content-between mb-3">
        <div>
            <h5 class="mb-1 font-weight-bold" style="color:var(--theme)">
                <i class="fa fa-wrench mr-2"></i>Parts &amp; Billing
                <small class="text-muted font-weight-normal" style="font-size:.83rem;">{{ $job->reference_no }}</small>
            </h5>
            <small class="text-muted">
                Customer: <strong>{{ optional($job->customer)->name }}</strong>
                &nbsp;|&nbsp; Warehouse: <strong>{{ optional($job->warehouse)->name }}</strong>
                &nbsp;|&nbsp; {!! $job->status_badge !!} {!! $job->priority_badge !!}
            </small>
        </div>
        <div style="display:flex;gap:6px;">
            <a href="{{ route('repair.service.show',$job->id) }}" class="btn btn-sm btn-theme-ol">
                <i class="fa fa-eye"></i> View Job
            </a>
            <a href="{{ route('repair.service.index') }}" class="btn btn-sm btn-secondary">
                <i class="fa fa-list"></i> All Jobs
            </a>
            <button onclick="openPrintInvoice()" class="btn btn-sm btn-default">
                <i class="dripicons-print"></i> Print Invoice
            </button>
        </div>
    </div>

    <div class="row">

    {{-- ═══════════════════ LEFT ═══════════════════ --}}
    <div class="col-md-8">

        {{-- PARTS --}}
        <div class="ps-section">
            <div class="ps-header"><h6><i class="fa fa-cogs mr-2"></i>Parts / Items Used</h6></div>
            <div class="ps-body">

                {{-- search --}}
                <div class="d-flex align-items-center mb-3" style="gap:8px;">
                    <div class="input-group" style="max-width:380px;">
                        <input type="text" id="product-search" class="form-control"
                            placeholder="Search product by code or name..." autocomplete="off"/>
                        <div class="input-group-append">
                            <span class="input-group-text" style="background:var(--theme);color:#fff;border-color:var(--theme);">
                                <i class="fa fa-search"></i>
                            </span>
                        </div>
                    </div>
                    <small class="text-muted">Select a product to add it instantly</small>
                </div>

                <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" id="parts-table">
                    <thead>
                        <tr>
                            <th width="36">#</th>
                            <th>Product</th>
                            <th width="148">Quantity</th>
                            <th width="110">Unit Price</th>
                            <th width="100">Total</th>
                            <th width="46"><i class="dripicons-trash"></i></th>
                        </tr>
                    </thead>
                    <tbody id="parts-tbody">
                    @forelse($job->items as $idx => $item)
                        <tr data-item-id="{{ $item->id }}"
                            data-product-id="{{ $item->product_id }}"
                            data-stock="{{ optional($item->product)->qty ?? 9999 }}">
                            <td class="rn">{{ $idx+1 }}</td>
                            <td>{{ optional($item->product)->name }}
                                <span class="text-muted">[{{ optional($item->product)->code }}]</span>
                            </td>
                            <td>
                                <div class="qty-wrap">
                                    <button type="button" class="qty-btn qm">−</button>
                                    <input type="number" class="qty-input qi"
                                        value="{{ $item->quantity }}" min="0.01" step="any"
                                        data-item="{{ $item->id }}" data-price="{{ $item->unit_price }}"/>
                                    <button type="button" class="qty-btn qp">+</button>
                                </div>
                            </td>
                            <td>
                                <input type="number" class="price-input pi"
                                    value="{{ $item->unit_price }}"
                                    data-item="{{ $item->id }}" step="any" min="0"/>
                            </td>
                            <td class="pt font-weight-bold">{{ number_format($item->total,config('decimal')) }}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-danger rp" data-item="{{ $item->id }}">
                                    <i class="fa fa-times"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr id="epr">
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fa fa-search mr-1"></i>Search a product above to add parts
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                    <tfoot>
                        <tr style="background:var(--theme-lt);">
                            <td colspan="4" class="text-right font-weight-bold" style="color:var(--theme);">Parts Total:</td>
                            <td id="ft-parts" class="font-weight-bold" style="color:var(--theme);">
                                {{ number_format($job->items->sum('total'),config('decimal')) }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
                </div>

            </div>
        </div>

        {{-- SERVICE CHARGES --}}
        <div class="ps-section">
            <div class="ps-header" style="background:#5a6872;"><h6><i class="fa fa-calculator mr-2"></i>Service Charges</h6></div>
            <div class="ps-body">
                <div class="row">
                    <div class="col-md-4 form-group mb-2">
                        <label style="font-size:.82rem;color:#666;">Service Charge</label>
                        <input type="number" id="sc" class="form-control charge-inp" value="{{ $job->service_charge }}" step="any" min="0"/>
                    </div>
                    <div class="col-md-4 form-group mb-2">
                        <label style="font-size:.82rem;color:#666;">Discount</label>
                        <input type="number" id="dc" class="form-control charge-inp" value="{{ $job->discount }}" step="any" min="0"/>
                    </div>
                    <div class="col-md-4 form-group mb-2">
                        <label style="font-size:.82rem;color:#666;">Tax</label>
                        <input type="number" id="tx" class="form-control charge-inp" value="{{ $job->tax }}" step="any" min="0"/>
                    </div>
                </div>
                <button type="button" id="save-ch" class="btn btn-sm btn-theme">
                    <i class="dripicons-checkmark"></i> Save Charges
                </button>
                <small class="text-muted ml-2">Totals update automatically after saving.</small>
            </div>
        </div>

        {{-- PAYMENTS --}}
        <div class="ps-section">
            <div class="ps-header" style="background:#455a64;"><h6><i class="fa fa-money mr-2"></i>Payments Received</h6></div>
            <div class="ps-body">

                <div class="pay-form">
                    <p class="mb-2 font-weight-bold text-muted" style="font-size:.85rem;">+ Collect New Payment</p>
                    <div class="row">
                        <div class="col-md-3 form-group mb-2">
                            <label style="font-size:.8rem;color:#666;">Amount *</label>
                            <input type="number" id="pa" class="form-control form-control-sm" placeholder="0.00" step="any" min="0.01"/>
                        </div>
                        <div class="col-md-3 form-group mb-2">
                            <label style="font-size:.8rem;color:#666;">Method *</label>
                            <select id="pm" class="form-control form-control-sm">
                                <option>Cash</option><option>Cheque</option><option>Card</option>
                                <option>bKash</option><option>Nagad</option><option>Rocket</option>
                                <option>Bank Transfer</option>
                            </select>
                        </div>
                        <div class="col-md-3 form-group mb-2">
                            <label style="font-size:.8rem;color:#666;">Account *</label>
                            <select id="pact" class="selectpicker form-control form-control-sm" data-live-search="true">
                                @foreach($lims_account_list as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group mb-2">
                            <label style="font-size:.8rem;color:#666;">Reference Note</label>
                            <input type="text" id="pr" class="form-control form-control-sm" placeholder="TrxID, cheque no..."/>
                        </div>
                    </div>
                    <button type="button" id="add-pay" class="btn btn-sm btn-theme">
                        <i class="fa fa-check mr-1"></i> Collect Payment
                    </button>
                    <small id="pay-err" class="text-danger ml-2"></small>
                </div>

                <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" id="payments-table">
                    <thead>
                        <tr>
                            <th width="36">#</th><th>Date</th><th>Amount</th>
                            <th>Method</th><th>Reference</th><th>Note</th>
                            <th width="46"><i class="dripicons-trash"></i></th>
                        </tr>
                    </thead>
                    <tbody id="pay-tbody">
                    @forelse($job->payments as $idx => $p)
                        <tr data-pid="{{ $p->id }}">
                            <td>{{ $idx+1 }}</td>
                            <td>{{ $p->payment_at ? $p->payment_at->format(config('date_format')) : '—' }}</td>
                            <td class="font-weight-bold" style="color:#28a745;">{{ number_format($p->amount,config('decimal')) }}</td>
                            <td><span class="badge badge-secondary">{{ $p->paying_method }}</span></td>
                            <td>{{ $p->payment_reference ?? '—' }}</td>
                            <td>{{ $p->payment_note ?? '—' }}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-danger rpy" data-payment="{{ $p->id }}">
                                    <i class="fa fa-times"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr id="epr-pay"><td colspan="7" class="text-center text-muted py-4">No payments yet.</td></tr>
                    @endforelse
                    </tbody>
                    <tfoot>
                        <tr style="background:var(--theme-lt);">
                            <td colspan="2" class="text-right font-weight-bold" style="color:var(--theme);">Total Paid:</td>
                            <td id="ft-paid" class="font-weight-bold" style="color:#28a745;">
                                {{ number_format($job->payments->sum('amount'),config('decimal')) }}
                            </td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                </table>
                </div>

            </div>
        </div>

    </div>{{-- /col-md-8 --}}

    {{-- ═══════════════════ RIGHT ═══════════════════ --}}
    <div class="col-md-4">

        {{-- Billing Summary --}}
        <div class="sum-wrap mb-4">
            <div class="sum-head"><h6><i class="fa fa-file-text mr-2"></i>Billing Summary</h6></div>
            <div class="sum-row">
                <span class="text-muted">Parts Total</span>
                <span id="s-parts" class="font-weight-bold">{{ number_format($job->items->sum('total'),config('decimal')) }}</span>
            </div>
            <div class="sum-row">
                <span class="text-muted">Service Charge</span>
                <span id="s-sc">{{ number_format($job->service_charge,config('decimal')) }}</span>
            </div>
            <div class="sum-row">
                <span class="text-muted">Discount</span>
                <span id="s-dc" class="text-danger">− {{ number_format($job->discount,config('decimal')) }}</span>
            </div>
            <div class="sum-row">
                <span class="text-muted">Tax</span>
                <span id="s-tx">{{ number_format($job->tax,config('decimal')) }}</span>
            </div>
            <div class="sum-row grand">
                <span>Grand Total</span>
                <span id="s-gt">{{ number_format($job->total_amount,config('decimal')) }}</span>
            </div>
            <div class="sum-row paid-r">
                <span>Paid Amount</span>
                <span id="s-paid">{{ number_format($job->paid_amount,config('decimal')) }}</span>
            </div>
            <div class="sum-row due-r {{ $job->due_amount<=0?'ok':'' }}" id="due-r">
                <span>Due Amount</span>
                <span id="s-due">{{ number_format($job->due_amount,config('decimal')) }}</span>
            </div>
            <div class="text-center py-3" style="border-top:1px solid #e2ddf5;">
                <span id="s-badge" class="pill {{ $job->due_amount<=0?'clear':'due' }}">
                    {{ $job->due_amount<=0 ? '✓ Fully Paid' : '⚠ Amount Due' }}
                </span>
            </div>
        </div>

        {{-- Job Info --}}
        <div class="sum-wrap">
            <div class="sum-head" style="background:#5a6872;"><h6><i class="fa fa-info-circle mr-2"></i>Job Info</h6></div>
            <div style="padding:4px 0;">
            <table class="ji-table" style="width:100%">
                <tr><th>Reference</th><td>{{ $job->reference_no }}</td></tr>
                <tr><th>Type</th><td>{{ ucfirst($job->service_type) }}</td></tr>
                <tr><th>Title</th><td>{{ $job->title }}</td></tr>
                <tr><th>Customer</th><td>{{ optional($job->customer)->name }}</td></tr>
                <tr><th>Warehouse</th><td>{{ optional($job->warehouse)->name }}</td></tr>
                <tr><th>Status</th><td>{!! $job->status_badge !!}</td></tr>
                @if($job->expected_delivery_date)
                <tr><th>Expected</th><td>{{ $job->expected_delivery_date->format(config('date_format')) }}</td></tr>
                @endif
            </table>
            </div>
        </div>

    </div>{{-- /col-md-4 --}}

    </div>{{-- /row --}}
</div></section>

@endsection

@push('scripts')
<script>
$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

var JOB      = {{ $job->id }};
var PC       = {{ $job->items->count() }};
var PYC      = {{ $job->payments->count() }};
var DEC      = {{ config('decimal') }};
var QTY_TMR  = null;

// ══════════════════════════════════════════════════════════════════
// PRODUCT AUTOCOMPLETE — select → instantly add to table
// ══════════════════════════════════════════════════════════════════
var CODES = [ @foreach($productArray as $p) "{{ $p }}", @endforeach ];

$('#product-search').autocomplete({
    source: function(req, res){
        var m = new RegExp('.?' + $.ui.autocomplete.escapeRegex(req.term), 'i');
        res($.grep(CODES, function(x){ return m.test(x); }));
    },
    minLength: 1,
    select: function(e, ui){
        e.preventDefault();
        $('#product-search').val('').prop('disabled', true);
        sp(true);
        $.get('{{ route("repair.products.search") }}', { data: ui.item.value }, function(res){
            console.log('Full response:', res);
            console.log('d[0] name:', res[0][0]);
            console.log('d[2] price:', res[0][2]);
            console.log('d[10] cost:', res[0][10]);
            if (!res || !res[0]){ sp(false); $('#product-search').prop('disabled',false); return; }
            var d = res[0];
            $.post('/repair/service/'+JOB+'/add-part',
                { product_id: d[8], quantity: 1, unit_price: d[2] },
                function(r){
                    if (r.success){
                        addRow(r.item, d[8], d[7] ?? 9999);
                        sync(r.job_totals);
                        toast('Part added!');
                    }
                }
            ).fail(function(xhr){
                alert(xhr.responseJSON ? xhr.responseJSON.message : 'Error adding part.');
            }).always(function(){ sp(false); $('#product-search').prop('disabled',false).focus(); });
        });
    }
});

function addRow(item, productId, stock){
    $('#epr').remove(); PC++;
    var uPrice = parseFloat(String(item.unit_price).replace(/,/g,'')) || 0;
    $('#parts-tbody').append(
        '<tr data-item-id="'+item.id+'" data-product-id="'+productId+'" data-stock="'+stock+'">'
        +'<td class="rn">'+PC+'</td>'
        +'<td>'+item.product_name+'</td>'
        +'<td><div class="qty-wrap">'
        +'<button type="button" class="qty-btn qm">−</button>'
        +'<input type="number" class="qty-input qi" value="'+item.quantity+'" min="0.01" step="any"'
        +' data-item="'+item.id+'" data-price="'+uPrice+'"/>'
        +'<button type="button" class="qty-btn qp">+</button>'
        +'</div></td>'
        +'<td><input type="number" class="price-input pi" value="'+uPrice+'" data-item="'+item.id+'" step="any" min="0"/></td>'
        +'<td class="pt font-weight-bold">'+item.total+'</td>'
        +'<td><button type="button" class="btn btn-sm btn-danger rp" data-item="'+item.id+'"><i class="fa fa-times"></i></button></td>'
        +'</tr>'
    );
}

// ══════════════════════════════════════════════════════════════════
// QTY STEPPER  —  − button
// ══════════════════════════════════════════════════════════════════
$(document).on('click','.qm', function(){
    var $i = $(this).siblings('.qi');
    var v  = parseFloat($i.val()) || 1;
    var nv = Math.max(0.01, parseFloat((v - 1).toFixed(4)));
    $i.val(nv).trigger('qty-change');
});

// ══════════════════════════════════════════════════════════════════
// QTY STEPPER  —  + button
// ══════════════════════════════════════════════════════════════════
$(document).on('click','.qp', function(){
    var $i    = $(this).siblings('.qi');
    var v     = parseFloat($i.val()) || 0;
    var stock = parseFloat($i.closest('tr').data('stock')) || 9999;
    var nv    = parseFloat((v + 1).toFixed(4));
    if (nv > stock){ alert('Cannot exceed available stock ('+stock+')'); return; }
    $i.val(nv).trigger('qty-change');
});

// ══════════════════════════════════════════════════════════════════
// QTY INPUT — manual typing
// ══════════════════════════════════════════════════════════════════
$(document).on('input', '.qi', function(){
    $(this).trigger('qty-change');
});

// ── Debounced qty-change handler ──────────────────────────────────
$(document).on('qty-change', '.qi', function(){
    var $i     = $(this);
    var itemId = $i.data('item');
    var price  = parseFloat($i.data('price')) || 0;
    var qty    = parseFloat($i.val()) || 0;
    var stock  = parseFloat($i.closest('tr').data('stock')) || 9999;
    var $row   = $i.closest('tr');

    if (qty < 0.01){ qty = 0.01; $i.val(qty); }
    if (qty > stock){ qty = stock; $i.val(qty); }

    // local preview
    $row.find('.pt').text((qty * price).toFixed(DEC));

    clearTimeout(QTY_TMR);
    QTY_TMR = setTimeout(function(){
        doUpdatePart(itemId, qty, price, $row);
    }, 700);
});

// ══════════════════════════════════════════════════════════════════
// PRICE EDIT — blur / enter
// ══════════════════════════════════════════════════════════════════
$(document).on('change blur', '.pi', function(){
    var $i     = $(this);
    var itemId = $i.data('item');
    var price  = parseFloat($i.val()) || 0;
    var $row   = $i.closest('tr');
    var qty    = parseFloat($row.find('.qi').val()) || 1;

    // update price cache on qty input too
    $row.find('.qi').data('price', price);
    $row.find('.pt').text((qty * price).toFixed(DEC));

    doUpdatePart(itemId, qty, price, $row);
});

// ── AJAX: update part qty+price ───────────────────────────────────
function doUpdatePart(itemId, qty, price, $row){
    $row.addClass('saving');
    $.post('/repair/service/'+JOB+'/update-part/'+itemId,
        { quantity: qty, unit_price: price },
        function(res){
            if (res.success){
                $row.find('.pt').text(res.item_total);
                sync(res.job_totals);
            }
        }
    ).fail(function(xhr){
        alert(xhr.responseJSON ? xhr.responseJSON.message : 'Update failed.');
    }).always(function(){ $row.removeClass('saving'); });
}

// ══════════════════════════════════════════════════════════════════
// REMOVE PART
// ══════════════════════════════════════════════════════════════════
$(document).on('click','.rp', function(){
    if (!confirm('Remove this part? Stock will be restored.')) return;
    var itemId = $(this).data('item');
    var $row   = $(this).closest('tr');
    sp(true);
    $.post('/repair/service/'+JOB+'/remove-part/'+itemId, { _method:'DELETE' }, function(res){
        if (res.success){
            $row.remove();
            renum('#parts-tbody');
            sync(res.job_totals);
            if (!$('#parts-tbody tr').length)
                $('#parts-tbody').append('<tr id="epr"><td colspan="6" class="text-center text-muted py-4"><i class="fa fa-search mr-1"></i>Search a product above to add parts</td></tr>');
            toast('Part removed.');
        }
    }).always(function(){ sp(false); });
});

// ══════════════════════════════════════════════════════════════════
// SAVE CHARGES
// ══════════════════════════════════════════════════════════════════
$('#save-ch').on('click', function(){
    sp(true);
    $.post('/repair/service/'+JOB+'/update-charges',
        { service_charge:$('#sc').val(), discount:$('#dc').val(), tax:$('#tx').val() },
        function(res){ if(res.success){ sync(res.job_totals); toast('Charges saved!'); } }
    ).always(function(){ sp(false); });
});

// ══════════════════════════════════════════════════════════════════
// ADD PAYMENT
// ══════════════════════════════════════════════════════════════════
$('#add-pay').on('click', function(){
    var amt  = parseFloat($('#pa').val()) || 0;
    var meth = $('#pm').val();
    var acct = $('#pact').val();
    var ref  = $('#pr').val();
    $('#pay-err').text('');

    if (amt <= 0)  { $('#pay-err').text('Enter a valid amount.'); return; }
    if (!acct)     { $('#pay-err').text('Select an account.'); return; }

    var due = parseFloat($('#s-due').text().replace(/,/g,'')) || 0;
    if (amt > due + 0.001){ $('#pay-err').text('Amount exceeds due ('+$('#s-due').text()+').'); return; }

    sp(true);
    $.post('/repair/service/'+JOB+'/add-payment',
        { amount:amt, paying_method:meth, account_id:acct, payment_reference:ref },
        function(res){
            if (res.success){
                addPayRow(res.payment);
                sync(res.job_totals);
                $('#pa').val(''); $('#pr').val('');
                toast('Payment collected!');
            }
        }
    ).fail(function(xhr){ $('#pay-err').text(xhr.responseJSON ? xhr.responseJSON.message : 'Error.'); })
     .always(function(){ sp(false); });
});

function addPayRow(p){
    $('#epr-pay').remove(); PYC++;
    $('#pay-tbody').append(
        '<tr data-pid="'+p.id+'">'
        +'<td>'+PYC+'</td><td>'+p.date+'</td>'
        +'<td class="font-weight-bold" style="color:#28a745;">'+p.amount+'</td>'
        +'<td><span class="badge badge-secondary">'+p.method+'</span></td>'
        +'<td>'+(p.reference||'—')+'</td><td>'+(p.note||'—')+'</td>'
        +'<td><button type="button" class="btn btn-sm btn-danger rpy" data-payment="'+p.id+'"><i class="fa fa-times"></i></button></td>'
        +'</tr>'
    );
}

$(document).on('click','.rpy', function(){
    if (!confirm('Delete this payment?')) return;
    var pid  = $(this).data('payment');
    var $row = $(this).closest('tr');
    sp(true);
    $.post('/repair/service/'+JOB+'/delete-payment/'+pid, { _method:'DELETE' }, function(res){
        if (res.success){
            $row.remove();
            renum('#pay-tbody');
            sync(res.job_totals);
            if (!$('#pay-tbody tr').length)
                $('#pay-tbody').append('<tr id="epr-pay"><td colspan="7" class="text-center text-muted py-4">No payments yet.</td></tr>');
            toast('Payment removed.');
        }
    }).always(function(){ sp(false); });
});

// ══════════════════════════════════════════════════════════════════
// sync — all numbers at once (screen + invoice area)
// ══════════════════════════════════════════════════════════════════
function sync(t){
    // screen sidebar
    $('#ft-parts').text(t.parts_total);
    $('#ft-paid').text(t.paid_amount);
    $('#s-parts').text(t.parts_total);
    $('#s-sc').text(t.service_charge);
    $('#s-dc').text('− '+t.discount);
    $('#s-tx').text(t.tax);
    $('#s-gt').text(t.total_amount);
    $('#s-paid').text(t.paid_amount);
    $('#s-due').text(t.due_amount);
    var due = parseFloat(String(t.due_amount).replace(/,/g,'')) || 0;
    if (due < 0.001){
        $('#due-r').addClass('ok');
        $('#s-badge').removeClass('due').addClass('clear').text('✓ Fully Paid');
    } else {
        $('#due-r').removeClass('ok');
        $('#s-badge').removeClass('clear').addClass('due').text('⚠ Amount Due');
    }
    // invoice area
    $('#inv-s-parts').text(t.parts_total);
    $('#inv-s-sc').text(t.service_charge);
    $('#inv-s-dc').text('− '+t.discount);
    $('#inv-s-tx').text(t.tax);
    $('#inv-s-gt').text(t.total_amount);
    $('#inv-s-paid').text(t.paid_amount);
    $('#inv-s-due').text(t.due_amount);
    if (due < 0.001){
        $('#inv-due-row').addClass('ok');
        $('#invoice-print-area .inv-stamp').removeClass('due-stamp').find('.inv-stamp-text').text('PAID');
    } else {
        $('#inv-due-row').removeClass('ok');
        $('#invoice-print-area .inv-stamp').addClass('due-stamp').find('.inv-stamp-text').text('DUE');
    }
}

// ══════════════════════════════════════════════════════════════════
// HELPERS
// ══════════════════════════════════════════════════════════════════
function sp(on){ on ? $('#sp').addClass('on') : $('#sp').removeClass('on'); }
function renum(sel){ $(sel+' tr').each(function(i){ $(this).find('td.rn, td:first-child').first().text(i+1); }); }
function toast(m){ if(typeof toastr!=='undefined') toastr.success(m); }

// sidebar active
$("ul#repair").siblings('a').attr('aria-expanded','true');
$("ul#repair").addClass("show");
$("ul#repair #service-list-menu").addClass("active");

// ══════════════════════════════════════════════════════════════════
// PRINT INVOICE — builds table from DOM then window.print()
// ══════════════════════════════════════════════════════════════════
function openPrintInvoice(){
    // Rebuild items table in invoice area from current DOM
    var $tb = $('#inv-items-tbody').empty();
    var hasRows = false;
    $('#parts-tbody tr').each(function(){
        var $c = $(this).find('td');
        if ($c.length < 5) return;
        hasRows = true;
        var num   = $c.eq(0).text().trim();
        var pname = $c.eq(1).text().trim();
        var qty   = $c.eq(2).find('.qi').val() || $c.eq(2).text().trim();
        var price = $c.eq(3).find('.pi').val() || $c.eq(3).text().trim();
        var total = $c.eq(4).text().trim();
        $tb.append(
            '<tr>'
            +'<td>'+num+'</td>'
            +'<td>'+pname+'</td>'
            +'<td class="num">'+parseFloat(qty).toFixed(DEC)+'</td>'
            +'<td class="num">'+parseFloat(price).toFixed(DEC)+'</td>'
            +'<td class="num" style="font-weight:700;">'+total+'</td>'
            +'</tr>'
        );
    });
    if (!hasRows){
        $tb.append('<tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:20px;">No parts added yet.</td></tr>');
    }

    // show the invoice area, print, then hide
    $('#invoice-print-area').show();
    setTimeout(function(){
        window.print();
        setTimeout(function(){ $('#invoice-print-area').hide(); }, 500);
    }, 200);
}
</script>
@endpush
