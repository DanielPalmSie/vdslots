@extends('admin.layout')

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    @include('admin.user.partials.date-filter')
    <div class="card card-primary border border-primary">
        <div class="card-header">
            Reward History
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="user-datatable" class="table table-striped table-bordered dt-responsive"
                       cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Expire Date</th>
                        <th>Activation Date</th>
                        <th>Completion Date</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($reward_history as $reward)
                        <tr>

                            <td><img class="img-responsive" src="{{ $reward['img'] }}"></td>
                            <td>{{ $reward['description'] }}</td>
                            <td>{{ $reward['expire_at'] }}</td>
                            <td>{{ $reward['activated_a'] == 0 ? 'Not applicable' : $reward['activated_at'] }}</td>
                            <td>{{ $reward['finished_at'] == 0 ? 'Not applicable' : $reward['finished_at'] }}</td>
                            <td>{{ \App\Helpers\DataFormatHelper::getRewardStatus($reward['status']) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script>
        $(function () {
            $("#user-datatable").DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No results found.", "lengthMenu": "Display _MENU_ records per page"
                },
                "order": [[ {{ $sort['column'] }}, "{{ $sort['type'] }}"]],
                "columnDefs": [{"targets": 0, "orderable": false, "searchable": false}]
            });
        });
    </script>
@endsection
