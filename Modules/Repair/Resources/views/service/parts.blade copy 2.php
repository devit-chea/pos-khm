@extends('backend.layout.main')

@push('css')
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
/* ═══════════════════════════════════════════════════════════════
   DESIGN TOKENS — Corporate Professional / Clean Industrial
═══════════════════════════════════════════════════════════════ */
:root {
    --ink:       #0f1923;
    --ink-2:     #2c3a47;
    --ink-3:     #5a6a77;
    --ink-4:     #8c9baa;
    --line:      #e4e9ee;
    --line-2:    #f0f4f7;
    --surface:   #ffffff;
    --surface-2: #f7f9fb;
    --surface-3: #eef2f6;
    --accent:    #1a56db;
    --accent-2:  #1341b5;
    --accent-lt: #eff4ff;
    --green:     #0d9f6e;
    --green-lt:  #ecfdf5;
    --red:       #e02424;
    --red-lt:    #fdf2f2;
    --amber:     #d97706;
    --amber-lt:  #fffbeb;
    --mono:      'DM Mono', monospace;
    --sans:      'DM Sans', sans-serif;
    --radius:    6px;
    --radius-lg: 10px;
    --shadow:    0 1px 3px rgba(15,25,35,.07), 0 4px 12px rgba(15,25,35,.05);
    --shadow-md: 0 2px 8px rgba(15,25,35,.10), 0 8px 24px rgba(15,25,35,.07);
}

*, *::before, *::after { box-sizing: border-box; }

body { font-family: var(--sans); color: var(--ink); background: var(--surface-2); }

/* ── Page layout ───────────────────────────────────────── */
.pb-wrap { max-width: 1380px; margin: 0 auto; padding: 0 20px 40px; }

/* ── Topbar ────────────────────────────────────────────── */
.pb-topbar {
    background: var(--surface);
    border-bottom: 1px solid var(--line);
    padding: 14px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 1px 0 var(--line), var(--shadow);
}
.pb-topbar-left { display: flex; align-items: center; gap: 14px; }
.pb-breadcrumb { font-size: .78rem; color: var(--ink-4); letter-spacing: .02em; }
.pb-breadcrumb a { color: var(--ink-4); text-decoration: none; }
.pb-breadcrumb a:hover { color: var(--accent); }
.pb-breadcrumb span { margin: 0 5px; }
.pb-job-id {
    font-family: var(--mono);
    font-size: .8rem;
    background: var(--accent-lt);
    color: var(--accent);
    border: 1px solid #c3d5fa;
    padding: 3px 10px;
    border-radius: 4px;
    letter-spacing: .03em;
}
.pb-topbar-actions { display: flex; gap: 8px; align-items: center; }

/* ── Buttons ───────────────────────────────────────────── */
.btn-pb-primary {
    background: var(--accent);
    color: #fff;
    border: none;
    padding: 7px 16px;
    border-radius: var(--radius);
    font-size: .82rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background .15s, transform .1s;
    font-family: var(--sans);
}
.btn-pb-primary:hover { background: var(--accent-2); color: #fff; transform: translateY(-1px); }
.btn-pb-ghost {
    background: var(--surface);
    color: var(--ink-2);
    border: 1px solid var(--line);
    padding: 7px 14px;
    border-radius: var(--radius);
    font-size: .82rem;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: border-color .15s, background .15s;
    font-family: var(--sans);
}
.btn-pb-ghost:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-lt); }
.btn-pb-danger {
    background: var(--red-lt);
    color: var(--red);
    border: 1px solid #fbd5d5;
    padding: 4px 10px;
    border-radius: var(--radius);
    font-size: .78rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: .15s;
    font-family: var(--sans);
}
.btn-pb-danger:hover { background: var(--red); color: #fff; border-color: var(--red); }

/* ── Section card ──────────────────────────────────────── */
.pb-card {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow);
    overflow: hidden;
    margin-bottom: 20px;
}
.pb-card-header {
    padding: 12px 20px;
    border-bottom: 1px solid var(--line);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--surface);
}
.pb-card-title {
    font-size: .82rem;
    font-weight: 700;
    letter-spacing: .07em;
    text-transform: uppercase;
    color: var(--ink-3);
    display: flex;
    align-items: center;
    gap: 8px;
}
.pb-card-title .dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    background: var(--accent);
    flex-shrink: 0;
}
.pb-card-title .dot.green { background: var(--green); }
.pb-card-title .dot.amber { background: var(--amber); }
.pb-card-body { padding: 18px 20px; }

/* ── Search bar ────────────────────────────────────────── */
.pb-search-wrap {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    background: var(--surface-2);
    border-bottom: 1px solid var(--line);
}
.pb-search-input-wrap {
    position: relative;
    flex: 1;
    max-width: 420px;
}
.pb-search-input-wrap i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--ink-4);
    font-size: .85rem;
}
#product-search {
    width: 100%;
    height: 36px;
    border: 1.5px solid var(--line);
    border-radius: var(--radius);
    padding: 0 12px 0 34px;
    font-size: .84rem;
    font-family: var(--sans);
    color: var(--ink);
    background: var(--surface);
    transition: border-color .15s, box-shadow .15s;
    outline: none;
}
#product-search:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(26,86,219,.12);
}
.pb-search-hint { font-size: .76rem; color: var(--ink-4); }

/* autocomplete */
.ui-autocomplete {
    z-index: 9999;
    max-height: 260px;
    overflow-y: auto;
    border: 1px solid var(--line);
    border-radius: var(--radius);
    box-shadow: var(--shadow-md);
    background: var(--surface);
    font-family: var(--sans);
    font-size: .84rem;
    padding: 4px 0;
}
.ui-menu-item .ui-menu-item-wrapper { padding: 8px 14px; color: var(--ink-2); }
.ui-menu-item .ui-menu-item-wrapper.ui-state-active {
    background: var(--accent-lt);
    color: var(--accent);
    border: none;
}

/* ── Parts table ───────────────────────────────────────── */
.pb-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .83rem;
}
.pb-table thead th {
    background: var(--surface-2);
    color: var(--ink-3);
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    padding: 10px 14px;
    border-bottom: 2px solid var(--line);
    white-space: nowrap;
}
.pb-table thead th:first-child { padding-left: 20px; }
.pb-table thead th:last-child { padding-right: 20px; }
.pb-table tbody td {
    padding: 11px 14px;
    border-bottom: 1px solid var(--line-2);
    vertical-align: middle;
    color: var(--ink-2);
}
.pb-table tbody td:first-child { padding-left: 20px; }
.pb-table tbody td:last-child { padding-right: 20px; }
.pb-table tbody tr:last-child td { border-bottom: none; }
.pb-table tbody tr:hover td { background: #fafbfd; }
.pb-table tfoot td {
    padding: 11px 14px;
    font-weight: 700;
    border-top: 2px solid var(--line);
    background: var(--surface-2);
    color: var(--accent);
    font-size: .84rem;
}
.pb-table tfoot td:first-child { padding-left: 20px; }
.pb-table tfoot td:last-child { padding-right: 20px; }

.row-num {
    font-family: var(--mono);
    font-size: .72rem;
    color: var(--ink-4);
    width: 32px;
}
.prod-name { font-weight: 600; color: var(--ink); }
.prod-code {
    font-family: var(--mono);
    font-size: .72rem;
    color: var(--ink-4);
    display: inline-block;
    background: var(--surface-3);
    padding: 1px 6px;
    border-radius: 3px;
    margin-left: 5px;
}
.row-total {
    font-family: var(--mono);
    font-weight: 700;
    color: var(--ink);
    font-size: .84rem;
}

/* qty stepper */
.qty-wrap { display: flex; align-items: center; gap: 4px; }
.qty-btn {
    width: 24px; height: 24px;
    border: 1px solid var(--line);
    border-radius: 4px;
    background: var(--surface);
    color: var(--ink-3);
    font-size: .9rem;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: .13s;
    flex-shrink: 0;
}
.qty-btn:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-lt); }
.qty-input {
    width: 56px; height: 28px;
    border: 1px solid var(--line);
    border-radius: 4px;
    text-align: center;
    font-size: .82rem;
    font-family: var(--mono);
    color: var(--ink);
    background: var(--surface);
    outline: none;
    transition: border-color .13s;
}
.qty-input:focus { border-color: var(--accent); box-shadow: 0 0 0 2px rgba(26,86,219,.1); }

/* price input */
.price-input {
    width: 90px; height: 28px;
    border: 1px solid var(--line);
    border-radius: 4px;
    text-align: right;
    font-size: .82rem;
    font-family: var(--mono);
    padding: 0 8px;
    color: var(--ink);
    background: var(--surface);
    outline: none;
    transition: border-color .13s;
}
.price-input:focus { border-color: var(--accent); box-shadow: 0 0 0 2px rgba(26,86,219,.1); }

/* empty row */
.pb-empty {
    text-align: center;
    padding: 40px 20px;
    color: var(--ink-4);
    font-size: .84rem;
}
.pb-empty i { font-size: 1.4rem; display: block; margin-bottom: 8px; opacity: .4; }

/* saving row */
tr.saving { opacity: .5; pointer-events: none; }

/* ── Charges section ───────────────────────────────────── */
.charge-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 16px;
}
.charge-field label {
    display: block;
    font-size: .73rem;
    font-weight: 700;
    letter-spacing: .07em;
    text-transform: uppercase;
    color: var(--ink-3);
    margin-bottom: 5px;
}
.charge-inp {
    width: 100%;
    height: 36px;
    border: 1.5px solid var(--line);
    border-radius: var(--radius);
    padding: 0 10px;
    font-size: .84rem;
    font-family: var(--mono);
    color: var(--ink);
    background: var(--surface);
    outline: none;
    transition: border-color .13s;
}
.charge-inp:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(26,86,219,.1); }

/* ── Payment form ──────────────────────────────────────── */
.pay-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1.4fr 1.2fr;
    gap: 12px;
    margin-bottom: 14px;
    align-items: end;
}
.pay-field label {
    display: block;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .07em;
    text-transform: uppercase;
    color: var(--ink-3);
    margin-bottom: 5px;
}
.pay-inp, .pay-sel {
    width: 100%;
    height: 34px;
    border: 1.5px solid var(--line);
    border-radius: var(--radius);
    padding: 0 10px;
    font-size: .83rem;
    font-family: var(--sans);
    color: var(--ink);
    background: var(--surface);
    outline: none;
    transition: border-color .13s;
    -webkit-appearance: none;
}
.pay-inp:focus, .pay-sel:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(26,86,219,.1); }

/* ── Summary panel ─────────────────────────────────────── */
.sum-panel {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow);
    overflow: hidden;
    margin-bottom: 20px;
}
.sum-panel-head {
    background: var(--ink);
    color: #fff;
    padding: 13px 18px;
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    display: flex;
    align-items: center;
    gap: 8px;
}
.sum-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 18px;
    border-bottom: 1px solid var(--line-2);
    font-size: .84rem;
}
.sum-row:last-of-type { border-bottom: none; }
.sum-label { color: var(--ink-3); font-size: .81rem; }
.sum-value { font-family: var(--mono); font-weight: 600; color: var(--ink); }
.sum-row.grand {
    background: var(--surface-2);
    border-top: 2px solid var(--line);
    border-bottom: 2px solid var(--line);
}
.sum-row.grand .sum-label { font-weight: 700; color: var(--ink); font-size: .86rem; }
.sum-row.grand .sum-value { font-size: 1rem; color: var(--ink); }
.sum-row.paid-r .sum-value { color: var(--green); }
.sum-row.due-r .sum-value { color: var(--red); font-size: .92rem; }
.sum-row.due-r.ok .sum-value { color: var(--green); }

.sum-status {
    padding: 14px 18px;
    border-top: 1px solid var(--line);
    text-align: center;
}
.status-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 18px;
    border-radius: 40px;
    font-size: .8rem;
    font-weight: 700;
    letter-spacing: .04em;
}
.status-chip.due {
    background: var(--red-lt);
    color: var(--red);
    border: 1.5px solid #fbd5d5;
}
.status-chip.clear {
    background: var(--green-lt);
    color: var(--green);
    border: 1.5px solid #a7f3d0;
}

/* ── Job info panel ────────────────────────────────────── */
.job-info-table { width: 100%; border-collapse: collapse; }
.job-info-table tr + tr td { border-top: 1px solid var(--line-2); }
.job-info-table td {
    padding: 8px 16px;
    font-size: .81rem;
}
.job-info-table td:first-child {
    color: var(--ink-4);
    font-size: .73rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    width: 100px;
    white-space: nowrap;
}
.job-info-table td:last-child { color: var(--ink-2); font-weight: 500; }

/* ── Spinner overlay ───────────────────────────────────── */
.sp-ov {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15,25,35,.35);
    z-index: 9999;
    justify-content: center;
    align-items: center;
    backdrop-filter: blur(2px);
}
.sp-ov.on { display: flex; }
.sp-ring {
    width: 44px; height: 44px;
    border: 3px solid rgba(255,255,255,.2);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin .7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Payments table badge ──────────────────────────────── */
.method-badge {
    font-family: var(--mono);
    font-size: .72rem;
    font-weight: 500;
    padding: 2px 8px;
    border-radius: 3px;
    background: var(--surface-3);
    color: var(--ink-3);
    border: 1px solid var(--line);
    display: inline-block;
}

/* ── Divider line ──────────────────────────────────────── */
.pay-divider {
    border: none;
    border-top: 1px solid var(--line);
    margin: 18px 0;
}

/* Alert */
.pb-alert {
    border-radius: var(--radius);
    border: 1px solid;
    padding: 10px 14px;
    font-size: .82rem;
    margin-bottom: 16px;
}
.pb-alert.success { background: var(--green-lt); border-color: #a7f3d0; color: var(--green); }

/* Bootstrap select override */
.bootstrap-select > .dropdown-toggle {
    height: 34px;
    font-size: .83rem;
    border: 1.5px solid var(--line);
    border-radius: var(--radius);
}
.bootstrap-select > .dropdown-toggle:focus {
    border-color: var(--accent) !important;
    box-shadow: 0 0 0 3px rgba(26,86,219,.1) !important;
    outline: none !important;
}

/* ── Print modal ───────────────────────────────────────── */
.print-modal .modal-content {
    border: none;
    border-radius: var(--radius-lg);
    box-shadow: 0 20px 60px rgba(15,25,35,.25);
    font-family: var(--sans);
}
.print-modal .modal-header {
    border-bottom: 1px solid var(--line);
    padding: 14px 20px;
}

@media (max-width: 768px) {
    .charge-grid { grid-template-columns: 1fr 1fr; }
    .pay-grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 520px) {
    .charge-grid, .pay-grid { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')

@if(session('message'))
<div class="pb-alert success">
    <i class="fa fa-check-circle mr-1"></i> {{ session('message') }}
</div>
@endif

<div class="sp-ov" id="sp"><div class="sp-ring"></div></div>

{{-- ────────────────────── TOPBAR ────────────────────────── --}}
<div class="pb-topbar">
    <div class="pb-topbar-left">
        <div>
            <div class="pb-breadcrumb">
                <a href="{{ route('repair.service.index') }}">Service Jobs</a>
                <span>›</span>
                <a href="{{ route('repair.service.show', $job->id) }}">{{ $job->reference_no }}</a>
                <span>›</span>
                <strong style="color:var(--ink-2)">Parts &amp; Billing</strong>
            </div>
            <div class="d-flex align-items-center gap-10 mt-1" style="gap:8px;">
                <span class="pb-job-id">{{ $job->reference_no }}</span>
                {!! $job->status_badge !!}
                {!! $job->priority_badge !!}
            </div>
        </div>
    </div>
    <div class="pb-topbar-actions">
        <button onclick="openPrintModal()" class="btn-pb-ghost">
            <i class="dripicons-print"></i> Print Invoice
        </button>
        <a href="{{ route('repair.service.show', $job->id) }}" class="btn-pb-ghost">
            <i class="fa fa-eye"></i> View Job
        </a>
        <a href="{{ route('repair.service.index') }}" class="btn-pb-ghost">
            <i class="fa fa-list"></i> All Jobs
        </a>
    </div>
</div>

<div class="pb-wrap">
<div class="row">

{{-- ═══════════════════════ LEFT COLUMN ═══════════════════════ --}}
<div class="col-md-8">

    {{-- ── PARTS / ITEMS ──────────────────────────────────────── --}}
    <div class="pb-card">
        <div class="pb-card-header">
            <div class="pb-card-title"><span class="dot"></span> Parts &amp; Items Used</div>
        </div>

        {{-- search --}}
        <div class="pb-search-wrap">
            <div class="pb-search-input-wrap">
                <i class="fa fa-search"></i>
                <input type="text" id="product-search"
                    placeholder="Search by product code or name…"
                    autocomplete="off"/>
            </div>
            <span class="pb-search-hint">Select a product to add it instantly</span>
        </div>

        <div class="table-responsive">
        <table class="pb-table" id="parts-table">
            <thead>
                <tr>
                    <th class="row-num" style="width:42px">#</th>
                    <th>Product</th>
                    <th style="width:152px">Quantity</th>
                    <th style="width:118px">Unit Price</th>
                    <th style="width:110px">Total</th>
                    <th style="width:52px"></th>
                </tr>
            </thead>
            <tbody id="parts-tbody">
            @forelse($job->items as $idx => $item)
                <tr data-item-id="{{ $item->id }}"
                    data-product-id="{{ $item->product_id }}"
                    data-stock="{{ optional($item->product)->qty ?? 9999 }}">
                    <td class="row-num rn">{{ $idx + 1 }}</td>
                    <td>
                        <span class="prod-name">{{ optional($item->product)->name }}</span>
                        <span class="prod-code">{{ optional($item->product)->code }}</span>
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
                    <td class="pt row-total">{{ number_format($item->total, config('decimal')) }}</td>
                    <td>
                        <button type="button" class="btn-pb-danger rp" data-item="{{ $item->id }}">
                            <i class="fa fa-times"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr id="epr">
                    <td colspan="6" class="pb-empty">
                        <i class="fa fa-inbox"></i>
                        No parts added yet — search a product above to get started
                    </td>
                </tr>
            @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align:right;font-size:.75rem;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Parts Total</td>
                    <td id="ft-parts" class="row-total" style="color:var(--accent);">
                        {{ number_format($job->items->sum('total'), config('decimal')) }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        </div>
    </div>

    {{-- ── SERVICE CHARGES ────────────────────────────────────── --}}
    <div class="pb-card">
        <div class="pb-card-header">
            <div class="pb-card-title"><span class="dot amber"></span> Service Charges</div>
        </div>
        <div class="pb-card-body">
            <div class="charge-grid">
                <div class="charge-field">
                    <label>Service Charge</label>
                    <input type="number" id="sc" class="charge-inp" value="{{ $job->service_charge }}" step="any" min="0" placeholder="0.00"/>
                </div>
                <div class="charge-field">
                    <label>Discount</label>
                    <input type="number" id="dc" class="charge-inp" value="{{ $job->discount }}" step="any" min="0" placeholder="0.00"/>
                </div>
                <div class="charge-field">
                    <label>Tax</label>
                    <input type="number" id="tx" class="charge-inp" value="{{ $job->tax }}" step="any" min="0" placeholder="0.00"/>
                </div>
            </div>
            <button type="button" id="save-ch" class="btn-pb-primary">
                <i class="dripicons-checkmark"></i> Save Charges
            </button>
            <small style="color:var(--ink-4);margin-left:10px;font-size:.77rem;">Totals update automatically after saving.</small>
        </div>
    </div>

    {{-- ── PAYMENTS ────────────────────────────────────────────── --}}
    <div class="pb-card">
        <div class="pb-card-header">
            <div class="pb-card-title"><span class="dot green"></span> Payments Received</div>
        </div>
        <div class="pb-card-body">

            {{-- New payment form --}}
            <p style="font-size:.73rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--ink-3);margin-bottom:12px;">
                Collect New Payment
            </p>
            <div class="pay-grid">
                <div class="pay-field">
                    <label>Amount *</label>
                    <input type="number" id="pa" class="pay-inp" placeholder="0.00" step="any" min="0.01"/>
                </div>
                <div class="pay-field">
                    <label>Method *</label>
                    <select id="pm" class="pay-sel">
                        <option>Cash</option>
                        <option>Cheque</option>
                        <option>Card</option>
                        <option>bKash</option>
                        <option>Nagad</option>
                        <option>Rocket</option>
                        <option>Bank Transfer</option>
                    </select>
                </div>
                <div class="pay-field">
                    <label>Account *</label>
                    <select id="pact" class="selectpicker pay-sel" data-live-search="true" title="Select account…">
                        @foreach($lims_account_list as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="pay-field">
                    <label>Reference Note</label>
                    <input type="text" id="pr" class="pay-inp" placeholder="TrxID, cheque no…"/>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:12px;">
                <button type="button" id="add-pay" class="btn-pb-primary">
                    <i class="fa fa-check"></i> Collect Payment
                </button>
                <small id="pay-err" style="color:var(--red);font-size:.8rem;"></small>
            </div>

            <hr class="pay-divider">

            {{-- Payments table --}}
            <div class="table-responsive">
            <table class="pb-table" id="payments-table">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Note</th>
                        <th style="width:52px"></th>
                    </tr>
                </thead>
                <tbody id="pay-tbody">
                @forelse($job->payments as $idx => $p)
                    <tr data-pid="{{ $p->id }}">
                        <td class="row-num">{{ $idx + 1 }}</td>
                        <td style="font-size:.8rem;color:var(--ink-3);">
                            {{ $p->payment_at ? $p->payment_at->format(config('date_format')) : '—' }}
                        </td>
                        <td class="row-total" style="color:var(--green);">
                            {{ number_format($p->amount, config('decimal')) }}
                        </td>
                        <td><span class="method-badge">{{ $p->paying_method }}</span></td>
                        <td style="font-size:.8rem;color:var(--ink-3);">{{ $p->payment_reference ?? '—' }}</td>
                        <td style="font-size:.8rem;color:var(--ink-3);">{{ $p->payment_note ?? '—' }}</td>
                        <td>
                            <button type="button" class="btn-pb-danger rpy" data-payment="{{ $p->id }}">
                                <i class="fa fa-times"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr id="epr-pay">
                        <td colspan="7" class="pb-empty">
                            <i class="fa fa-credit-card"></i>
                            No payments recorded yet
                        </td>
                    </tr>
                @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" style="text-align:right;font-size:.75rem;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Total Paid</td>
                        <td id="ft-paid" class="row-total" style="color:var(--green);">
                            {{ number_format($job->payments->sum('amount'), config('decimal')) }}
                        </td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
            </table>
            </div>

        </div>
    </div>

</div>{{-- /col-md-8 --}}

{{-- ═══════════════════════ RIGHT COLUMN ══════════════════════ --}}
<div class="col-md-4">

    {{-- Billing Summary --}}
    <div class="sum-panel">
        <div class="sum-panel-head">
            <i class="fa fa-file-text-o"></i> Billing Summary
        </div>
        <div class="sum-row">
            <span class="sum-label">Parts Total</span>
            <span id="s-parts" class="sum-value">{{ number_format($job->items->sum('total'), config('decimal')) }}</span>
        </div>
        <div class="sum-row">
            <span class="sum-label">Service Charge</span>
            <span id="s-sc" class="sum-value">{{ number_format($job->service_charge, config('decimal')) }}</span>
        </div>
        <div class="sum-row">
            <span class="sum-label">Discount</span>
            <span id="s-dc" class="sum-value" style="color:var(--red);">− {{ number_format($job->discount, config('decimal')) }}</span>
        </div>
        <div class="sum-row">
            <span class="sum-label">Tax</span>
            <span id="s-tx" class="sum-value">{{ number_format($job->tax, config('decimal')) }}</span>
        </div>
        <div class="sum-row grand">
            <span class="sum-label">Grand Total</span>
            <span id="s-gt" class="sum-value">{{ number_format($job->total_amount, config('decimal')) }}</span>
        </div>
        <div class="sum-row paid-r">
            <span class="sum-label">Paid Amount</span>
            <span id="s-paid" class="sum-value">{{ number_format($job->paid_amount, config('decimal')) }}</span>
        </div>
        <div class="sum-row due-r {{ $job->due_amount <= 0 ? 'ok' : '' }}" id="due-r">
            <span class="sum-label">Due Amount</span>
            <span id="s-due" class="sum-value">{{ number_format($job->due_amount, config('decimal')) }}</span>
        </div>
        <div class="sum-status">
            <span id="s-badge" class="status-chip {{ $job->due_amount <= 0 ? 'clear' : 'due' }}">
                @if($job->due_amount <= 0)
                    <i class="fa fa-check-circle"></i> Fully Paid
                @else
                    <i class="fa fa-exclamation-circle"></i> Amount Due
                @endif
            </span>
        </div>
    </div>

    {{-- Job Info --}}
    <div class="sum-panel">
        <div class="sum-panel-head" style="background:var(--ink-2);">
            <i class="fa fa-info-circle"></i> Job Information
        </div>
        <table class="job-info-table">
            <tr>
                <td>Reference</td>
                <td style="font-family:var(--mono);font-size:.8rem;">{{ $job->reference_no }}</td>
            </tr>
            <tr>
                <td>Type</td>
                <td>{{ ucfirst($job->service_type) }}</td>
            </tr>
            <tr>
                <td>Title</td>
                <td>{{ $job->title }}</td>
            </tr>
            <tr>
                <td>Customer</td>
                <td style="font-weight:600;">{{ optional($job->customer)->name }}</td>
            </tr>
            <tr>
                <td>Warehouse</td>
                <td>{{ optional($job->warehouse)->name }}</td>
            </tr>
            <tr>
                <td>Status</td>
                <td>{!! $job->status_badge !!}</td>
            </tr>
            @if($job->expected_delivery_date)
            <tr>
                <td>Expected</td>
                <td>{{ $job->expected_delivery_date->format(config('date_format')) }}</td>
            </tr>
            @endif
        </table>
    </div>

</div>{{-- /col-md-4 --}}

</div>{{-- /row --}}
</div>{{-- /pb-wrap --}}


{{-- ═══════════════════════ PRINT MODAL ════════════════════════ --}}
<div id="print-modal" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left print-modal">
    <div role="document" class="modal-dialog modal-lg">
        <div class="modal-content">

            {{-- Modal header --}}
            <div class="modal-header d-print-none" style="background:var(--surface-2);border-radius:var(--radius-lg) var(--radius-lg) 0 0;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <button id="do-print-btn" type="button"
                        style="background:var(--accent);color:#fff;border:none;padding:7px 16px;border-radius:6px;font-size:.82rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;font-family:var(--sans);">
                        <i class="dripicons-print"></i> Print
                    </button>
                    <span style="font-size:.78rem;color:var(--ink-4);">Preview below</span>
                </div>
                <button type="button" data-dismiss="modal"
                    style="background:none;border:none;cursor:pointer;color:var(--ink-4);font-size:1.3rem;line-height:1;">
                    <i class="dripicons-cross"></i>
                </button>
            </div>

            {{-- Invoice preview --}}
            <div class="modal-body" style="padding:0;">
                <div id="invoice-preview" style="padding:32px 40px;font-family:var(--sans);color:var(--ink);">

                    {{-- Invoice header --}}
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:28px;padding-bottom:20px;border-bottom:2px solid var(--ink);">
                        <div>
                            <div style="font-size:1.5rem;font-weight:800;letter-spacing:-.02em;color:var(--ink);">
                                {{ $general_setting->site_title }}
                            </div>
                            <div style="font-size:.78rem;color:var(--ink-4);margin-top:2px;letter-spacing:.02em;">
                                Service &amp; Repair Management
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:1.2rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase;color:var(--ink);">Invoice</div>
                            <div style="font-family:var(--mono);font-size:.82rem;color:var(--accent);margin-top:3px;">{{ $job->reference_no }}</div>
                            <div style="font-size:.76rem;color:var(--ink-4);margin-top:3px;">
                                Date: {{ date(config('date_format'), strtotime($job->created_at)) }}
                            </div>
                        </div>
                    </div>

                    {{-- Bill to / job info --}}
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">
                        <div>
                            <div style="font-size:.68rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--ink-4);margin-bottom:6px;">Bill To</div>
                            <div style="font-weight:700;font-size:.9rem;">{{ optional($job->customer)->name }}</div>
                            <div style="font-size:.8rem;color:var(--ink-3);">{{ optional($job->customer)->phone ?? '' }}</div>
                            <div style="font-size:.8rem;color:var(--ink-3);">{{ optional($job->customer)->email ?? '' }}</div>
                        </div>
                        <div>
                            <div style="font-size:.68rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--ink-4);margin-bottom:6px;">Job Details</div>
                            <table style="font-size:.8rem;border-collapse:collapse;width:100%;">
                                <tr><td style="color:var(--ink-4);padding:2px 0;width:80px;">Type</td><td style="font-weight:500;padding:2px 0;">{{ ucfirst($job->service_type) }}</td></tr>
                                <tr><td style="color:var(--ink-4);padding:2px 0;">Status</td><td style="font-weight:500;padding:2px 0;">{{ ucfirst(str_replace('_', ' ', $job->status)) }}</td></tr>
                                <tr><td style="color:var(--ink-4);padding:2px 0;">Warehouse</td><td style="font-weight:500;padding:2px 0;">{{ optional($job->warehouse)->name }}</td></tr>
                                @if($job->expected_delivery_date)
                                <tr><td style="color:var(--ink-4);padding:2px 0;">Expected</td><td style="font-weight:500;padding:2px 0;">{{ $job->expected_delivery_date->format(config('date_format')) }}</td></tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    {{-- Parts table (dynamically filled) --}}
                    <table class="print-tbl" style="width:100%;border-collapse:collapse;margin-bottom:24px;font-size:.82rem;">
                        <thead>
                            <tr style="background:var(--ink);color:#fff;">
                                <th style="padding:9px 12px;text-align:left;font-weight:700;font-size:.72rem;letter-spacing:.06em;text-transform:uppercase;width:36px;">#</th>
                                <th style="padding:9px 12px;text-align:left;font-weight:700;font-size:.72rem;letter-spacing:.06em;text-transform:uppercase;">Product</th>
                                <th style="padding:9px 12px;text-align:center;font-weight:700;font-size:.72rem;letter-spacing:.06em;text-transform:uppercase;width:80px;">Qty</th>
                                <th style="padding:9px 12px;text-align:right;font-weight:700;font-size:.72rem;letter-spacing:.06em;text-transform:uppercase;width:110px;">Unit Price</th>
                                <th style="padding:9px 12px;text-align:right;font-weight:700;font-size:.72rem;letter-spacing:.06em;text-transform:uppercase;width:110px;">Total</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>

                    {{-- Summary (dynamically filled) --}}
                    <div id="pm-footer" style="display:flex;justify-content:flex-end;">
                        <table style="width:240px;font-size:.83rem;border-collapse:collapse;">
                        </table>
                    </div>

                    {{-- Title / notes --}}
                    <div id="pm-title-note" style="margin-top:20px;padding-top:16px;border-top:1px solid var(--line);font-size:.8rem;color:var(--ink-3);">
                        <strong>Service:</strong> {{ addslashes($job->title) }}
                    </div>
                    <div style="margin-top:24px;padding-top:16px;border-top:1px solid var(--line);font-size:.73rem;color:var(--ink-4);text-align:center;letter-spacing:.03em;">
                        Thank you for choosing {{ $general_setting->site_title }} &nbsp;·&nbsp;
                        Created by: {{ optional($job->createdBy)->name ?? 'System' }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

var JOB     = {{ $job->id }};
var PC      = {{ $job->items->count() }};
var PYC     = {{ $job->payments->count() }};
var DEC     = {{ config('decimal') }};
var QTY_TMR = null;

// ══════════════════════════════════════════════════════════
// PRODUCT AUTOCOMPLETE
// ══════════════════════════════════════════════════════════
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
        $.get('{{ route("manufacturing.products.search") }}', { data: ui.item.value }, function(res){
            if (!res || !res[0]){ sp(false); $('#product-search').prop('disabled', false); return; }
            var d = res[0];
            $.post('/repair/service/' + JOB + '/add-part',
                { product_id: d[8], quantity: 1, unit_price: d[10] },
                function(r){
                    if (r.success){
                        addRow(r.item, d[8], d[17] ?? 9999);
                        sync(r.job_totals);
                        toast('Part added successfully!');
                    }
                }
            ).fail(function(xhr){
                alert(xhr.responseJSON ? xhr.responseJSON.message : 'Error adding part.');
            }).always(function(){ sp(false); $('#product-search').prop('disabled', false).focus(); });
        });
    }
});

function addRow(item, productId, stock){
    $('#epr').remove(); PC++;
    var uPrice = parseFloat(String(item.unit_price).replace(/,/g,'')) || 0;
    var nameParts = item.product_name.split('[');
    var name = nameParts[0].trim();
    var code = nameParts[1] ? nameParts[1].replace(']','').trim() : '';
    $('#parts-tbody').append(
        '<tr data-item-id="'+item.id+'" data-product-id="'+productId+'" data-stock="'+stock+'">'
        +'<td class="row-num rn">'+PC+'</td>'
        +'<td><span class="prod-name">'+name+'</span><span class="prod-code">'+code+'</span></td>'
        +'<td><div class="qty-wrap">'
        +'<button type="button" class="qty-btn qm">−</button>'
        +'<input type="number" class="qty-input qi" value="'+item.quantity+'" min="0.01" step="any"'
        +' data-item="'+item.id+'" data-price="'+uPrice+'"/>'
        +'<button type="button" class="qty-btn qp">+</button>'
        +'</div></td>'
        +'<td><input type="number" class="price-input pi" value="'+uPrice+'" data-item="'+item.id+'" step="any" min="0"/></td>'
        +'<td class="pt row-total">'+item.total+'</td>'
        +'<td><button type="button" class="btn-pb-danger rp" data-item="'+item.id+'"><i class="fa fa-times"></i></button></td>'
        +'</tr>'
    );
}

// ══════════════════════════════════════════════════════════
// QTY STEPPER — minus
// ══════════════════════════════════════════════════════════
$(document).on('click', '.qm', function(){
    var $i = $(this).siblings('.qi');
    var v  = parseFloat($i.val()) || 1;
    var nv = Math.max(0.01, parseFloat((v - 1).toFixed(4)));
    $i.val(nv).trigger('qty-change');
});

// ══════════════════════════════════════════════════════════
// QTY STEPPER — plus
// ══════════════════════════════════════════════════════════
$(document).on('click', '.qp', function(){
    var $i    = $(this).siblings('.qi');
    var v     = parseFloat($i.val()) || 0;
    var stock = parseFloat($i.closest('tr').data('stock')) || 9999;
    var nv    = parseFloat((v + 1).toFixed(4));
    if (nv > stock){ alert('Cannot exceed available stock (' + stock + ')'); return; }
    $i.val(nv).trigger('qty-change');
});

// ══════════════════════════════════════════════════════════
// QTY manual input
// ══════════════════════════════════════════════════════════
$(document).on('input', '.qi', function(){
    $(this).trigger('qty-change');
});

$(document).on('qty-change', '.qi', function(){
    var $i     = $(this);
    var itemId = $i.data('item');
    var price  = parseFloat($i.data('price')) || 0;
    var qty    = parseFloat($i.val()) || 0;
    var stock  = parseFloat($i.closest('tr').data('stock')) || 9999;
    var $row   = $i.closest('tr');
    if (qty < 0.01){ qty = 0.01; $i.val(qty); }
    if (qty > stock){ qty = stock; $i.val(qty); }
    $row.find('.pt').text((qty * price).toFixed(DEC));
    clearTimeout(QTY_TMR);
    QTY_TMR = setTimeout(function(){
        doUpdatePart(itemId, qty, price, $row);
    }, 700);
});

// ══════════════════════════════════════════════════════════
// PRICE EDIT
// ══════════════════════════════════════════════════════════
$(document).on('change blur', '.pi', function(){
    var $i     = $(this);
    var itemId = $i.data('item');
    var price  = parseFloat($i.val()) || 0;
    var $row   = $i.closest('tr');
    var qty    = parseFloat($row.find('.qi').val()) || 1;
    $row.find('.qi').data('price', price);
    $row.find('.pt').text((qty * price).toFixed(DEC));
    doUpdatePart(itemId, qty, price, $row);
});

function doUpdatePart(itemId, qty, price, $row){
    $row.addClass('saving');
    $.post('/repair/service/' + JOB + '/update-part/' + itemId,
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

// ══════════════════════════════════════════════════════════
// REMOVE PART
// ══════════════════════════════════════════════════════════
$(document).on('click', '.rp', function(){
    if (!confirm('Remove this part? Stock will be restored.')) return;
    var itemId = $(this).data('item');
    var $row   = $(this).closest('tr');
    sp(true);
    $.post('/repair/service/' + JOB + '/remove-part/' + itemId, { _method: 'DELETE' }, function(res){
        if (res.success){
            $row.remove();
            renum('#parts-tbody');
            sync(res.job_totals);
            if (!$('#parts-tbody tr').length)
                $('#parts-tbody').append('<tr id="epr"><td colspan="6" class="pb-empty"><i class="fa fa-inbox"></i>No parts added yet — search a product above</td></tr>');
            toast('Part removed.');
        }
    }).always(function(){ sp(false); });
});

// ══════════════════════════════════════════════════════════
// SAVE CHARGES
// ══════════════════════════════════════════════════════════
$('#save-ch').on('click', function(){
    sp(true);
    $.post('/repair/service/' + JOB + '/update-charges',
        { service_charge: $('#sc').val(), discount: $('#dc').val(), tax: $('#tx').val() },
        function(res){ if(res.success){ sync(res.job_totals); toast('Charges saved!'); } }
    ).always(function(){ sp(false); });
});

// ══════════════════════════════════════════════════════════
// ADD PAYMENT
// ══════════════════════════════════════════════════════════
$('#add-pay').on('click', function(){
    var amt  = parseFloat($('#pa').val()) || 0;
    var meth = $('#pm').val();
    var acct = $('#pact').val();
    var ref  = $('#pr').val();
    $('#pay-err').text('');
    if (amt <= 0)  { $('#pay-err').text('Enter a valid amount.'); return; }
    if (!acct)     { $('#pay-err').text('Select an account.'); return; }
    var due = parseFloat($('#s-due').text().replace(/,/g,'')) || 0;
    if (amt > due + 0.001){ $('#pay-err').text('Amount exceeds due (' + $('#s-due').text() + ').'); return; }
    sp(true);
    $.post('/repair/service/' + JOB + '/add-payment',
        { amount: amt, paying_method: meth, account_id: acct, payment_reference: ref },
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
        +'<td class="row-num">'+PYC+'</td>'
        +'<td style="font-size:.8rem;color:var(--ink-3);">'+p.date+'</td>'
        +'<td class="row-total" style="color:var(--green);">'+p.amount+'</td>'
        +'<td><span class="method-badge">'+p.method+'</span></td>'
        +'<td style="font-size:.8rem;color:var(--ink-3);">'+(p.reference||'—')+'</td>'
        +'<td style="font-size:.8rem;color:var(--ink-3);">'+(p.note||'—')+'</td>'
        +'<td><button type="button" class="btn-pb-danger rpy" data-payment="'+p.id+'"><i class="fa fa-times"></i></button></td>'
        +'</tr>'
    );
}

$(document).on('click', '.rpy', function(){
    if (!confirm('Delete this payment?')) return;
    var pid  = $(this).data('payment');
    var $row = $(this).closest('tr');
    sp(true);
    $.post('/repair/service/' + JOB + '/delete-payment/' + pid, { _method: 'DELETE' }, function(res){
        if (res.success){
            $row.remove();
            renum('#pay-tbody');
            sync(res.job_totals);
            if (!$('#pay-tbody tr').length)
                $('#pay-tbody').append('<tr id="epr-pay"><td colspan="7" class="pb-empty"><i class="fa fa-credit-card"></i>No payments recorded yet</td></tr>');
            toast('Payment removed.');
        }
    }).always(function(){ sp(false); });
});

// ══════════════════════════════════════════════════════════
// SYNC — update all totals
// ══════════════════════════════════════════════════════════
function sync(t){
    $('#ft-parts').text(t.parts_total);
    $('#ft-paid').text(t.paid_amount);
    $('#s-parts').text(t.parts_total);
    $('#s-sc').text(t.service_charge);
    $('#s-dc').text('− ' + t.discount);
    $('#s-tx').text(t.tax);
    $('#s-gt').text(t.total_amount);
    $('#s-paid').text(t.paid_amount);
    $('#s-due').text(t.due_amount);
    var due = parseFloat(String(t.due_amount).replace(/,/g,'')) || 0;
    if (due < 0.001){
        $('#due-r').addClass('ok');
        $('#s-badge').removeClass('due').addClass('clear')
            .html('<i class="fa fa-check-circle"></i> Fully Paid');
    } else {
        $('#due-r').removeClass('ok');
        $('#s-badge').removeClass('clear').addClass('due')
            .html('<i class="fa fa-exclamation-circle"></i> Amount Due');
    }
}

// ══════════════════════════════════════════════════════════
// HELPERS
// ══════════════════════════════════════════════════════════
function sp(on){ on ? $('#sp').addClass('on') : $('#sp').removeClass('on'); }

function renum(sel){
    $(sel + ' tr').each(function(i){
        $(this).find('td.row-num').first().text(i + 1);
    });
}

function toast(m){
    if (typeof toastr !== 'undefined') toastr.success(m);
}

// Sidebar active state
$("ul#repair").siblings('a').attr('aria-expanded', 'true');
$("ul#repair").addClass("show");
$("ul#repair #service-list-menu").addClass("active");

// ══════════════════════════════════════════════════════════
// PRINT
// ══════════════════════════════════════════════════════════
function openPrintModal(){
    var $tbody = $('.print-tbl tbody');
    $tbody.remove();
    var $nb = $('<tbody>');
    var hasRows = false;

    $('#parts-tbody tr').each(function(){
        var $c = $(this).find('td');
        if ($c.length < 5){ return; }
        hasRows = true;
        var rowStyle = 'background:' + ($nb.find('tr').length % 2 === 0 ? '#fff' : '#f7f9fb') + ';';
        $nb.append(
            '<tr style="'+rowStyle+'">'
            +'<td style="padding:8px 12px;border-bottom:1px solid #e4e9ee;font-size:.78rem;color:#8c9baa;text-align:left;">' + ($nb.find('tr').length + 1) + '</td>'
            +'<td style="padding:8px 12px;border-bottom:1px solid #e4e9ee;font-size:.82rem;font-weight:600;color:#0f1923;">' + $c.eq(1).text() + '</td>'
            +'<td style="padding:8px 12px;border-bottom:1px solid #e4e9ee;font-size:.82rem;text-align:center;font-family:monospace;">' + $c.eq(2).find('.qi').val() + '</td>'
            +'<td style="padding:8px 12px;border-bottom:1px solid #e4e9ee;font-size:.82rem;text-align:right;font-family:monospace;">' + $c.eq(3).find('.pi').val() + '</td>'
            +'<td style="padding:8px 12px;border-bottom:1px solid #e4e9ee;font-size:.82rem;text-align:right;font-family:monospace;font-weight:700;">' + $c.eq(4).text() + '</td>'
            +'</tr>'
        );
    });

    if (!hasRows){
        $nb.append('<tr><td colspan="5" style="padding:20px;text-align:center;color:#8c9baa;font-size:.82rem;">No parts added</td></tr>');
    }

    // Summary rows
    var summaryData = [
        ['Parts Total',     $('#ft-parts').text(), false],
        ['Service Charge',  $('#s-sc').text(),      false],
        ['Discount',        $('#dc').val(),          true],
        ['Tax',             $('#tx').val(),          false],
        ['Grand Total',     $('#s-gt').text(),       false, true],
        ['Paid Amount',     $('#s-paid').text(),     false],
        ['Due Amount',      $('#s-due').text(),      false],
    ];

    summaryData.forEach(function(r){
        var bold = r[3] ? 'font-weight:800;font-size:.88rem;background:#f0f4f7;' : 'font-weight:500;';
        var color = r[2] ? 'color:#e02424;' : (r[3] ? 'color:#0f1923;' : 'color:#2c3a47;');
        $nb.append(
            '<tr>'
            +'<td colspan="4" style="padding:7px 12px;border-bottom:1px solid #e4e9ee;font-size:.8rem;color:#5a6a77;text-align:right;'+bold+'">'+r[0]+'</td>'
            +'<td style="padding:7px 12px;border-bottom:1px solid #e4e9ee;font-size:.83rem;text-align:right;font-family:monospace;'+bold+color+'">'+r[1]+'</td>'
            +'</tr>'
        );
    });

    $('.print-tbl').append($nb);
    $('#print-modal').modal('show');
}

$('#do-print-btn').on('click', function(){
    var a = window.open('');
    a.document.write('<html><head>'
        +'<style>'
        +'@import url("https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap");'
        +'*{box-sizing:border-box;margin:0;padding:0}'
        +'body{font-family:"DM Sans",sans-serif;font-size:13px;color:#0f1923;padding:32px 40px;background:#fff}'
        +'h2{font-size:22px;font-weight:800;letter-spacing:-.02em}'
        +'.header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;padding-bottom:16px;border-bottom:2px solid #0f1923}'
        +'.inv-label{font-size:18px;font-weight:800;text-transform:uppercase;letter-spacing:.04em}'
        +'.inv-ref{font-family:"DM Mono",monospace;font-size:12px;color:#1a56db;margin-top:3px}'
        +'.meta{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px}'
        +'.meta-label{font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#8c9baa;margin-bottom:4px}'
        +'table{width:100%;border-collapse:collapse;margin-top:16px}'
        +'thead tr{background:#0f1923;color:#fff}'
        +'th{padding:9px 12px;font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase}'
        +'td{padding:8px 12px;border-bottom:1px solid #e4e9ee;font-size:12px}'
        +'.mono{font-family:"DM Mono",monospace}'
        +'.footer{margin-top:24px;padding-top:14px;border-top:1px solid #e4e9ee;font-size:11px;color:#8c9baa;text-align:center}'
        +'</style></head><body>');

    a.document.write('<div class="header">');
    a.document.write('<div><h2>{{ $general_setting->site_title }}</h2><div style="font-size:11px;color:#8c9baa;margin-top:2px;">Service &amp; Repair Management</div></div>');
    a.document.write('<div><div class="inv-label">Invoice</div><div class="inv-ref">{{ $job->reference_no }}</div><div style="font-size:11px;color:#8c9baa;margin-top:2px;">{{ date(config("date_format"), strtotime($job->created_at)) }}</div></div>');
    a.document.write('</div>');

    a.document.write('<div class="meta">');
    a.document.write('<div><div class="meta-label">Bill To</div><strong style="font-size:13px;">{{ optional($job->customer)->name }}</strong><br><span style="font-size:11px;color:#5a6a77;">{{ optional($job->customer)->phone ?? "" }}</span></div>');
    a.document.write('<div><div class="meta-label">Job Info</div><span style="font-size:11px;color:#5a6a77;">Type: {{ ucfirst($job->service_type) }} &nbsp;|&nbsp; Status: {{ ucfirst(str_replace("_"," ",$job->status)) }}<br>Warehouse: {{ optional($job->warehouse)->name }}</span></div>');
    a.document.write('</div>');

    a.document.write(document.querySelector("table.print-tbl").outerHTML);
    a.document.write('<div class="footer">Service: {{ addslashes($job->title) }} &nbsp;·&nbsp; Created by: {{ optional($job->createdBy)->name ?? "System" }} &nbsp;·&nbsp; Thank you for your business.</div>');
    a.document.write('</body></html>');
    a.document.close();
    setTimeout(function(){ a.print(); a.close(); }, 500);
});
</script>
@endpush
