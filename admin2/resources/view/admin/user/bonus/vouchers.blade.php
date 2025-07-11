@extends('admin.layout')

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    @include('admin.user.bonus.partials.add-voucher')

    <div class="card card-primary border border-primary">
        <div class="card-header">
            <h3 class="card-title">User Vouchers</h3>
        </div><!-- /.box-header -->
        <div class="card-body">
            <div class="table-responsive">
                <table id="vouchers-datatable" class="table table-striped table-bordered dt-responsive"
                       cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <th>Redeem timestamp</th>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Redeemed</th>
                        <th>Exclusive</th>
                        <th>Details</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($vouchers_list as $voucher)
                        <tr>
                            <td>{{ $voucher->redeem_stamp }}</td>
                            <td>{{ $voucher->voucher_name }}</td>
                            <td>{{ $voucher->voucher_code }}</td>
                            <td>{{ $voucher->redeemed ? 'Yes' : 'No'}}</td>
                            <td>{{ $voucher->exclusive }}</td>
                            <td>{{ $voucher->bonus_name or $voucher->description }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div><!-- /.box-body -->
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script>
        $(function () {
            $("#vouchers-datatable").DataTable({
                "pageLength": 25,
                "language": {
                    "emptyTable": "No results found.",
                    "lengthMenu": "Display _MENU_ records per page"
                },
                "order": [[ {{ $sort['column'] }}, "{{ $sort['type'] }}"]],
                "columnDefs": [{"targets": 0, "orderable": false, "searchable": false}]
            });
        });
    </script>

@endsection
