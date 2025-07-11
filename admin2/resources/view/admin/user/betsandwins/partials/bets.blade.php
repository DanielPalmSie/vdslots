@if(empty($query_data['chrono']))
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Casino Bets</h3>
            <span class="float-right">
                @include('admin.user.betsandwins.partials.download-button')
            </span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="user-bets-datatable"
                       class="table table-striped table-bordered dt-responsive" cellspacing="0"
                       width="100%">
                    <thead>
                    <tr>
                        <th>Bet ID</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Amount ({{ $user->currency }})</th>
                        <th>Game</th>
                        <th>Balance ({{ $user->currency }})</th>
                        <th>Bonus Bet</th>
                        <th>ID</th>
                        <th>Transaction ID</th>
                        <th>Boosted RTP (c)</th>
                        <th>Jackpot Contrib.</th>
                    </tr>
                    </thead>
                    <tbody>
                        <?php $bets_sum = 0 ?>
                    @foreach($bets as $bet)
                            <?php $bets_sum += $bet->amount ?>
                        <tr>
                            <td>{{ $bet->id }}</td>
                            <td>Bet</td>
                            <td>{{ $bet->created_at }}</td>
                            <td>{{ $bet->amount / 100 }}</td>
                            <td>{{ empty($bet->game_name) ? $bet->game_ref : $bet->game_name }}</td>
                            <td>{{ $bet->balance / 100 }}</td>
                            <td>{{ ($bet->bonus_bet) ? 'Yes' : 'No' }}</td>
                            <td>{{ $bet->mg_id }}</td>
                            <td>{{ $bet->trans_id }}</td>
                            <td>{{ $bet->loyalty }}</td>
                            <td>{{ empty($bet->jp_contrib) ? 0 : $bet->jp_contrib }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td>{{ \App\Helpers\DataFormatHelper::nf($bets_sum) }}</td>
                        <td></td>
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
