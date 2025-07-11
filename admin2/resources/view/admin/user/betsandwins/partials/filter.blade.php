<form method="get">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-6 col-lg-2">
                    <label>From</label>
                    <input autocomplete="off" data-provide="datepicker" data-date-format="yyyy-mm-dd" type="text" name="start_date"
                           class="form-control datepicker" placeholder="Start date"
                           value="{{ !empty($query_data['start_date']) ? \Carbon\Carbon::parse($query_data['start_date'])->format('Y-m-d') : null }}">
                </div>
                <div class="col-6 col-lg-2">
                    <label>To</label>
                    <input autocomplete="off" data-provide="datepicker" data-date-format="yyyy-mm-dd" type="text" name="end_date"
                           class="form-control datepicker" placeholder="End date"
                           value="{{ !empty($query_data['end_date']) ? \Carbon\Carbon::parse($query_data['end_date'])->format('Y-m-d') : null }}">
                </div>
                <div class="col-6 col-lg-2">
                    <label for="select-bonus">Bonus Bets</label>
                    <select name="bonus" id="select-bonus" class="form-control">
                        <option {{ $query_data['bonus'] == 'all' ? 'selected' : '' }} value="all">All</option>
                        <option {{ $query_data['bonus'] == 'yes' ? 'selected' : '' }} value="yes">Yes</option>
                        <option {{ $query_data['bonus'] == 'no' ? 'selected' : '' }} value="no">No</option>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <div class="form-group">
                        <label for="select-games">Game</label>
                        <select name="game" id="select-games" class="form-control select2-games"
                                style="width: 100%;" data-placeholder="Select a game">
                            <option value="all">See all the games</option>
                            @foreach($games as $game)
                                <option value="{{ $game->game_id }}">{{ $game->game_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <label for="select-order">Order By</label>
                    <select name="order" id="select-order" class="form-control">
                        <option {{ $query_data['order'] == 'ASC' ? 'selected' : '' }}  value="ASC">Date ascending</option>
                        <option {{ $query_data['order'] == 'DESC' ? 'selected' : '' }} value="DESC">Date descending</option>
                    </select>
                </div>

            </div>
        </div><!-- /.box-body -->
        <div class="card-footer">
            <button formaction="{{ $app['url_generator']->generate('admin.user-betsandwins-bets', ['user' => $user->id]) }}"
                    class="btn btn-info">Search Bets
            </button>
            <button formaction="{{ $app['url_generator']->generate('admin.user-betsandwins-wins', ['user' => $user->id]) }}"
                    class="btn btn-info">Search Wins
            </button>
            <button formaction="{{ $app['url_generator']->generate('admin.user-betsandwins-transactions', ['user' => $user->id]) }}"
                    class="btn btn-info">Search Transactions
            </button>
        </div><!-- /.box-footer-->
    </div>
</form>

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $(".select2-games").select2().val('{{ $query_data['game'] }}').change();
        });
    </script>

@endsection
