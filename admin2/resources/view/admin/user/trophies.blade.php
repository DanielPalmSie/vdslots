@extends('admin.layout')

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    <div class="row">
        <div class="col-6 col-lg-7">@include('admin.user.partials.trophies.filter')</div>
        @if (p('give.trophies'))
            <div class="col-6 col-lg-5">@include('admin.user.partials.trophies.add-form')</div>
        @endif
    </div>
    <div class="card card-primary border border-primary">
        <div class="card-header">
            <h3 class="card-title">User Trophies</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="trophies-datatable" class="table table-striped table-bordered dt-responsive"
                       cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <th>Image</th>
                        <th>Trophy</th>
                        <th>Rules</th>
                        <th>Updated At</th>
                        <th>Finished</th>
                        <th>Progress</th>
                        <th>Threshold</th>
                        <th>Game name</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $cur_user = cu($user->id);
                    ?>
                    @foreach($user_trophy_events as $te)
                        <tr>
                            <td><img class="img-responsive" src="{{ $te['img'] }}"></td>
                            <td>{{ t('trophyname.'.$te['alias']) }}</td>
                            <td><?= rep(tAssoc('trophy.'.phive('Trophy')->getDescrStr($te).'.descr', $te), $cur_user, true) ?></td>
                            <td>{{ $te['te_updated_at'] }}</td>
                            <td>{{ empty($te['finished']) ? 'No' : 'Yes' }}</td>
                            <td>{{ $te['te_progress'] }}</td>
                            <td>{{ $te['te_thershold'] }}</td>
                            <td>{{ $te['game_name'] }}</td>
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
            $("#trophies-datatable").DataTable({
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
