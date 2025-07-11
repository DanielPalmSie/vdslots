@if(empty($query_data['chrono']))
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Casino Wins</h3>
            <span class="float-right">@include('admin.user.betsandwins.partials.download-button')</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="user-wins-datatable"
                       class="table table-striped table-bordered dt-responsive" cellspacing="0"
                       width="100%">
                    <thead>
                    <tr>
                        <th>Win ID</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Amount ({{ $user->currency }})</th>
                        <th>Game</th>
                        <th>Balance ({{ $user->currency }})</th>
                        <th>Bonus Bet</th>
                        <th>Booster Amount</th>
                        <th>ID</th>
                        <th>Transaction ID</th>
                        <th>Trans type</th>
                    </tr>
                    </thead>
                    <tbody>
                        <?php $wins_sum = 0 ?>
                    @foreach($wins as $win)
                            <?php $wins_sum += $win->amount ?>
                        <tr>
                            <td>{{ $win->id }}</td>
                            <td>Win</td>
                            <td>{{ $win->created_at }}</td>
                            <td>{{ $win->amount / 100 }}</td>
                            <td>{{ empty($win->game_name) ? $win->game_ref : $win->game_name }}</td>
                            <td>{{ $win->balance / 100 }}</td>
                            <td>{{ ($win->bonus_bet) ? 'Yes' : 'No' }}</td>
                            <td>{{ abs($win->transferred_to_vault / 100) }}</td>
                            <td>{{ $win->mg_id }}</td>
                            <td>{{ $win->trans_id }}</td>
                            <td>{{ \App\Helpers\DataFormatHelper::getWinType($win->award_type) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td>{{ \App\Helpers\DataFormatHelper::nf($wins_sum) }}</td>
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
