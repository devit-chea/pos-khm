@extends('backend.layout.main')
@section('content')
    <div class="container">

        @include('woocommerce::includes.nav')

        <section class="card">
            <div class="card-body">
                <table id="synclog" class="table table-hover thead-dark">
                    <thead>
                        <tr>
                            <th>{{__('db.Date')}}</th>
                            <th>{{__('db.Sync Type')}}</th>
                            <th>{{__('db.Operation')}}</th>
                            <th>{{__('db.Records')}}</th>
                            <th>{{__('db.Synced By')}}</th>
                        </tr>
                    <thead>
                        <tbody>
                            @foreach ($woocommerceSyncLog as $item)
                                <tr>
                                    <td>{{$item->created_at}} <br>
                                            @php
                                                $times_ago = Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $item->created_at)->diffForHumans();
                                            @endphp
                                        <small>{{$times_ago}}</small>
                                    </td>
                                    <td>{{$item->sync_type}}</td>
                                    <td>{{$item->operation}}</td>
                                    <td>
                                        @php
                                        $records = json_decode($item->records, true);
                                        if (!empty($records)) {
                                            echo implode(', ', $records).'<br><small>Item: '.count($records).'</small>';
                                        }
                                        @endphp
                                    </td>
                                    <td>{{$item->name}}</td>
                                </tr>
                            @endforeach

                        </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection


@push('scripts')

<script>
    (function($) {
        "use strict";
        $(document).ready(function() {
            $('#synclog').DataTable({
                order: [[ 0, 'desc' ]],
                columnDefs: [ {
                    'targets': 3,
                    'orderable': false
                } ],
                dom: 'lfrtip',
            });
        });
    })(jQuery);
</script>

@endpush
