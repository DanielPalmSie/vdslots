@extends('admin.layout')
@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    @include('admin.user.gamestatistics.partials.cashback-date-filter')
    <div class="box box-solid box-primary">
        <div class="box-header">
            Casino Weekend Booster (Current week: {{ \Carbon\Carbon::now()->weekOfYear }})
        </div>
        <div class="box-body">
            <table id="user-cashback-datatable" class="table table-responsive table-bordered dt-responsive" cellspacing="0"
                   width="100%">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Earned Amount ({{ $user->currency }})</th>
                        <th>Released Amount ({{ $user->currency }})</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($res['list'] as $key => $val)
                    <tr>
                        @if($res['type'] == 'year')
                            <td>{{ \Carbon\Carbon::create($val['year'], $val['month'])->format('F') }}</td>
                        @elseif($res['type'] == 'week')
                            <td>{{ \Carbon\Carbon::create($val['year'], $val['month'], $val['day'])->format('D: Y-m-d') }}</td>
                        @else
                            <td>{{ \Carbon\Carbon::create($val['year'], $val['month'], $val['day'])->format('Y-m-d') }}</td>
                        @endif
                        <td>{{ \App\Helpers\DataFormatHelper::nf($val['earned']) }}</td>
                        <td>{{ \App\Helpers\DataFormatHelper::nf($val['released']) }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td><b>Totals</b></td>
                        <td><b>{{ \App\Helpers\DataFormatHelper::nf($res['totals']['earned']) }}</b></td>
                        <td><b>{{ \App\Helpers\DataFormatHelper::nf($res['totals']['released']) }}</b></td>
                    </tr>
                </tfoot>
            </table>
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
