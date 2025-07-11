@extends('admin.layout')
@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    @include('admin.user.gamestatistics.partials.cashback-date-filter')
    <div class="card card-primary border border-primary">
        <div class="card-header">
            Casino Weekend Booster (Current week: {{ \Carbon\Carbon::now()->weekOfYear }})
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="user-cashback-datatable" class="table table-bordered dt-responsive" cellspacing="0"
                       width="100%">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount ({{ $user->currency }})</th>
                        <th>Released Amount ({{ $user->currency }})</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($res['list'] as $key => $val)
                        <tr>
                            @if($res['type'] == 'year')
                                <td>{{ \Carbon\Carbon::create(null,$key)->format('F') }}</td>
                            @elseif($res['type'] == 'month')
                                <td>{{ \Carbon\Carbon::create($date_range['year'], $date_range['month'],$key)->format('Y-m-d') }}</td>
                            @else
                                <td>{{ $key }}</td>
                            @endif
                            <td>{{ \App\Helpers\DataFormatHelper::nf($val['amount']) }}</td>
                            <td>{{ \App\Helpers\DataFormatHelper::nf($val['released']) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <td><b>Total</b></td>
                        <td><b>{{ \App\Helpers\DataFormatHelper::nf($res['total']) }}</b></td>
                        <td><b>{{ \App\Helpers\DataFormatHelper::nf($res['totals']['released'] ?? 0) }}</b></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script>
        $('#user-cashback-datatable').DataTable({
            "searching": false,
            "paging": false,
            "info": false,
            "ordering": false,
            "language": {
                "emptyTable": "No results found.",
                "lengthMenu": "Display _MENU_ records per page"
            }
        });
    </script>
@endsection
