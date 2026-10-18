@extends('backend.layout.main')

@push('css')
<style>
    .timeline { border-left: 3px solid #6777ef; margin-left: 10px; padding-left: 20px; }
    .timeline-item { position: relative; margin-bottom: 20px; }
    .timeline-item::before { content: ''; width: 13px; height: 13px; border-radius: 50%; background: #6777ef; position: absolute; left: -27px; top: 4px; border: 2px solid #fff; box-shadow: 0 0 0 2px #6777ef; }
    .info-card { background: #f8f9fa; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
</style>
@endpush

@section('content')

@if(session()->has('message'))
    <div class="alert alert-success alert-dismissible text-center">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('message') }}
    </div>
@endif

<section class="forms">
    <div class="container-fluid">

        <div class="row mb-3">
            <div class="col-md-8">
                <h4 class="mb-0">
                    <i class="fa {{ $job->service_type === 'device' ? 'fa-mobile' : 'fa-car' }} mr-2"></i>
                    {{ $job->title }}
                    <small class="text-muted ml-2">{{ $job->reference_no }}</small>
                </h4>
            </div>
            <div class="col-md-4 text-right">
                <a href="{{ route('repair.service.edit', $job->id) }}" class="btn btn-warning btn-sm">
                    <i class="fa fa-edit"></i> {{ __('db.edit') }}
                </a>
                <a href="{{ route('repair.service.index') }}" class="btn btn-secondary btn-sm ml-1">
                    <i class="fa fa-arrow-left"></i> {{ __('db.Back') }}
                </a>
                <button onclick="printJob()" class="btn btn-default btn-sm ml-1">
                    <i class="dripicons-print"></i> {{ __('db.Print') }}
                </button>
            </div>
        </div>

        <div class="row" id="printable-area">

            <div class="col-md-8">

                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ __('db.job_info') }}</h5>
                        <div>
                            {!! $job->status_badge !!}
                            {!! $job->priority_badge !!}
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 info-card">
                                <p><strong>{{ __('db.customer') }}:</strong> {{ optional($job->customer)->name ?? 'N/A' }}</p>
                                <p><strong>{{ __('db.Phone') }}:</strong> {{ optional($job->customer)->phone ?? 'N/A' }}</p>
                                <p><strong>{{ __('db.Warehouse') }}:</strong> {{ optional($job->warehouse)->name ?? 'N/A' }}</p>
                                <p><strong>{{ __('db.technician') }}:</strong> {{ optional($job->assignedTo)->name ?? __('db.unassigned') }}</p>
                            </div>
                            <div class="col-md-6 info-card">
                                <p><strong>{{ __('db.date_created') }}:</strong> {{ date(config('date_format'), strtotime($job->created_at)) }}</p>
                                <p><strong>{{ __('db.expected_delivery') }}:</strong> {{ $job->expected_delivery_date ? $job->expected_delivery_date->format(config('date_format')) : 'N/A' }}</p>
                                @if($job->delivery_date)
                                    <p><strong>{{ __('db.delivered_on') }}:</strong> {{ $job->delivery_date->format(config('date_format')) }}</p>
                                @endif
                                <p><strong>{{ __('db.Created By') }}:</strong> {{ optional($job->createdBy)->name }}</p>
                            </div>
                        </div>
                        @if($job->description)
                            <p class="mt-2"><strong>{{ __('db.Description') }}:</strong> {{ $job->description }}</p>
                        @endif
                        @if($job->note)
                            <p><strong>{{ __('db.Note') }}:</strong> {{ $job->note }}</p>
                        @endif
                    </div>
                </div>

                @if($job->service_type === 'device' && $job->device)
                    <div class="card mb-3">
                        <div class="card-header"><h5 class="mb-0">📱 {{ __('db.device_details') }}</h5></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr><th width="150">{{ __('db.device_type') }}</th><td>{{ ucfirst($job->device->device_type) }}</td></tr>
                                        <tr><th>{{ __('db.Brand') }}</th><td>{{ $job->device->brand ?? '—' }}</td></tr>
                                        <tr><th>{{ __('db.model') }}</th><td>{{ $job->device->model ?? '—' }}</td></tr>
                                        <tr><th>{{ __('db.serial_number') }}</th><td>{{ $job->device->serial_number ?? '—' }}</td></tr>
                                        <tr><th>{{ __('db.imei') }}</th><td>{{ $job->device->imei ?? '—' }}</td></tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr><th width="150">{{ __('db.password_hint') }}</th><td>{{ $job->device->password_hint ?? '—' }}</td></tr>
                                        <tr><th>{{ __('db.accessories') }}</th><td>{{ $job->device->accessories ?? '—' }}</td></tr>
                                    </table>
                                    @if($job->device->issue_reported)
                                        <p><strong>{{ __('db.issue_reported') }}:</strong><br>{{ $job->device->issue_reported }}</p>
                                    @endif
                                    @if($job->device->condition_notes)
                                        <p><strong>{{ __('db.condition_on_arrival') }}:</strong><br>{{ $job->device->condition_notes }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif($job->service_type === 'vehicle' && $job->vehicle)
                    <div class="card mb-3">
                        <div class="card-header"><h5 class="mb-0">🚗 {{ __('db.vehicle_details') }}</h5></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr><th width="150">{{ __('db.vehicle_type') }}</th><td>{{ ucfirst(str_replace('_', ' ', $job->vehicle->vehicle_type)) }}</td></tr>
                                        <tr><th>{{ __('db.Brand') }}</th><td>{{ $job->vehicle->brand ?? '—' }}</td></tr>
                                        <tr><th>{{ __('db.model') }}</th><td>{{ $job->vehicle->model ?? '—' }}</td></tr>
                                        <tr><th>{{ __('db.year') }}</th><td>{{ $job->vehicle->year ?? '—' }}</td></tr>
                                        <tr><th>{{ __('db.registration_no') }}</th><td>{{ $job->vehicle->registration_no ?? '—' }}</td></tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr><th width="150">{{ __('db.engine_no') }}</th><td>{{ $job->vehicle->engine_no ?? '—' }}</td></tr>
                                        <tr><th>{{ __('db.chassis_no') }}</th><td>{{ $job->vehicle->chassis_no ?? '—' }}</td></tr>
                                        <tr><th>{{ __('db.mileage') }}</th><td>{{ $job->vehicle->mileage ? number_format($job->vehicle->mileage) . ' km' : '—' }}</td></tr>
                                        <tr><th>{{ __('db.fuel_level') }}</th><td>{{ $job->vehicle->fuel_level ?? '—' }}</td></tr>
                                    </table>
                                    @if($job->vehicle->condition_notes)
                                        <p><strong>{{ __('db.condition_notes') }}:</strong><br>{{ $job->vehicle->condition_notes }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0">🔧 {{ __('db.parts_items_used') }}</h5></div>
                    <div class="card-body">
                        @if($job->items->count())
                            <div class="table-responsive">
                                <table class="table table-bordered" id="show-parts-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('db.product') }}</th>
                                            <th>{{ __('db.qty') }}</th>
                                            <th>{{ __('db.Unit Price') }}</th>
                                            <th>{{ __('db.Total') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($job->items as $i => $item)
                                            <tr>
                                                <td>{{ $i + 1 }}</td>
                                                <td>{{ optional($item->product)->name }} [{{ optional($item->product)->code }}]</td>
                                                <td>{{ $item->quantity }}</td>
                                                <td>{{ number_format($item->unit_price, config('decimal')) }}</td>
                                                <td>{{ number_format($item->total, config('decimal')) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr><td colspan="4" class="text-right"><strong>{{ __('db.parts_total') }}:</strong></td><td><strong>{{ number_format($job->items->sum('total'), config('decimal')) }}</strong></td></tr>
                                        <tr><td colspan="4" class="text-right">{{ __('db.service_charge') }}:</td><td>{{ number_format($job->service_charge, config('decimal')) }}</td></tr>
                                        <tr><td colspan="4" class="text-right">{{ __('db.Discount') }}:</td><td>{{ number_format($job->discount, config('decimal')) }}</td></tr>
                                        <tr><td colspan="4" class="text-right">{{ __('db.Tax') }}:</td><td>{{ number_format($job->tax, config('decimal')) }}</td></tr>
                                        <tr><td colspan="4" class="text-right"><strong>{{ __('db.grand total') }}:</strong></td><td><strong>{{ number_format($job->total_amount, config('decimal')) }}</strong></td></tr>
                                        <tr><td colspan="4" class="text-right">{{ __('db.Paid Amount') }}:</td><td>{{ number_format($job->paid_amount, config('decimal')) }}</td></tr>
                                        <tr><td colspan="4" class="text-right"><strong>{{ __('db.Due') }}:</strong></td><td><strong>{{ number_format($job->due_amount, config('decimal')) }}</strong></td></tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <p class="text-muted mb-0">{{ __('db.no_parts_used') }}</p>
                        @endif
                    </div>
                </div>

            </div>

            <div class="col-md-4">

                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0">💰 {{ __('db.billing_summary') }}</h5></div>
                    <div class="card-body p-3">
                        <table class="table table-sm mb-0">
                            <tr><td>{{ __('db.parts_total') }}</td><td class="text-right">{{ number_format($job->items->sum('total'), config('decimal')) }}</td></tr>
                            <tr><td>{{ __('db.service_charge') }}</td><td class="text-right">{{ number_format($job->service_charge, config('decimal')) }}</td></tr>
                            <tr><td>{{ __('db.Discount') }}</td><td class="text-right text-danger">- {{ number_format($job->discount, config('decimal')) }}</td></tr>
                            <tr><td>{{ __('db.Tax') }}</td><td class="text-right">{{ number_format($job->tax, config('decimal')) }}</td></tr>
                            <tr class="table-dark"><td><strong>{{ __('db.grand total') }}</strong></td><td class="text-right"><strong>{{ number_format($job->total_amount, config('decimal')) }}</strong></td></tr>
                            <tr class="table-success"><td>{{ __('db.Paid') }}</td><td class="text-right">{{ number_format($job->paid_amount, config('decimal')) }}</td></tr>
                            <tr class="{{ $job->due_amount > 0 ? 'table-danger' : '' }}"><td><strong>{{ __('db.Due') }}</strong></td><td class="text-right"><strong>{{ number_format($job->due_amount, config('decimal')) }}</strong></td></tr>
                        </table>
                    </div>
                </div>

                <div class="card mb-3 d-print-none">
                    <div class="card-header"><h5 class="mb-0">⚡ {{ __('db.quick_status_update') }}</h5></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>{{ __('db.new_status') }}</label>
                            <select id="quick_status" class="form-control">
                                <option value="pending"     {{ $job->status === 'pending'     ? 'selected' : '' }}>{{ __('db.Pending') }}</option>
                                <option value="diagnosed"   {{ $job->status === 'diagnosed'   ? 'selected' : '' }}>{{ __('db.diagnosed') }}</option>
                                <option value="in_progress" {{ $job->status === 'in_progress' ? 'selected' : '' }}>{{ __('db.in_progress') }}</option>
                                <option value="completed"   {{ $job->status === 'completed'   ? 'selected' : '' }}>{{ __('db.Completed') }}</option>
                                <option value="delivered"   {{ $job->status === 'delivered'   ? 'selected' : '' }}>{{ __('db.Delivered') }}</option>
                                <option value="cancelled"   {{ $job->status === 'cancelled'   ? 'selected' : '' }}>{{ __('db.Cancel') }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ __('db.Note') }}</label>
                            <textarea id="quick_note" class="form-control" rows="2" placeholder="{{ __('db.optional_note') }}"></textarea>
                        </div>
                        <button type="button" id="quick-update-btn" class="btn btn-primary btn-sm btn-block">
                            <i class="dripicons-checkmark"></i> {{ __('db.update_status') }}
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5 class="mb-0">📋 {{ __('db.activity_timeline') }}</h5></div>
                    <div class="card-body">
                        @if($job->updates->count())
                            <div class="timeline">
                                @foreach($job->updates as $update)
                                    <div class="timeline-item">
                                        <small class="text-muted">{{ $update->created_at->format('d M Y, h:i A') }}</small>
                                        <br>
                                        <span class="badge badge-secondary">{{ ucfirst(str_replace('_', ' ', $update->status)) }}</span>
                                        by <strong>{{ optional($update->updatedBy)->name }}</strong>
                                        @if($update->note)
                                            <br><small class="text-muted">{{ $update->note }}</small>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted">{{ __('db.no_updates_yet') }}</p>
                        @endif
                    </div>
                </div>

            </div>
        </div>

    </div>
</section>

{{-- Print Modal --}}
<div id="print-modal" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="container mt-3 pb-2 border-bottom">
                <div class="row">
                    <div class="col-md-6 d-print-none">
                        <button id="do-print-btn" type="button" class="btn btn-default btn-sm">
                            <i class="dripicons-print"></i> {{ __('db.Print') }}
                        </button>
                    </div>
                    <div class="col-md-6 d-print-none">
                        <button type="button" data-dismiss="modal" class="close"><span><i class="dripicons-cross"></i></span></button>
                    </div>
                    <div class="col-md-12">
                        <h3 class="modal-title text-center">{{ $general_setting->site_title }}</h3>
                    </div>
                    <div class="col-md-12 text-center">
                        <i style="font-size:15px;">{{ __('db.service_job_details') }}</i>
                    </div>
                </div>
            </div>
            <div id="print-modal-content" class="modal-body"></div>
            <br>
            <table class="table table-bordered print-parts-table">
                <thead>
                    <th>#</th>
                    <th>{{ __('db.product') }}</th>
                    <th>{{ __('db.qty') }}</th>
                    <th>{{ __('db.Unit Price') }}</th>
                    <th>{{ __('db.Total') }}</th>
                </thead>
                <tbody></tbody>
            </table>
            <div id="print-modal-footer" class="modal-body"></div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    $('#quick-update-btn').on('click', function () {
        $.post('{{ route("repair.service.add-update", $job->id) }}', {
            status: $('#quick_status').val(),
            note:   $('#quick_note').val()
        }, function (res) {
            if (res.success) { alert('{{ __("db.status_updated_refresh") }}'); location.reload(); }
        });
    });

    function printJob() { openPrintModal(); }

    function openPrintModal() {
        var htmltext = '{{ __("db.date") }}: {{ date(config("date_format"), strtotime($job->created_at)) }}'
            + '<br>{{ __("db.reference") }}: {{ $job->reference_no }}'
            + '<br>{{ __("db.service_type") }}: {{ ucfirst($job->service_type) }}'
            + '<br>{{ __("db.status") }}: {{ ucfirst(str_replace("_", " ", $job->status)) }}'
            + '<br>{{ __("db.priority") }}: {{ ucfirst($job->priority) }}'
            @if($job->expected_delivery_date)
            + '<br>{{ __("db.expected_delivery") }}: {{ $job->expected_delivery_date->format(config("date_format")) }}'
            @endif
            @if($job->note)
            + '<br>{{ __("db.Note") }}: {{ addslashes($job->note) }}'
            @endif
            + '<br><br><div class="row">'
            + '<div class="col-md-6"><strong>{{ __("db.customer") }}:</strong><br>{{ optional($job->customer)->name }}<br>{{ optional($job->customer)->phone ?? "" }}</div>'
            + '<div class="col-md-6"><div class="float-right"><strong>{{ __("db.Warehouse") }}:</strong><br>{{ optional($job->warehouse)->name }}<br><strong>{{ __("db.technician") }}:</strong><br>{{ optional($job->assignedTo)->name ?? __("db.unassigned") }}</div></div>'
            + '</div>'
            @if($job->service_type === 'device' && $job->device)
            + '<br><strong>{{ __("db.device_details") }}:</strong> {{ $job->device->brand ?? "" }} {{ $job->device->model ?? "" }}'
            + ' | {{ __("db.serial_number") }}: {{ $job->device->serial_number ?? "—" }}'
            + ' | {{ __("db.imei") }}: {{ $job->device->imei ?? "—" }}'
            @elseif($job->service_type === 'vehicle' && $job->vehicle)
            + '<br><strong>{{ __("db.vehicle_details") }}:</strong> {{ $job->vehicle->brand ?? "" }} {{ $job->vehicle->model ?? "" }} ({{ $job->vehicle->year ?? "" }})'
            + ' | {{ __("db.registration_no") }}: {{ $job->vehicle->registration_no ?? "—" }}'
            @endif
        ;

        $(".print-parts-table tbody").remove();
        var newBody = $("<tbody>");

        @if($job->items->count())
            @foreach($job->items as $i => $item)
                var row{{ $i }} = $("<tr>");
                row{{ $i }}.append(
                    '<td>{{ $i + 1 }}</td>'
                    + '<td>{{ optional($item->product)->name }} [{{ optional($item->product)->code }}]</td>'
                    + '<td>{{ $item->quantity }}</td>'
                    + '<td>{{ number_format($item->unit_price, config("decimal")) }}</td>'
                    + '<td>{{ number_format($item->total, config("decimal")) }}</td>'
                );
                newBody.append(row{{ $i }});
            @endforeach
            var totalsData = [
                ['{{ __("db.parts_total") }}',    '{{ number_format($job->items->sum("total"), config("decimal")) }}'],
                ['{{ __("db.service_charge") }}', '{{ number_format($job->service_charge,     config("decimal")) }}'],
                ['{{ __("db.Discount") }}',       '{{ number_format($job->discount,           config("decimal")) }}'],
                ['{{ __("db.Tax") }}',            '{{ number_format($job->tax,               config("decimal")) }}'],
                ['{{ __("db.grand total") }}',    '{{ number_format($job->total_amount,       config("decimal")) }}'],
                ['{{ __("db.Paid Amount") }}',    '{{ number_format($job->paid_amount,        config("decimal")) }}'],
                ['{{ __("db.Due") }}',            '{{ number_format($job->due_amount,         config("decimal")) }}'],
            ];
            $.each(totalsData, function(i, r) {
                var tr = $("<tr>");
                tr.append('<td colspan="4">' + r[0] + ':</td><td>' + r[1] + '</td>');
                newBody.append(tr);
            });
        @else
            newBody.append('<tr><td colspan="5" class="text-center">{{ __("db.no_parts_added") }}</td></tr>');
        @endif

        $("table.print-parts-table").append(newBody);
        var htmlfooter = '<p><strong>{{ __("db.title") }}:</strong> {{ addslashes($job->title) }}</p>'
            + '<strong>{{ __("db.Created By") }}:</strong> {{ optional($job->createdBy)->name }}'
            + ' | {{ date(config("date_format"), strtotime($job->created_at)) }}';
        $('#print-modal-content').html(htmltext);
        $('#print-modal-footer').html(htmlfooter);
        $('#print-modal').modal('show');
    }

    $('#do-print-btn').on('click', function () {
        var a = window.open('');
        a.document.write('<html><head><style>body{font-family:sans-serif;line-height:1.5;font-size:13px;}h3{text-align:center;margin-bottom:2px;}table{width:100%;border-collapse:collapse;margin-top:15px;}th,td{border:1px solid #000;padding:7px;text-align:left;}.no-border td{border:none;padding:0;vertical-align:top;}</style></head><body>');
        a.document.write('<h3>{{ $general_setting->site_title }}</h3>');
        a.document.write('<p style="text-align:center"><i>{{ __("db.service_job_details") }}</i></p>');
        a.document.write('<table class="no-border" style="margin-top:5px;"><tr>');
        a.document.write('<td style="width:50%;padding-left:0;vertical-align:top;">');
        a.document.write('{{ __("db.date") }}: {{ date(config("date_format"), strtotime($job->created_at)) }}<br>');
        a.document.write('{{ __("db.reference") }}: {{ $job->reference_no }}<br>');
        a.document.write('{{ __("db.service_type") }}: {{ ucfirst($job->service_type) }}<br>');
        a.document.write('{{ __("db.status") }}: {{ ucfirst(str_replace("_", " ", $job->status)) }}<br>');
        a.document.write('{{ __("db.priority") }}: {{ ucfirst($job->priority) }}<br>');
        @if($job->expected_delivery_date)
        a.document.write('{{ __("db.expected_delivery") }}: {{ $job->expected_delivery_date->format(config("date_format")) }}<br>');
        @endif
        @if($job->service_type === 'device' && $job->device)
        a.document.write('{{ __("db.device_details") }}: {{ ($job->device->brand ?? "") . " " . ($job->device->model ?? "") }}<br>');
        a.document.write('{{ __("db.serial_number") }}: {{ $job->device->serial_number ?? "—" }}<br>');
        a.document.write('{{ __("db.imei") }}: {{ $job->device->imei ?? "—" }}<br>');
        @elseif($job->service_type === 'vehicle' && $job->vehicle)
        a.document.write('{{ __("db.vehicle_details") }}: {{ ($job->vehicle->brand ?? "") . " " . ($job->vehicle->model ?? "") . " (" . ($job->vehicle->year ?? "") . ")" }}<br>');
        a.document.write('{{ __("db.registration_no") }}: {{ $job->vehicle->registration_no ?? "—" }}<br>');
        @endif
        a.document.write('</td>');
        a.document.write('<td style="width:50%;padding-right:0;vertical-align:top;text-align:right;">');
        a.document.write('<strong>{{ __("db.customer") }}:</strong><br>{{ optional($job->customer)->name ?? "" }}<br>{{ optional($job->customer)->phone ?? "" }}<br><br>');
        a.document.write('<strong>{{ __("db.Warehouse") }}:</strong><br>{{ optional($job->warehouse)->name ?? "" }}<br><br>');
        a.document.write('<strong>{{ __("db.technician") }}:</strong><br>{{ optional($job->assignedTo)->name ?? __("db.unassigned") }}<br>');
        a.document.write('</td></tr></table>');
        a.document.write('<p style="margin:8px 0;"><strong>{{ __("db.title") }}:</strong> {{ addslashes($job->title) }}</p>');
        @if($job->description)
        a.document.write('<p style="margin:4px 0;"><strong>{{ __("db.Description") }}:</strong> {{ addslashes($job->description) }}</p>');
        @endif
        @if($job->note)
        a.document.write('<p style="margin:4px 0;"><strong>{{ __("db.Note") }}:</strong> {{ addslashes($job->note) }}</p>');
        @endif
        var tableHTML = document.querySelector("table.print-parts-table").outerHTML;
        a.document.write(tableHTML);
        a.document.write('<br><p><strong>{{ __("db.Created By") }}:</strong> {{ optional($job->createdBy)->name }} | {{ date(config("date_format"), strtotime($job->created_at)) }}</p>');
        a.document.write('</body></html>');
        a.document.close();
        a.focus();
        setTimeout(function () { a.print(); a.close(); }, 500);
    });

    $("ul#repair").siblings('a').attr('aria-expanded', 'true');
    $("ul#repair").addClass("show");
    $("ul#repair #service-list-menu").addClass("active");
</script>
@endpush
