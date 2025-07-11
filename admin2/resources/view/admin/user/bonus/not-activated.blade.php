@extends('admin.layout')

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    @include('admin.user.bonus.partials.bonuses-filter')
    <div class="card">
        @include('admin.user.bonus.partials.nav-bonuses')
        <div class="card-body">
            <div class="row">
                <div class="table-responsive">
                    <table id="user-datatable" class="table table-striped table-bordered dt-responsive no-wrap"
                           cellspacing="0" width="100%">
                        <thead>
                        <tr>
                            <th>Expires</th>
                            <th>Bonus Type</th>
                            <th>Bonus</th>
                            <th>Comment</th>
                            <th>Bonus Amount</th>
                            <th>Game</th>
                            <th>Valid days</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($rewards_not_activated as $reward)
                            <tr>
                                <td>{{ $reward->expire_at }}</td>
                                <td>{{ $reward->type }}</td>
                                <td>{{ $reward->description }}</td>
                                <td>{{ $reward->comment }}</td>
                                <td>{{ $reward->amount }}</td>
                                <td>{{ $reward->game_name }}</td>
                                <td>{{ $reward->valid_days }}</td>
                                <td>
                                    @if(p('account.removebonus'))
                                        <a href="{{ $app['url_generator']->generate('admin.user-delete-award-entry', ['user' => $user->id, 'award_id' => $reward->tao_id]) }}">Delete</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
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
                    "emptyTable": "No results found.",
                    "lengthMenu": "Display _MENU_ records per page"
                },
                "order": [[ {{ $sort['column'] }}, "{{ $sort['type'] }}"]],
                "columnDefs": [{"targets": 7, "orderable": false, "searchable": false}]
            });
        });
    </script>
@endsection
