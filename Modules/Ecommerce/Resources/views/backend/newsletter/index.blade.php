@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush


@section('content')

    @if (session()->has('not_permitted'))
        <div class="alert alert-danger alert-dismissible text-center">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session()->get('not_permitted') }}
        </div>
    @endif

    <section>
        <div class="container-fluid">

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible text-center mar-bot-30">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session()->has('message'))
                <div class="alert alert-{{ session('type') }} alert-dismissible text-center mar-bot-30">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    {{ session('message') }}
                </div>
            @endif

        </div>

        <div class="table-responsive">
            <table id="newsletter_list_table" class="table">
                <thead>
                    <tr>
                        <th class="not-exported"></th>
                        <th scope="col">{{ __('db.Email') }}</th>
                        <th scope="col">{{ __('db.Subscription Date') }}</th>
                        <th scope="col" class="not-exported">{{ __('db.Action') }}</th>
                    </tr>
                </thead>


                <tbody>
                    @foreach ($newsletters as $key => $newsletter)
                        <tr data-id="{{ $newsletter->id }}">
                            <td>{{ $key }}</td>
                            <td>{{ $newsletter->email ?? '--' }}</td>
                            <td>{{ \Carbon\Carbon::parse($newsletter->created_at)->format('Y-M-d') }}</td>
                            <td>
                                <a href="{{ route('newsletter.delete', $newsletter->id) }}" class="btn btn-danger btn-sm"
                                    data-confirm-message="{{ __('db.Are you sure want to delete?') }}">
                                    <i class="ti ti-trash"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

            </table>
        </div>
    </section>

@endsection
@push('scripts')
    @include('backend.layout.partials.datatable_js')
    <script type="text/javascript">
        "use strict";

        $('#newsletter_list_table').DataTable({
            order: [],
            language: {
                lengthMenu: '_MENU_ {{ trans('db.records per page') }}',
                info: '<small>{{ trans('db.Showing') }} _START_ - _END_ (_TOTAL_)</small>',
                search: '{{ trans('db.Search') }}',
                paginate: {
                    previous: '<i class="ti ti-chevron-left"></i>',
                    next: '<i class="ti ti-chevron-right"></i>'
                }
            },
            columnDefs: [{
                    orderable: false,
                    targets: [0, 3]
                },
                {
                    render: function(data, type, row, meta) {
                        if (type === 'display') {
                            data =
                                '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                        }
                        return data;
                    },
                    checkboxes: {
                        selectRow: true,
                        selectAllRender: '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>'
                    },
                    targets: [0]
                }
            ],
            select: {
                style: 'multi',
                selector: 'td:first-child'
            },
            lengthMenu: [
                [10, 25, 50, -1],
                [10, 25, 50, "All"]
            ],
            dom: '<"row"lfB>rtip',
            buttons: [{
                    extend: 'pdf',
                    text: '<i class="ti ti-file-type-pdf"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)'
                    }
                },
                {
                    extend: 'csv',
                    text: '<i class="ti ti-file-type-csv"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)'
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="ti ti-printer"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)'
                    }
                },
                {
                    extend: 'colvis',
                    text: '<i class="ti ti-eye"></i>',
                    columns: ':gt(0)'
                }
            ],
        });
    </script>
@endpush
