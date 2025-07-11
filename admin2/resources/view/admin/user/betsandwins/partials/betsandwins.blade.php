@if(!empty($query_data['chrono']))
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">
                Bets and wins
            </h3>
            <span class="float-right">@include('admin.user.betsandwins.partials.download-button')</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="user-betsandwins-datatable"
                       class="table table-striped table-bordered dt-responsive" cellspacing="0"
                       width="100%">
                    <thead>
                    <tr>
                        <th>Bet/Win ID</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Amount ({{ $user->currency }})</th>
                        <th>Game</th>
                        <th>Balance ({{ $user->currency }})</th>
                        <th>Bonus Bet</th>
                        <th>ID</th>
                        <th>Transaction ID</th>
                        <th>Trans type</th>
                    </tr>
                    </thead>
                    <tbody>
                        <?php $bets_and_wins_sum = 0 ?>
                    @foreach($bets_and_wins as $bet_or_win)
                            <?php $bets_and_wins_sum += $bet_or_win->amount ?>
                        <tr>
                            <td>{{ $bet_or_win->id }}</td>
                            <td>{{ $bet_or_win->type }}</td>
                            <td>{{ $bet_or_win->created_at }}</td>
                            <td>{{ $bet_or_win->amount / 100 }}</td>
                            <td>{{ empty($bet_or_win->game_name) ? $bet_or_win->game_ref : $bet_or_win->game_name }}</td>
                            <td>{{ $bet_or_win->balance / 100 }}</td>
                            <td>{{ ($bet_or_win->bonus_bet) ? 'Yes' : 'No' }}</td>
                            <td>{{ $bet_or_win->mg_id }}</td>
                            <td>{{ $bet_or_win->trans_id }}</td>
                            <td>{{ empty($bet_or_win->award_type) ? null : \App\Helpers\DataFormatHelper::getWinType($bet_or_win->award_type) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td>{{ \App\Helpers\DataFormatHelper::nf($bets_and_wins_sum) }}</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endif
