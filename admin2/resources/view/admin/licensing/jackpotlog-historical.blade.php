@extends('admin.layout')
@section('content')
    @include('admin.licensing.partials.topmenu')
    @include('admin.user.partials.date-filter')
    <div class="box box-solid box-primary">
        <div class="box-header">
            <h3 class="box-title">Jackpot Log</h3>
        </div>
        <div class="box-body">
            <table id="user-datatable" class="table table-responsive table-striped table-bordered dt-responsive"
                   cellspacing="0" width="100%">
                <thead>
                <tr>
                    <th>Value</th>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Supplier</th>
                    <th>Currency</th>
                    <th>Created At</th>
                    <th>Game Reference</th>
                    <th>Contributions</th>
                    <th>Trigger Amount</th>
                    <th>Configuration</th>

                </tr>
                </thead>
                <tbody>
                @foreach($jp_log as $jp)
                    <tr>
                        <td>{{ \App\Helpers\DataFormatHelper::nf($jp->jp_value) }}</td>
                        <td>{{ $jp->jp_id }}</td>
                        <td>{{ $jp->jp_name }}</td>
                        <td>{{ $jp->network }}</td>
                        <td>{{ $jp->currency }}</td>
                        <td>{{ $jp->created_at }}</td>
                        <td>{{ $jp->game_ref }}</td>
                        <td>{{ \App\Helpers\DataFormatHelper::nf($jp->contributions) }}</td>
                        <td>{{ \App\Helpers\DataFormatHelper::nf($jp->trigger_amount) }}</td>
                        <td>{{ $jp->configuration }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script>
        $(function () {
            $("#user-datatable").DataTable({
                "pageLength": 25,
                "language": {
                    "emptyTable": "No results found.", "lengthMenu": "Display _MENU_ records per page"
                },
                "order": [[ 5, "desc"]]
            });
        });
    </script>

@endsection
