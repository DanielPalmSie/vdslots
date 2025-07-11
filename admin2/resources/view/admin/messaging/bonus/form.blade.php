@extends('admin.layout')

@section('content')
    @include('admin.bonus.partials.topmenu')
    <div class="card card-solid card-primary">
        <div class="card-header">
            <h3 class="card-title">Create New Bonus Type</h3>
            <div class="float-right">
                <a href="{{ $app['url_generator']->generate('bonuses.new') }}"><i class="fa fa-file-o"></i> Add new bonus type</a>
                <a href="{{ $app['url_generator']->generate('bonuses.index') }}"><i class="fa fa-arrow-left"></i> Back to the list</a>
            </div>
        </div>
        <div class="card-body">
            <form id="bonus-type-form" method="post">
                <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                <div class="col-4 col-lg-4">
                    <div class="form-group">
                        <label for="expire_time">Expire time</label>
                        <span data-toggle="tooltip" title="The time at which point the bonus stops being considered, applies to bust bonuses and deposit bonuses." class="badge bg-light-blue">?</span>
                        <input name="expire_time" id="expire_time" type="date" class="form-control" data-placeholder="Select date">
                    </div>
                    <div class="form-group">
                        <label for="num_days">Number of days</label>
                        <span data-toggle="tooltip" title="Controls when a bonus entry should expire, it is the current date when the bonus is activated + num days." class="badge bg-light-blue">?</span>
                        <input type="number" name="num_days" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="cost">Cost in cents - needs to be turned over</label>
                        <span data-toggle="tooltip" title=" This is the amount that is to be turned over." class="badge bg-light-blue">?</span>
                        <input type="number" name="cost" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="reward">Reward</label>
                        <span data-toggle="tooltip" title="The amount of money (in cents) to be payed out, also the amount of money the bonus balance starts with. In case of non-bust bonuses the reward will in the end be the bonus balance which might be bigger or smaller than the actual reward value." class="badge bg-light-blue">?</span>
                        <input type="number" name="reward" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="bonus_name">Bonus name</label>
                        <span data-toggle="tooltip" title="The name of the bonus, is used in various places as a label." class="badge bg-light-blue">?</span>
                        <input type="text" name="bonus_name" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="deposit_limit">Deposit limit</label>
                        <span data-toggle="tooltip" title="In effect the Max Payout. Controls how much money (in cents) the up to value will be in '50% up to 100'", if it is a deposit bonus." class="badge bg-light-blue">?</span>
                        <input type="number" name="deposit_limit" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="rake_percent">Rake percent</label>
                        <span data-toggle="tooltip" title="Controls the amount of money that needs to be turned over for deposit bonuses. For example: 300 will result in a cost value of 300 if the reward ends up being 100." class="badge bg-light-blue">?</span>
                        <input type="number" name="cost" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="bonus_code">Bonus code</label>
                        <span data-toggle="tooltip" title="Controls if the bonus should apply to a given player by way of which affiliate the user is tagged to, applies to deposit and bust bonuses." class="badge bg-light-blue">?</span>
                        <input type="text" name="bonus_code" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="deposit_multiplier">Deposit multiplier</label>
                        <span data-toggle="tooltip" title="Controls the reward amount for deposit bonuses, if set to 2 and the deposit is 100 then the reward will be 200." class="badge bg-light-blue">?</span>
                        <input type="text" name="deposit_multiplier" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="bonus_type">Bonus type</label>
                        <span data-toggle="tooltip" title="" class="badge bg-light-blue">?</span>
                        <select name="bonus_type" class="form-control select2-class" data-placeholder="Select bonus type">
                            <option></option>
                            <option value="casino">Casino</option>
                            <option value="casinowager">Casino Wager</option>
                            <option value="freespin">Free Spin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="exclusive">Exclusive</label>
                        <span data-toggle="tooltip" title="A bonus can not be added if there already is one exlusive bonus active. This value needs to be set to 1 or 2 to avoid exclusivity! Avalue of 2 makes it possible to add and active the bonus again and again if prior entries have failed or has been approved. A value of 3 will enable the bonus to exist at the same time as exclusives, but it can't be reactivated." class="badge bg-light-blue">?</span>
                        <select name="exclusive" class="form-control select2-class" data-placeholder="Select exclusive">
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button class="btn btn-info" id="save-form-btn">Save</button>
                    </div>
                </div>
                <div class="col-4 col-lg-4">
                    <div class="form-group">
                        <label for="bonus_tag">Bonus tag</label>
                        <span data-toggle="tooltip" title="If freespin bonus set the network name here, BSG, Microgaming ..." class="badge bg-light-blue">?</span>
                        <select name="bonus_tag" class="form-control select2-class" data-placeholder="Select bonus tag">
                            <option value="bsg"></option>
                            @foreach($bonus_tags as $bonus_tag)
                                <option value="{{ $bonus_tag }}">{{ ucfirst($bonus_tag) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="game_tags">Game tags</label>
                        <span data-toggle="tooltip" title="Controls which games (by way of the game tag) the bonus applies to, game play on other type will not affect the bonus leave empty for all games. Game reference names can also be mixed with game tags or used standalone of course, use for instance videoslots,mgs_americanroulette to allow the videoslots category and the American Roulette game." class="badge bg-light-blue">?</span>
                        <input type="text" name="game_tags" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="game_percents">Game percents</label>
                        <span data-toggle="tooltip" title="Has to correspond to Game tags, each tag needs a percent value, if Game tags is set to 'videoslots,blackjack' then this one needs to be set to '1,0.1' for videoslots games to generate 100% turnover towards the Cost and blackjack games 10%." class="badge bg-light-blue">?</span>
                        <input type="text" name="game_percents" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="cash_percentage">Cash percentage</label>
                        <span data-toggle="tooltip" title="Only makes sense when creating a bonus balance bonus, controls how much of the turnover needs to be through actual cash play and not with bonus money. Set to for instance 50 for 50%." class="badge bg-light-blue">?</span>
                        <input type="number" name="cash_percentage" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="max_payout">Max payout</label>
                        <span data-toggle="tooltip" title="Controls how much money should be rewarded (in cents) when the bonus is completed, if set to zero (default) the bonus balance will be rewarded, if non-zero then this value will be rewarded if the bonus balance is bigger than this value." class="badge bg-light-blue">?</span>
                        <input type="number" name="max_payout" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="reload_code">Reload code</label>
                        <span data-toggle="tooltip" title="This code needs to be entered properly by the player during the deposit process, if the entered code matches the reload code the reload bonus will be activated and work in the same way as a deposit bonus." class="badge bg-light-blue">?</span>
                        <input type="text" name="reload_code" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="excluded_countries">Excluded countries</label>
                        <span data-toggle="tooltip" title="Enter for instance se pl uk, that is the 2 letter iso code with a space between each country code to block people from those countries to redeem vouchers connected to this bonus, does not work on deposit bonuses atm!" class="badge bg-light-blue">?</span>
                        <input type="text" name="excluded_countries" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="included_countries">Included countries</label>
                        <span data-toggle="tooltip" title="Enter for instance se pl uk, that is the 2 letter iso code with a space between each country code" class="badge bg-light-blue">?</span>
                        <input type="text" name="included_countries" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="fail_limit">Fail limit</label>
                        <span data-toggle="tooltip" title="Fail limit" class="badge bg-light-blue">?</span>
                        <input type="number" name="fail_limit" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="deposit_amount">Deposit amount</label>
                        <span data-toggle="tooltip" title="If not empty it will be a requirement that the player should have deposited this amount of cents to be able to activate the bonus through a voucher." class="badge bg-light-blue">?</span>
                        <input type="number" name="deposit_amount" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="deposit_max_bet_percent">Deposit max bet percent</label>
                        <span data-toggle="tooltip" title="If not empty is max bet as a percentage of deposit (if depositbonus). If set to 0.1 and the deposit is 100 and a bet of 11 is registered the bonus will fail." class="badge bg-light-blue">?</span>
                        <input type="number" name="deposit_max_bet_percent" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="bonus_max_bet_percent">Bonus max bet percent</label>
                        <span data-toggle="tooltip" title="If not empty is max bet as a percentage of the bonus reward. If set to 0.1 and the reward is 100 and a bet of 11 is registered the bonus will fail." class="badge bg-light-blue">?</span>
                        <input type="number" name="bonus_max_bet_percent" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="max_bet_amount">Max bet amount</label>
                        <span data-toggle="tooltip" title="If not empty it is the absolute amount in cents that can happen when the bonus is active, a bet higher than that will result in a failed bonus." class="badge bg-light-blue">?</span>
                        <input type="number" name="max_bet_amount" class="form-control">
                    </div>
                </div>
                <div class="col-4 col-lg-4">
                    <div class="form-group">
                        <label for="loyalty_percent">Loyalty percent</label>
                        <span data-toggle="tooltip" title="Set to for instance 0.5 if you want the bonus to generate 50% of the wager turnover towards the normal Weekend Booster, result: X wager * 0.5 * 0.01 = actual cashback." class="badge bg-light-blue">?</span>
                        <input type="number" name="loyalty_percent" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="top_up">Top up</label>
                        <span data-toggle="tooltip" title="Applies to reload bonuses, if set to bigger than 0 it will increase the player's cash balance with the amount in cents (generates a cash transaction type 14), it will not affect the bonus in any other way." class="badge bg-light-blue">?</span>
                        <input type="number" name="top_up" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="stagger_percent">Stagger percent</label>
                        <span data-toggle="tooltip" title="Applies to casino wager bonuses. When set to for instance 0.1 then when 10% of the revenue (cost) goal has been reached 10% of the reward is paid out and so on. Set to 0 for a one time full payout of the reward when the turnover goal has been reached." class="badge bg-light-blue">?</span>
                        <input type="number" name="stagger_percent" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="ext_ids">Ext ids</label>
                        <span data-toggle="tooltip" title="External ids" class="badge bg-light-blue">?</span>
                        <input type="text" name="ext_ids" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="progress_type">Progress type</label>
                        <span data-toggle="tooltip" title="Default is both, if set to cash then the bonus will only progress when real money is turned over, if set to bonus then it will only progress when bonus money is being turned over." class="badge bg-light-blue">?</span>
                        <select name="progress_type" class="form-control select2-class" data-placeholder="Select progress type">
                            <option></option>
                            <option value="cash">Cash</option>
                            <option value="bonus">Bonus</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="deposit_threshold">Deposit Threshold</label>
                        <span data-toggle="tooltip" title="Deposit Threshold" class="badge bg-light-blue">?</span>
                        <input type="number" name="deposit_threshold" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="game_id">Game id</label>
                        <span data-toggle="tooltip" title="Game id" class="badge bg-light-blue">?</span>
                        <input type="text" name="game_id" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="allow_race">Allow race</label>
                        <span data-toggle="tooltip" title="Set to 0 (default) to disallow or 1 to allow progress to happen in a casino race while this bonus is active, note that progress doesn't happen if 2 bonuses are active at the same time and one of them has allow set to 0. This only works with the new realtime casino race logic!" class="badge bg-light-blue">?</span>
                        <select name="allow_race" class="form-control select2-class" data-placeholder="Select allow race">
                            <option></option>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="frb_coins">FRB coins</label>
                        <span data-toggle="tooltip" title="" class="badge bg-light-blue">?</span>
                        <input type="number" name="frb_coins" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="frb_denomination">FRB denomination</label>
                        <span data-toggle="tooltip" title="" class="badge bg-light-blue">?</span>
                        <input type="number" name="frb_denomination" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="frb_lines">FRB lines</label>
                        <span data-toggle="tooltip" title="" class="badge bg-light-blue">?</span>
                        <input type="number" name="frb_lines" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="frb_cost">FRB cost</label>
                        <span data-toggle="tooltip" title="" class="badge bg-light-blue">?</span>
                        <input type="number" name="frb_cost" class="form-control">
                    </div>
                </div>
                <input type="hidden" name="type" value="casino">
            </form>
        </div>
    </div>
    <div id="errorModal" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Error</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                </div>
                <div class="modal-body">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $('#bonus-type-form select').select2()
            $('#bonus-type-form select[name=bonus_type]').on('change', function(e){
                if(this.value == 'casino'){
                    $('#bonus-type-form input[name=frb_coins]').prop('disabled', true);
                    $('#bonus-type-form input[name=frb_denomination]').prop('disabled', true);
                    $('#bonus-type-form input[name=frb_cost]').prop('disabled', true);
                    $('#bonus-type-form input[name=frb_lines]').prop('disabled', true);
                } else {
                    $('#bonus-type-form input[name=frb_coins]').prop('disabled', false);
                    $('#bonus-type-form input[name=frb_denomination]').prop('disabled', false);
                    $('#bonus-type-form input[name=frb_cost]').prop('disabled', false);
                    $('#bonus-type-form input[name=frb_lines]').prop('disabled', false);
                }
                if(this.value == 'casinowager'){
                    $('#bonus-type-form input[name=cash_percentage]').prop('disabled', false);
                } else {
                    $('#bonus-type-form input[name=cash_percentage]').prop('disabled', true);
                }
            });
            $('#save-form-btn').on('click', function(e){
                e.preventDefault();

                var bonus_name = $('#bonus-type-form input[name=bonus_name]').val();
                var game_tags = $('#bonus-type-form input[name=game_tags]').val();
                var game_percents = $('#bonus-type-form input[name=game_percents]').val();
                if(!bonus_name){
                    modalErrorMessage('Bonus name is missing!');
                    return false;
                }
                // game tags check vs. game percents
                var game_tags_array = game_tags.split(',');
                var game_percents_array = game_percents.split(',');
                if(game_tags_array.length < 1 || game_percents_array.length < 1 || game_tags_array.length != game_percents_array.length){
                    modalErrorMessage('Game tags and game percents should contain same amount of comma separated values!');
                    return false;
                }
                for(i = 0; i<game_percents_array.length; i++){
                    var item = game_percents_array[i];
                    if(parseFloat(item) != NaN){
                        if(parseFloat(item) < 0 || parseFloat(item) > 1){
                            modalErrorMessage('Check game percent values, it can contain 0 <= x <= 1 numbers and frb');
                            return false;
                        }
                    } else if(item != 'rtp'){
                        modalErrorMessage('2 Check game percent values, it can contain 0 <= x <= 1 numbers and frb');
                        return false;
                    }
                }
                $('#bonus-type-form').submit();
            });
        });

        function modalErrorMessage(message){
            $(".modal-body").text(message);
            $('#errorModal').modal('show');
            return false;
        };

    </script>
@endsection