@extends('admin.layout')
<?php
$u = cu($user->username);
?>
@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    <div class="card card-primary border border-primary">
        <div class="card-header">
            <div class="card-title">
                Game History
            </div>
        </div>
        <div class="row">
            <div class="col-6">
                <div class="card shadow-none">
                    <div class="game-history-wins-header card-header border-bottom-0 text-md row">
                        <div class="col-6 col-xl-1">Wins</div>
                        <div class="col-6 col-xl-3">
                            <select id="game-history-vertical-select" name="vertical" class="form-control select2-class">
                                <option value="casino" {{ !isset($vertical) || $vertical === 'casino' ? 'selected="selected"' : '' }}>
                                    Casino
                                </option>
                                <option value="bingo" {{ $vertical === 'bingo' ? 'selected="selected"' : '' }}>
                                    Bingo
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="wins">
                                <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Game Name</th>
                                    <th>Won Amount</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($wins as $win)
                                    <tr>
                                        <td>{{ $win->created_at }}</td>
                                        <td>{{ $win->game_name }}</td>
                                        <td>{{ $win->currency }} {{ $win->amount / 100 }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card shadow-none">
                    <p class="card-header border-bottom-0 text-md">Wagers</p>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="bets">
                                <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Game Name</th>
                                    <th>Wagered Amount</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($bets as $bet)
                                    <tr>
                                        <td>{{ $bet->created_at }}</td>
                                        <td>{{ $bet->game_name }}</td>
                                        <td>{{ $bet->currency }} {{ $bet->amount / 100 }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end bg-white pt-0" id="custom-pagination-container">
            <!-- Pagination will be moved here -->
        </div>

    </div>

@endsection

@section('footer-javascript')
    @parent
    <script type="text/javascript">
        $(document).ready(function() {
            var table_init = {};
            const user = <?= $user->id ?>;

            table_init['columnDefs'] = [{
                "targets": 2,
                "render": function(data, type, row, meta) {
                    if (!row.currency)
                        return data;
                    return row.currency + ' ' + row.amount / 100;
                },
            }];

            var order = [{'column':0, 'dir':'desc'}];

            table_init['processing'] = true;
            table_init['serverSide'] = true;

            const vertical = (new URL(window.location)).searchParams.get('vertical');
            const url = `/admin2/userprofile/${user}/game-history/?vertical=${vertical}`;

            table_init['ajax'] = {
                "url" : url,
                "type" : "POST",
                "data" : function(d) {
                    d.source = 'bets';
                    d.order = order;
                    d.max_records_total = parseInt("{{ $max_records_total }}");
                }
            };

            table_init['columns'] = [];
            table_init['columns'].push({ "data": "created_at"});
            table_init['columns'].push({ "data": "game_name"});
            table_init['columns'].push({ "data": "amount"});

            table_init['language'] = {
                "emptyTable": "No results found.",
            };

            table_init['bPaginate'] = true;
            table_init['bLengthChange'] = false;
            table_init['bSort'] = false;
            table_init['bInfo'] = false;
            table_init['bAutoWidth'] = false;

            table_init['searching'] = false;
            table_init['order'] = [ [ 0, 'desc' ] ];
            table_init['deferLoading'] = parseInt("{{ $max_records_total }}");
            table_init['pageLength'] = parseInt("{{ $page_length }}");

            $('#bets').DataTable(table_init);
            $('#bets').on( 'page.dt', function () {
                $('#wins').DataTable().ajax.reload();
            });

            table_init['ajax']["data"] = function(d) {
                d.source = 'wins';
                d.order = order;

                var info = $('#bets').DataTable().page.info();
                d.start = info.start;
                d.length = info.length;

                d.max_records_total = parseInt("{{ $max_records_total }}");
            };
            table_init['bPaginate'] = false;

            table_init['drawCallback'] = function() {
                var pagination = $('#bets_paginate');
                var customPaginationContainer = $('#custom-pagination-container');

                if (!customPaginationContainer.has(pagination).length) {
                    customPaginationContainer.html(pagination);
                }
            };

            $('#wins').DataTable(table_init);

            $('#game-history-vertical-select').change(event => {
                const vertical = event.target.value;

                const url = new URL(window.location);
                url.searchParams.set('vertical', vertical);
                window.history.replaceState({}, '', url);

                const ajaxUrl = `/admin2/userprofile/${user}/game-history/?vertical=${vertical}`;

                $('#wins').DataTable().ajax.url(ajaxUrl).load();
                $('#bets').DataTable().ajax.url(ajaxUrl).load();
            })
        });

    </script>
@endsection
