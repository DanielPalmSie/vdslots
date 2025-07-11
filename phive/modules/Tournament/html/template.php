<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../api/crud/crud.php';
$iso = phive('Tournament')->curIso();
?>
<div class="pad10" style="overflow-x: scroll; width: 80%; margin: 10px;">
<p><strong>Category:</strong> currently only normal or guaranteed is possible.</p>
<p><strong>Start format:</strong> currently mtt and sng is possible, mtt is just a scheduled tournament, sngs are not scheduled, they start when the min amount of players have registered.</p>
<p><strong>Win format:</strong> tht (The Highest Total) or thw (The Highest Win). In case of tht the players who accumulates the highest total win sum wins, in case of thw the player who scores the highest single win wins.</p>
<p><strong>Play format:</strong> currently only balance and xspin, xspin is the same as balance but with a limited amount of spins, if it is an xspin Xspin info needs to have a value.</p>
<p><strong>Cost:</strong> the amount of money in <?php echo $iso ?> cents that will be the player's play/MP balance.</p>
<p><strong>Pot cost:</strong> the amount of money in <?php echo $iso ?> cents that will be paid to the prize pot, this money is reserved and will not be gambled with.</p>
<p><strong>Xspin info:</strong> Currencly the amount of spins the tournament should be limited to.</p>
<p><strong>Min players:</strong> the minimum amount of players that need to sign up, if this target is not reached the MP will be cancelled automatically instead of started and all balances and pot money will be re-disbursed to registered players' cash balances.</p>
<p><strong>Max players:</strong> in case of mtt registration will close when this number has been reached, in case of sng the MP starts right away when the number is reached, at the same time a new sng MG is created from the template.</p>
<p><strong>Mtt show hours before:</strong> the number of hours before start an mtt should show at all (ie display with status upcoming).</p>
<p><strong>Duration minutes:</strong> the number of minutes an MP should last.</p>
<p><strong>Mtt start time:</strong> in case of Mtt recur type is day: 06,08,12,14,16,18,20,22, in case it is week or month: 20:30:00 for half past eight PM.</p>
<p><strong>Mtt start date:</strong> in case of a one-time MP, this is the date it starts (ex 2014-12-24 for a Christmas eve tournament), in case of a recurring MP it will be ignored.</p>
<p><strong>Mtt reg duration minutes:</strong> the number of minutes before the start time it will be possible to register for an mtt MP.</p>
<p><strong>Mtt late reg duration minutes:</strong> the number of minutes after the start time it will be possible to register for an mtt MP.</p>
<p><strong>Mtt recur type:</strong> can be day, week or month in case of mtt.</p>
<p><strong>Mtt recur days:</strong> in case recur type is week: 1-7 (ex 5,6,7 if it is to show on Fri, Sat and Sun), in case it is month: 01-31 (ex 01,02,03 if it is to show on the first 3 days in every month), leave empty in case it is day.</p>
<p><strong>Recur end date:</strong> when the MP should stop recurring, applies to both sng and mtt.</p>
<p>
  <strong>Recur:</strong> if the MP should recur, setting this to 0 will in effect pause creation of the MP in question, this field should be used to hide/remove an MP, <strong>deletion is forbidden</strong>.<br/>
  In case of SNG:<br/>
  0: Disables new creations of the SNG.<br/>
  1: Will cause immediate creation of a new tournament when the current one is filled,<br/>
  2: Causes the creation to happen when the current one is finished, ie the play time is up.<br/>
  3: Similar to #1 but player can't register in the same tournament until he is finished with the current one (tournament entry status is finished).<br/>
  4: Similar to #1 but player can't register in the same tournament until the current one is completely finished (tournament status is finished).<br/>
</p>
<p><strong>Guaranteed prize amount:</strong> amount in <?php echo $iso ?> cents, in case the total prize pool is less than this number the difference will be made up by way of transactions of type 41 to each winning player.</p>
<p><strong>Prize type:</strong> currently cash-balance, cash-fixed or win-prog.<br>
  In case of win-fixed the cost will be used for the prize pool, cash balances will NOT be returned, note that a house fee needs to be applied to this type in order for the house to make any money (in case of a non-freeroll), <strong>during game play the cash balance will not be increased with the winnings.</strong><br>
  In case of cash-balance both the prize pot and all MP cash balances will be used to make up the total prize pool.<br>
  In case of cash-fixed only the prize pot will be used and all MP balances will be returned to each player.<br>
  In case of win-prog the prize pot, cash balances and win amounts will be used to make up the prize pool, <strong>during game play the cash balance will not be increased with the winnings.</strong>
</p>
<p>
  <strong>Get race, Get loyalty and Get trophy</strong> should be either 0 or 1. 1 to get said thing and 0 to not get it.
</p>
<p>
  <strong>Turnover threshold</strong> is the amount of <?php echo $iso ?> cents that needs to be wagered in order to be able to receive a prize at all.
</p>
<p>
  <strong>Award ladder tag</strong> is the tag for the award prize ladder to be used in case cash prizes are not used. Note that setting this value to something automatically overrides any normal ladder.
</p>
<p>
  <strong>Ladder tag</strong> is the tag for the cash prize ladder to be used. Note that this value is disregarded in case an Award ladder tag is set.
</p>
<p>
  <strong>Included and Excluded countries</strong> are lists of ISO2 codes, ie PL BG RU. Players from non-allowed countries won't see the tournament in question in the lobby listing.
</p>
<p>
  <strong>Reg wager lim, Reg dep lim and Reg lim period</strong> controls how much a player needs to have deposited and / or wagered in <?php echo $iso ?> cents in the Reg lim period which is in days.
</p>
<p>
  <strong>Free pot cost</strong>, if there is a pot cost it will be paid for by the casino if this value is 1, if it is zero or empty it will be payed for by the player.
</p>
<p>
  <strong>Prize calc wait minutes</strong>, due to the possibility of players entering freespins etc just before the tournament time period is over this value can be used to delay prize calculations which enables lagging wins to arrive before prizes are handed out. Any lagging bets or rebuys will still be rejected.
</p>
<p>
  <strong>Allow bonus</strong>, (0 / 1), 0 = do not allow, 1 = allow.
</p>
<p>
  <strong>Total cost</strong> in cents of the tournament currency. This value will be compared against self imposed limits (wager, loss etc) to determine if if the player can "afford" to enter the tournament.
</p>
<p>
  <strong>Rebuy house fee</strong> in cents of the tournament currency. This value will be applied everytime someone makes a rebuy.
</p>
<p>
  <strong>Spin M</strong>, the spin multiplier, will be used to multiply the cash balance and the available spins on register and rebuy. Use only with <strong>win fixed</strong>, use the default value 1 for all other types.
</p>
<p>
  <strong>Number of jokers</strong>, the number of Jokers for this tournament. Jokers will get doubled up with their winnings if any.
</p>
<p>
  <strong>Bounty award id</strong>, the award for the bounty prize for being better with exactly 1 place than the bounty guy who won the last tournament. If it is 0, bounty will be not applied to this tournament.
</p>
<p>
    <strong>Bet levels</strong>, ex: 20,40,60,100 NO spaces, just commas and cents. If empty min and max bet will be respected instead.
</p>
<?php
$crud = Crud::table('tournament_tpls');
$crud->delete = false;
$crud->renderInterface(
  'id', 
  array(
    'game_ref' => array(
      'table' => 'micro_games', 
      'idfield' => 'ext_game_name',  
      'dfield' => 'game_name', 
      'dfields' => array('game_name', 'ext_game_name', 'device_type'))
  )
);
?>
</div>

