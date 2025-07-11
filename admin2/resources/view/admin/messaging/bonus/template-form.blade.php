<style>
    .select2-class {
        width: 100%;
    }
    .clear-fix {
        clear: both;
    }
    .bottom-margin {
        margin-bottom: 10px;
        margin-top: 10px;
    }
</style>
<div class="card">
    <div class="card-header with-border">
        <h3 class="card-title">Step 2 - Edit template</h3>
    </div>
    <div class="card-body">
        <form id="bonus-type-form" method="post">
            <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
            <div class="col-12 col-lg-4">
                <div class="form-group">
                    <label for="template_name">Template name</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle" title=""></i>
                    <input type="text" name="template_name" class="form-control" value="{{ $bonus_type->template_name }}" placeholder="Template name" required>
                </div>
                <div class="form-group">
                    <label for="bonus_name">Bonus name</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle" title="The name of the bonus, is used in various places as a label."></i>
                    <input type="text" name="bonus_name" class="form-control" value="{{ $bonus_type->bonus_name }}">
                </div>
                <div class="form-group">
                    <label for="code_type">Code type</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle" title=""></i>
                    <select name="code_type" id="code-type-select" class="form-control select2-class" data-allow-clear="true" data-placeholder="Select code type">
                        <option></option>
                        <option value="bonus">Use bonus code</option>
                        <option value="reload">Use reload code</option>
                    </select>
                </div>
                <div class="form-group" id="code-group">
                    <label for="code">Code</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle"></i>
                    <input type="text" name="code" class="form-control" disabled value="No code selected">
                </div>
                <div class="form-group" id="bonus-code-group d-none">
                    <label for="bonus_code">Bonus code (<a href="javascript:modalErrorMessage('Format example: VS@{{date|dmy}} generates codes like VS120417')">See example</a>)</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle"
                       title="Controls if the bonus should apply to a given player by way of which affiliate the user is tagged to, applies to deposit and bust bonuses."></i>
                    <input type="text" name="bonus_code" class="form-control" placeholder="Example: VS100@{{date|W}}" value="{{ $bonus_type->bonus_code }}">
                </div>
                <div class="form-group" id="reload-code-group d-none">
                    <label for="reload_code">Reload code (<a href="javascript:modalErrorMessage('Format example: VS100@{{date|W}}.')">See example</a>)</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle"
                       title="This code needs to be entered properly by the player during the deposit process, if the entered code matches the reload code the reload bonus will be activated and work in the same way as a deposit bonus."></i>
                    <input type="text" name="reload_code" class="form-control" value="{{ $bonus_type->reload_code }}">
                </div>
                <div class="form-group">
                    <label for="expire_time">Expire time (<a id="expire-examples-link" href="javascript:modalErrorMessage('Format example: +7 day, +2 month, +3 year, tomorrow, yesterday, next wednesday, last friday or this thursday.')">See examples</a>)</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle"
                          title="The time at which point the bonus stops being considered, applies to bust bonuses and deposit bonuses."></i>
                    <input name="expire_time" id="expire_time" type="text" class="form-control" data-placeholder="Select expire time" value="+7 day">
                </div>
                <div class="form-group">
                    <label for="num_days">Number of days</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle"
                          title="Controls when a bonus entry should expire, it is the current date when the bonus is activated + num days."></i>
                    <input type="number" min="0"name="num_days" class="form-control" value="{{ $bonus_type->num_days }}">
                </div>
                <div class="form-group">
                    <label for="cost">Cost in cents - needs to be turned over</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle" title=" This is the amount that is to be turned over."></i>
                    <input type="number" min="0"name="cost" class="form-control" value="{{ $bonus_type->cost }}">
                </div>
                <div class="form-group">
                    <label for="reward">Reward</label>
                    <i data-toggle="tooltip" data-placement="right"
                          title="The amount of money (in cents) to be payed out, also the amount of money the bonus balance starts with. In case of non-bust bonuses the reward will in the end be the bonus balance which might be bigger or smaller than the actual reward value."
                          class="fa fa-info-circle"></i>
                    <input type="number" min="0"name="reward" class="form-control" value="{{ $bonus_type->reward }}">
                </div>
                <div class="form-group">
                    <label for="deposit_limit">Deposit limit</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle"
                          title="In effect the Max Payout. Controls how much money (in cents) the up to value will be in '50% up to 100', if it is a deposit bonus."></i>
                    <input type="number" min="0"name="deposit_limit" class="form-control"
                           value="{{ $bonus_type->deposit_limit }}">
                </div>
                <div class="form-group">
                    <label for="rake_percent">Rake percent</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle"
                          title="Controls the amount of money that needs to be turned over for deposit bonuses. For example: 300 will result in a cost value of 300 if the reward ends up being 100."></i>
                    <input type="number" min="0"name="rake_percent" class="form-control"
                           value="{{ $bonus_type->rake_percent }}">
                </div>
                <div class="form-group">
                    <label for="deposit_multiplier">Deposit multiplier</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle"
                          title="Controls the reward amount for deposit bonuses, if set to 2 and the deposit is 100 then the reward will be 200."></i>
                    <input type="text" name="deposit_multiplier" class="form-control"
                           value="{{ $bonus_type->deposit_multiplier }}">
                </div>
                <div class="form-group">
                    <label for="bonus_type">Bonus type</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle" title=""></i>
                    <select name="bonus_type" class="form-control select2-class" data-placeholder="Select bonus type">
                        <option></option>
                        <option value="casino">Casino</option>
                        <option value="casinowager">Casinowager</option>
                        <option value="freespin">Freespin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="type">Type</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle" title=""></i>
                    <select name="type" class="form-control select2-class" data-placeholder="Select bonus type">
                        <option></option>
                        <option value="affiliates">Affiliates</option>
                        <option value="casino">Casino</option>
                        <option value="normal">Normal</option>
                        <option value="reward">Reward</option>
                        <option value="VIP">VIP</option>
                    </select>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="form-group">
                    <label for="exclusive">Exclusive</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle"
                       title="A bonus can not be added if there already is one exlusive bonus active. This value needs to be set to 1 or 2 to avoid exclusivity! Avalue of 2 makes it possible to add and active the bonus again and again if prior entries have failed or has been approved. A value of 3 will enable the bonus to exist at the same time as exclusives, but it can't be reactivated."></i>
                    <select name="exclusive" class="form-control select2-class" data-placeholder="Select exclusive">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="bonus_tag">Bonus tag</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle"
                          title="If freespin bonus set the network name here, BSG, Microgaming ..."></i>
                    <select name="bonus_tag" class="form-control select2-class" data-placeholder="Select bonus tag">
                        @foreach($bonus_tags as $bonus_tag)
                            <option value="{{ $bonus_tag }}">{{ ucfirst($bonus_tag) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="game_tags">Game tags</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle"
                          title="Controls which games (by way of the game tag) the bonus applies to, game play on other type will not affect the bonus leave empty for all games. Game reference names can also be mixed with game tags or used standalone of course, use for instance videoslots,mgs_americanroulette to allow the videoslots category and the American Roulette game."></i>
                    <input type="text" name="game_tags" class="form-control" value="{{ $bonus_type->game_tags }}">
                </div>
                <div class="form-group">
                    <label for="game_percents">Game percents</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle"
                          title="Has to correspond to Game tags, each tag needs a percent value, if Game tags is set to 'videoslots,blackjack' then this one needs to be set to '1,0.1' for videoslots games to generate 100% turnover towards the Cost and blackjack games 10%."></i>
                    <input type="text" name="game_percents" class="form-control"
                           value="{{ $bonus_type->game_percents }}">
                </div>
                <div class="form-group">
                    <label for="cash_percentage">Cash percentage</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle"
                          title="Only makes sense when creating a bonus balance bonus, controls how much of the turnover needs to be through actual cash play and not with bonus money. Set to for instance 50 for 50%."></i>
                    <input type="number" min="0"name="cash_percentage" class="form-control"
                           value="{{ $bonus_type->cash_percentage }}">
                </div>
                <div class="form-group">
                    <label for="max_payout">Max payout</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle"
                          title="Controls how much money should be rewarded (in cents) when the bonus is completed, if set to zero (default) the bonus balance will be rewarded, if non-zero then this value will be rewarded if the bonus balance is bigger than this value."></i>
                    <input type="number" min="0"name="max_payout" class="form-control" value="{{ $bonus_type->max_payout }}">
                </div>
                <div class="form-group">
                    <label for="excluded_countries">Excluded countries</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle"
                          title="Enter for instance se pl uk, that is the 2 letter iso code with a space between each country code to block people from those countries to redeem vouchers connected to this bonus, does not work on deposit bonuses atm!"></i>
                    <select name="excluded_countries[]" id="select-excluded_countries" class="form-control select2-class" multiple="multiple" data-placeholder="Select one or multiple" data-allow-clear="true">
                        @foreach(\App\Helpers\DataFormatHelper::getCountryList() as $country)
                            <option value="{{ $country['iso'] }}"> {{ $country['printable_name'] }} </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="included_countries">Included countries</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle"
                          title="Enter for instance se pl uk, that is the 2 letter iso code with a space between each country code"></i>

                    <select name="included_countries[]" id="select-included_countries" class="form-control select2-class" multiple="multiple" data-placeholder="Select one or multiple" data-allow-clear="true">
                        @foreach(\App\Helpers\DataFormatHelper::getCountryList() as $country)
                            <option value="{{ $country['iso'] }}"> {{ $country['printable_name'] }} </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="fail_limit">Fail limit</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle" title="Fail limit"></i>
                    <input type="number" min="0"name="fail_limit" class="form-control" value="{{ $bonus_type->fail_limit }}">
                </div>
                <div class="form-group">
                    <label for="deposit_amount">Deposit amount</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle"
                          title="If not empty it will be a requirement that the player should have deposited this amount of cents to be able to activate the bonus through a voucher."></i>
                    <input type="number" min="0"name="deposit_amount" class="form-control"
                           value="{{ $bonus_type->deposit_amount }}">
                </div>
                <div class="form-group">
                    <label for="deposit_max_bet_percent">Deposit max bet percent</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle"
                          title="If not empty is max bet as a percentage of deposit (if depositbonus). If set to 0.1 and the deposit is 100 and a bet of 11 is registered the bonus will fail."></i>
                    <input type="number" min="0"name="deposit_max_bet_percent" class="form-control"
                           value="{{ $bonus_type->deposit_max_bet_percent }}">
                </div>
                <div class="form-group">
                    <label for="bonus_max_bet_percent">Bonus max bet percent</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle"
                          title="If not empty is max bet as a percentage of the bonus reward. If set to 0.1 and the reward is 100 and a bet of 11 is registered the bonus will fail."></i>
                    <input type="number" min="0"name="bonus_max_bet_percent" class="form-control"
                           value="{{ $bonus_type->bonus_max_bet_percent }}">
                </div>
                <div class="form-group">
                    <label for="max_bet_amount">Max bet amount</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle"
                          title="If not empty it is the absolute amount in cents that can happen when the bonus is active, a bet higher than that will result in a failed bonus."></i>
                    <input type="number" min="0"name="max_bet_amount" class="form-control"
                           value="{{ $bonus_type->max_bet_amount }}">
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="form-group">
                    <label for="loyalty_percent">Loyalty percent</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle"
                          title="Set to for instance 0.5 if you want the bonus to generate 50% of the wager turnover towards the normal Weekend Booster, result: X wager * 0.5 * 0.01 = actual Weekend Booster."></i>
                    <input type="number" min="0"name="loyalty_percent" class="form-control"
                           value="{{ $bonus_type->loyalty_percent }}">
                </div>
                <div class="form-group">
                    <label for="top_up">Top up</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle"
                          title="Applies to reload bonuses, if set to bigger than 0 it will increase the player's cash balance with the amount in cents (generates a cash transaction type 14), it will not affect the bonus in any other way."></i>
                    <input type="number" min="0"name="top_up" class="form-control" value="{{ $bonus_type->top_up }}">
                </div>
                <div class="form-group">
                    <label for="stagger_percent">Stagger percent</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle"
                          title="Applies to casino wager bonuses. When set to for instance 0.1 then when 10% of the revenue (cost) goal has been reached 10% of the reward is paid out and so on. Set to 0 for a one time full payout of the reward when the turnover goal has been reached."></i>
                    <input type="number" min="0"name="stagger_percent" class="form-control"
                           value="{{ $bonus_type->stagger_percent }}">
                </div>
                <div class="form-group">
                    <label for="ext_ids">Ext ids</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle" title="External ids"></i>
                    <select name="ext_ids[]" id="select-ext_ids" class="form-control select2-class"  data-placeholder="  Select one or multiple" data-allow-clear="true" multiple="multiple">
                        @foreach(\App\Helpers\DataFormatHelper::getExternalGameNameList() as $ext_id)
                            <option value="{{ $ext_id['ext_game_name'] }}"> {{ $ext_id['ext_game_name'] }} </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="progress_type">Progress type</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle"
                          title="Default is both, if set to cash then the bonus will only progress when real money is turned over, if set to bonus then it will only progress when bonus money is being turned over."></i>
                    <select name="progress_type" class="form-control select2-class" data-placeholder="Select progress type">
                        <option></option>
                        <option value="cash">Cash</option>
                        <option value="bonus">Bonus</option>
                        <option value="both">Both</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="deposit_threshold">Deposit Threshold</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle" title="Deposit Threshold"></i>
                    <input type="number" min="0"name="deposit_threshold" class="form-control"
                           value="{{ $bonus_type->deposit_threshold }}">
                </div>
                <div class="form-group">
                    <label for="game_id">Game id</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle" title="Game id"></i>
                    <select name="game_id[]" id="select-game_id" class="form-control select2-class"  data-placeholder="  Select one or multiple" data-allow-clear="true" multiple="multiple">
                        @foreach(\App\Helpers\DataFormatHelper::getGameIdList() as $ext_id)
                            <option value="{{ $ext_id['game_id'] }}"> {{ $ext_id['game_id'] }} </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="allow_race">Allow race</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle"
                          title="Set to 0 (default) to disallow or 1 to allow progress to happen in a casino race while this bonus is active, note that progress doesn't happen if 2 bonuses are active at the same time and one of them has allow set to 0. This only works with the new realtime casino race logic!"></i>
                    <select name="allow_race" class="form-control select2-class" data-placeholder="Select allow race">
                        <option></option>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="frb_coins">FRB coins</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle" title=""></i>
                    <input type="number" min="0"name="frb_coins" class="form-control" value="{{ $bonus_type->frb_coins }}">
                </div>
                <div class="form-group">
                    <label for="frb_denomination">FRB denomination</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle" title=""></i>
                    <input type="number" min="0"name="frb_denomination" class="form-control"
                           value="{{ $bonus_type->frb_denomination }}">
                </div>
                <div class="form-group">
                    <label for="frb_lines">FRB lines</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle" title=""></i>
                    <input type="number" min="0"name="frb_lines" class="form-control" value="{{ $bonus_type->frb_lines }}">
                </div>
                <div class="form-group">
                    <label for="frb_cost">FRB cost</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle" title=""></i>
                    <input type="number" min="0"name="frb_cost" class="form-control" value="{{ $bonus_type->frb_cost }}">
                </div>
                <div class="form-group">
                    <label for="keep_winnings">Keep Winnings</label>
                    <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle" title=""></i>
                    <select name="keep_winnings" class="form-control select2-class" data-placeholder="Select keep winnins">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                    </select>

                </div>
            </div>
            <input type="hidden" name="award_id" id="award-id-input" value="{{ $bonus_type->award_id }}">
            <input type="hidden" name="action"  value="{{$app['request_stack']->getCurrentRequest()->get('action')}}">
        </form>
    </div>
    <div class="card-footer">
        <div class="row">
            <div class="col-12 col-sm-12 col-md-6 col-lg-6">
                <div class="input-group">
                    <span class="input-group-addon"><b>Reward</b></span>
                    <input type="text" id="award-desc-input" class="form-control" placeholder=""
                           value="{{ !$reward ? 'No reward linked to this bonus' : $reward->description  }}" disabled>
                    <div class="input-group-btn">
                        <button type="button" id="see-rewards-link" class="btn btn-flat btn-info">Show rewards list</button>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-12 col-md-6 col-lg-6">
                <button class="btn btn-block btn-flat btn-info" id="save-form-btn">Save</button>
            </div>
        </div>
    </div>
    <div id="awards-ajax-box">

    </div>
</div>
<div id="errorModal" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Info</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<div id="keyValModal" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Info</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <div class="col-12 hidden form-item">
                    <div class="col-6">
                        <input type="text" class="form-control" data-type="tag" placeholder="Game tag">
                    </div>
                    <div class="col-1">
                        <p class="text-center">:</p>
                    </div>
                    <div class="col-5">
                        <input type="text" class="form-control" data-type="percent" placeholder="Game percent">
                    </div>
                </div>
                <div class="form"></div>
                <div class="clear-fix"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="addButton"> Add new row </button>
                <button type="button" class="btn btn-success" id="saveButton"> Save </button>
                <button type="button" class="btn btn-danger"  data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
    <script>
        $("#addButton").click(function() {
            $(".form").append($(".form-item").clone().removeClass('form-item').removeClass('hidden'));
        });
        $("#saveButton").click(function() {
            var map = {
                tags: $(".form [data-type='tag']").map(function($i, $el) {
                    return $($el).val();
                }),
                percents: $(".form [data-type='percent']").map(function($i, $el) {
                    return $($el).val();
                })
            };

            $("[name='game_tags']").attr('value', $.makeArray(map.tags).join(','));
            $("[name='game_percents']").attr('value', $.makeArray(map.percents).join(','));

            $('#keyValModal').modal('hide')
        });
    </script>
</div>

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script type="text/javascript">
        $form_item = $(".form-item").clone();

        $("#see-rewards-link").on('click', function(e) {
            e.preventDefault();
            var self = $(this);

            if (window.is_rewards_visible) {
                $("#award-desc-input").val('No reward linked to this bonus');
                $("#award-id-input").val('');
            } else {
                $.ajax({
                    url: "{{ $app['url_generator']->generate('messaging.rewards.list') }}",
                    type: "POST",
                    success: function (response, textStatus, jqXHR) {
                        self.html('-');
                        window.is_rewards_visible = true;

                        $("#awards-ajax-box").html(response['html']);
                        $("#award-list-databable").DataTable({
                            "pageLength": 25,
                            "language": {
                                "emptyTable": "No results found.",
                                "lengthMenu": "Display _MENU_ records per page"
                            },
                            "order": [[0, "desc"]],
                            "columnDefs": [{"targets": 4, "orderable": false, "searchable": false}]
                        });
                        $("[name='game_tags']").click(openKeyValModal);
                        $("[name='game_percents']").click(openKeyValModal);

                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert('AJAX ERROR');
                    }
                });
            }
        });

        $(document).ready(function() {
            var bonus_code = $('#bonus-code-group');
            var reload_code = $('#reload-code-group');
            var code = $('#code-group');

            window.is_rewards_visible = false;

            @if($reward)
                var rewardObj = {!! $reward !!};

                $("#award-desc-input").val(rewardObj['description']);
                $("#award-id-input").val(rewardObj['id']);
            @endif

            @if($bonus_type)
                var btObj = {!! $bonus_type !!};
                console.log(btObj);
                $("select[name='bonus_type']").select2().val(btObj['bonus_type']).trigger("change");
                $("select[name='type']").select2().val(btObj['type']).trigger("change");
                $("select[name='bonus_tag']").select2().val(btObj['bonus_tag']).trigger("change");
                $("select[name='progress_type']").select2().val(btObj['progress_type']).trigger("change");
                $("select[name='allow_race']").select2().val(btObj['allow_race']).trigger("change");
                $("select[name='exclusive']").select2().val(btObj['exclusive']).trigger("change");

                $("select[name='keep_winnings']").select2().val(btObj['keep_winnings'] ? btObj['keep_winnings'] : 0).trigger("change");

                var initial_type = '';
                if (btObj['reload_code']) {
                    initial_type = 'reload';
                } else if(btObj['bonus_code']) {
                    initial_type = 'bonus';
                }

                if (btObj['ext_ids'].length > 0)
                {
                    $("#select-ext_ids").select2().val(btObj['ext_ids']).trigger("change");
                }

                if (btObj['game_id'].length > 0)
                {
                    $("#select-game_id").select2().val(btObj['game_id']).trigger("change");
                }
                if (btObj['included_countries'].length > 0)
                {
                    $("#select-included_countries").select2().val(btObj['included_countries']).trigger("change");
                }
                if (btObj['excluded_countries'].length > 0)
                {
                    $("#select-excluded_countries").select2().val(btObj['excluded_countries']).trigger("change");
                }

                if (btObj['bonus_type'] != null)
                {
                    $('#bonus-type-form select[name=bonus_tag]').prop('disabled', btObj['bonus_type'] != 'freespin');
                }
        @endif

        $('#bonus-type-form select').select2();
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
                if(this.value != 'freespin'){
                    $('#bonus-type-form input[name=cash_percentage]').prop('disabled', false);
                    $('#bonus-type-form select[name=bonus_tag]').prop('disabled', true);
                } else {
                    $('#bonus-type-form input[name=cash_percentage]').prop('disabled', true);
                    $('#bonus-type-form select[name=bonus_tag]').prop('disabled', false);
                }
            });

            $('#code-type-select').select2({
                minimumResultsForSearch: -1
            }).val(initial_type).on('change', function(e){
                if ($(this).val() == 'bonus') {
                    bonus_code.show();
                    reload_code.hide();
                    code.hide();
                } else if ($(this).val() == 'reload') {
                    bonus_code.hide();
                    reload_code.show();
                    code.hide();
                } else {
                    bonus_code.hide();
                    reload_code.hide();
                    code.show();
                }
            }).change();



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

                $.ajax({
                    url: $('#bonus-type-form').attr('action'),
                    type: "POST",
                    data: $('#bonus-type-form').serialize(),
                    success: function (data, textStatus, jqXHR) {

                        if (data['error'] != 0)
                        {
                            displayNotifyMessage('danger', data['message']);
                        } else {
                            $('#bonus-type-form').submit();
                        }
                        $("[name='game_tags']").click(openKeyValModal);
                        $("[name='game_percents']").click(openKeyValModal);

                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert('AJAX ERROR');
                    }
                });

            });
        });

        function openKeyValModal() {
            var map = {
                tags: $("[name='game_tags']").val().split(','),
                percents: $("[name='game_percents']").val().split(',')
            };
            $("#keyValModal .form").html('').append(function() {
               return map.tags.map(function($el, $i) {
                   $new_item = $form_item.clone().removeClass('form-item').removeClass('hidden');
                   $new_item.find("[data-type='tag']").attr('value', map.tags[$i]);
                   $new_item.find("[data-type='percent']").attr('value', map.percents[$i]);
                   return $new_item[0].outerHTML;
               });
            }());

            $modal = $("#keyValModal").modal('show');
        }
        $("[name='game_tags']").click(openKeyValModal);
        $("[name='game_percents']").click(openKeyValModal);

        function modalErrorMessage(message) {
            $("#errorModal .modal-body").text(message);
            $('#errorModal').modal('show');
            return false;
        }

    </script>
@endsection