<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

ini_set('max_execution_time', '90000');
ini_set('memory_limit', '5000M');
$mg = phive('MicroGames');

$sday = '01';
$eday = '31';
$sdate = empty($_REQUEST['sdate']) ? date("Y")."-01-01" : $_REQUEST['sdate'];
$date = $edate = empty($_REQUEST['edate']) ? phive()->today() : $_REQUEST['edate'];


$dormant_date = phive()->hisMod('-30 month', $sdate, 'Y-m-d');
$country = empty($_REQUEST['country']) ? 'GB' : $_REQUEST['country'];

$show_countries = $_GET['show_countries'];
$show_networks = $_GET['show_networks'];

$countrylist = phive('SQL')->makeIn($show_countries);
$networklist = phive('SQL')->makeIn($show_networks);

// Sharding TODO, this whole file needs to work sharded

$users = phive('SQL')->readOnly()->shs('sum', '', null, 'users')->loadArray("SELECT COUNT(id) as count FROM users WHERE DATE(last_login) < '$dormant_date' AND country IN ($countrylist)")[0];

$selfexclusions = phive('SQL')->readOnly()->shs('sum', '', null, 'users')->loadArray("SELECT COUNT(*) AS amount FROM users_settings us, users u 
                                           WHERE setting = 'excluded-date' AND us.user_id = u.id AND value >= '$sdate 00:00:00' AND value <= '$edate 23:59:59' AND u.country IN ($countrylist)")[0];

$prev_sdate = phive()->hisMod('-6 month', $sdate, 'Y-m-d');
$lost_cnt = current(phive('SQL')->readOnly()->shs('sum', '', null, 'users')->loadArray("SELECT count(*) FROM users u WHERE DATE(last_login) >= '$prev_sdate' AND DATE(last_login) <= '$sdate'")[0]);

$self_return_str = "
    SELECT COUNT(*) FROM users u, users_settings us 
    WHERE u.id = us.user_id 
    AND u.country IN ($countrylist) 
    AND us.setting = 'unexcluded-date' 
    AND us.created_at >= '$sdate'
    AND us.created_at <= '$edate'
    AND u.last_login >= us.created_at";
$self_ex_returns_cnt = (int)current(phive('SQL')->readOnly()->shs('sum', '', null, 'users')->loadArray($self_return_str)[0]);


$block_cnt_str = "
    SELECT COUNT(*) FROM users_blocked ub, users u 
    WHERE ub.user_id = u.id 
    AND ub.reason IN(0,1,2,3,5,6,7,8,9) 
    AND u.country IN($countrylist)
    AND ub.date >= '$sdate'
    AND ub.date <= '$edate'
    GROUP BY ub.user_id";
$block_cnt = count(phive('SQL')->readOnly()->shs('merge', '', null, 'users_blocked')->loadArray($block_cnt_str));

$restrictions = phive('SQL')->readOnly()->shs('merge', '', null, 'actions')->loadArray("SELECT * FROM actions a, users u 
                                         WHERE a.tag = 'play_block' AND a.created_at >= '$sdate 00:00:00' AND a.created_at <= '$edate 23:59:59' AND a.target = u.id AND u.country IN ($countrylist)");

$timeouts = phive('SQL')->readOnly()->shs('merge', '', null, 'actions')->loadArray("SELECT * FROM actions a, users u 
                                     WHERE a.tag = 'profile-lock' AND a.created_at >= '$sdate 00:00:00' AND a.created_at <= '$edate 23:59:59' AND a.target = u.id AND u.country IN ($countrylist)");

// We get aggregated results from master
$active = phive('SQL')->readOnly()->loadArray("SELECT * FROM users_daily_stats 
                                   WHERE country IN ($countrylist) AND `date` >= '$sdate' AND `date` <= '$edate' AND bets > 0 
                                   GROUP BY user_id");


$new_active = phive('SQL')->readOnly()->loadArray("
    SELECT * FROM users_daily_stats                                    
    WHERE country IN ($countrylist) 
    AND `date` >= '$sdate' 
    AND `date` <= '$edate' 
    AND bets > 0
    AND user_id IN(SELECT id FROM users WHERE DATE(register_date) >= '$sdate'  AND DATE(register_date) <= '$edate')
    GROUP BY user_id");


$sql = "SELECT * FROM users 
        WHERE country IN ($countrylist)
        AND DATE(register_date) >= '$sdate'
        AND DATE(register_date) <= '$edate'
        ORDER BY dob DESC";

$accounts = phive('SQL')->readOnly()->shs('merge', 'dob', 'desc', 'users')->loadArray($sql, "ASSOC", "id");
//$archived_accounts = phive('SQL')->doDb('archive')->loadArray($sql, "ASSOC", "id");

$allaccounts = array();
foreach ($accounts as $id => $account) 
    $allaccounts[$id] = $account;

foreach ($archived_accounts as $id => $account) {
    if (empty($accounts[$id]))
        $allaccounts[$id] = $account;
}

// We get aggregated results from master
$networks = phive('SQL')->readOnly()->loadArray("SELECT network FROM users_daily_game_stats WHERE network != '' GROUP BY network", "ASSOC", "network");

$bankcountries = phive('SQL')->readOnly()->loadKeyValues("SELECT * FROM `bank_countries` ORDER BY printable_name ASC", "iso", "printable_name");

// We get aggregated results from master
$countries = phive('SQL')->readOnly()->loadArray("SELECT country FROM users_daily_stats WHERE country != '' GROUP BY country", "ASSOC", "country");


$limitstalks = phive('SQL')->readOnly()->shs('sum', '', null, 'users_comments')->loadArray("SELECT count(*) AS num FROM users_comments 
                                        INNER JOIN users u ON u.id = users_comments.user_id 
                                        WHERE tag = 'limits' AND u.country IN ($countrylist) AND created_at >= '$sdate' AND created_at <= '$edate'")[0];

$complaints = phive('SQL')->readOnly()->shs('sum', '', null, 'users_comments')->loadArray("SELECT count(*) AS num FROM users_comments 
                                       INNER JOIN users u ON u.id = users_comments.user_id 
                                       WHERE tag = 'complaint' AND u.country IN ($countrylist) AND created_at >= '$sdate' AND created_at <= '$edate'")[0];

$gametypes = array(	'tablegames' => "g.tag in('roulette', 'blackjack', 'table', 'live')",
                    'slots' => "g.tag in ('slots', 'slots_jackpot', 'system', 'videoslots_jackpot', 'videoslots_jackpotbsg')",
                    'cardgames' => "g.tag = 'videopoker'",
			        'other' => "g.tag in('other', 'scratch-cards')"
);

$videoslots = phive('SQL')->readOnly()->loadKeyValues("SELECT id, ext_game_name FROM micro_games WHERE tag = 'videoslots'", "id", "ext_game_name");
$videoslots_games = phive('SQL')->makeIn($videoslots);

foreach (cisos() as $ciso) {
    $where_type = $_REQUEST['device_type'] == 'na' ? '' : "AND g.device_type_num = {$_REQUEST['device_type']}";
    foreach ($gametypes as $gametype => $typestr) {
        //  $sql = "SELECT SUM(ug.bets / cur.multiplier) AS bets, SUM(ug.wins / cur.multiplier) AS wins, ug.currency as currency FROM users_daily_game_stats ug
        //LEFT JOIN currencies AS cur ON cur.code = currency
        $sql = "SELECT SUM(ug.bets) AS bets, SUM(ug.wins) AS wins, ug.currency as currency FROM users_daily_game_stats ug
                LEFT JOIN micro_games AS g ON ug.game_ref = g.ext_game_name AND ug.device_type = g.device_type_num
                WHERE ug.date >= '$sdate' 
                    AND ug.date <= '$edate' 
                    AND ug.currency = '$ciso'  
                    AND ug.country IN ($countrylist)
                    AND ug.network IN ($networklist)
                    $where_type
                    AND $typestr";
        $games[$gametype][$ciso] = phive('SQL')->readOnly()->loadAssoc($sql);

        if ($gametype == "slots"){

            $sql = "SELECT SUM(ug.bets) AS bets, SUM(ug.wins) AS wins, ug.currency as currency FROM users_daily_game_stats ug
                LEFT JOIN micro_games AS g ON ug.game_ref = g.ext_game_name AND ug.device_type = g.device_type_num
                WHERE ug.date >= '$sdate' 
                    AND ug.date <= '$edate' 
                    AND ug.currency = '$ciso'  
                    AND ug.country IN ($countrylist)
                    AND ug.network IN ($networklist)
                    $where_type
                    AND ug.game_ref IN ($videoslots_games)";
            $vsresult[$gametype][$ciso] = phive('SQL')->readOnly()->loadAssoc($sql);
            $games[$gametype][$ciso]["bets"] +=   $vsresult[$gametype][$ciso]["bets"];
            $games[$gametype][$ciso]["wins"] +=   $vsresult[$gametype][$ciso]["wins"];
        }
    }
}

$groups  = array();
$genders = array();
$cnts    = array();
$t       = 0;
$uh      = phive('UserHandler');

foreach ($accounts as $a) {
  $age = $uh->ageFromDoB($a['dob']);
  $map = array(24 => '18-24', 34 => '25-34', 44 => '35-44', 54 => '45-54', 64 => '55-64', 65 => '65+');
  $genders[$a['sex']] += 1;
  $n = phive()->getLvl($age, $map, '65+');
  $groups[$n] = $a;
  $cnts[$n] += 1; $t += 1;
}

$q = "SELECT 
    SUM(bets / cur.multiplier) AS bets, 
    SUM(wins / cur.multiplier) AS wins, 
    SUM(gross / cur.multiplier) AS gross, 
    SUM(jp_contrib / cur.multiplier) AS jp_contrib, 
    SUM(rewards / cur.multiplier) AS rewards, 
    (SUM(gross/cur.multiplier) - SUM(rewards/cur.multiplier) - SUM(jp_contrib/cur.multiplier)) AS net, 
    SUM(tax / cur.multiplier) AS tax,        
    currency 
FROM network_stats 
LEFT JOIN fx_rates AS cur ON cur.code = currency AND cur.day_date = network_stats.date 
WHERE date >= '$sdate' 
AND date <= '$edate' 
AND country IN ($countrylist) 
AND network IN ($networklist) 
GROUP BY currency";


$network_stats = phive('SQL')->readOnly()->loadArray($q);

$q = "SELECT 
    SUM(gross / cur.multiplier) AS gross, 
    SUM(rewards / cur.multiplier) AS rewards, 
    (SUM(gross/cur.multiplier) - SUM(rewards/cur.multiplier) - SUM(jp_contrib/cur.multiplier)) AS net, 
    SUM(tax / cur.multiplier) AS tax,
    country
FROM network_stats 
LEFT JOIN fx_rates AS cur ON cur.code = currency AND cur.day_date = network_stats.date 
WHERE `date` >= '$sdate' 
AND `date` <= '$edate' 
AND country IN ($countrylist) 
AND network IN ($networklist) 
GROUP BY country";

$res = phive('SQL')->readOnly()->loadArray($q);

$total_tax = phive()->sum2d($res, 'tax');

$ggy = phive()->sum2d($network_stats, "net");

$regs = phive('SQL')->readOnly()->shs('merge', '', null, 'users')->loadArray("SELECT id FROM users 
                                 WHERE country IN ($countrylist) AND DATE(register_date) >= '$sdate' AND DATE(register_date) <= '$edate'
                                 GROUP BY id");
?><pre>
  <?php 
  $real = 0;
  foreach (cisos() as $ciso) {
      $b = miscCache("$date-cash-balance-$ciso");	
      $b = unserialize($b);
      $real += chg($ciso, "EUR", $b['real'], 1);
  }
  ?></pre>
<div class="pad10">
  <h3>Generate country report</h3>
  <h4>(All numbers in EUR unless stated otherwise)</h4>
  <form action="" method="get">
    <table>
      <tr>
        <td>Choose countries: </td>
        <td>
            <select name="show_countries[]" multiple="multiple" size="10">
            <?php foreach ($countries as $k => $c) :?>
              <? if (!empty($bankcountries[$k])) : ?>
                <option <?= in_array($k, $show_countries)? ' selected=selected ' : '' ?> value="<?= $k ?>"><?= $bankcountries[$k] ?></option>
              <? endif ?>
            <?php endforeach ?>
          </select>
        </td>
        <td>Choose networks: </td>
        <td>
            <select name="show_networks[]" multiple="multiple" size="10">
            <?php foreach ($networks as $n => $c) :?>
              <option <?= in_array($n, $show_networks)? ' selected=selected ' : '' ?> value="<?= $n ?>"><?= $n ?></option>
            <?php endforeach ?>
          </select>
        </td>
      </tr>
      <tr>
        <td>From:</td>
        <td>
          <?php dbInput('sdate', $sdate) ?>
        </td>
      </tr>
      <tr>
        <td>To:</td>
        <td>
          <?php dbInput('edate', $edate) ?>
        </td>
      </tr>
      <tr>
          <td>Device type:</td>
          <td>
              <?php dbSelect('device_type', ['na' => 'Select', 0 => 'PC', 1 => 'Mobile'], '') ?>
          </td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>
          <?php dbSubmit('Submit') ?>
        </td>
      </tr>
    </table>
  </form>
  <table>
    <tr class="fill-odd">
      <td>Total GGY:</td><td> 	<?= nfCents($ggy); ?></td>
    </tr>
    <tr>
      <td>TAX:</td><td> 		<?= nfCents($total_tax) ?></td>
    </tr>
    <tr class="fill-odd">
      <td>Funds held in customer accounts (<?php echo $date ?>, all countries):</td><td><?php nfCents($real) ?></td>
    </tr>
    <tr>
      <td>Total number of accounts for Gambling Commission licensed activities:</td><td> <?= count($allaccounts) ?></td>
    </tr>
    <tr class="fill-odd">
      <td>Total number of active accounts for Gambling Commission licensed activities:</td><td> <?= count($active); ?></td>
    </tr>
    <tr>
      <td>Total gambling active customers:</td><td> <?= count($active); ?></td>
    </tr>
    <tr class="fill-odd">
      <td>Number of new registrations:</td><td> <?= count($regs) ?></td>
    </tr>
    <tr>
      <td>Number of dormant accounts (no activity for over 30 months):</td><td> <?= $users['count'] ?></td>
    </tr>
    <tr class="fill-odd">
        <td>Self-excluded individuals opting to return to gambling (after minimum 6 month exclusion period):</td><td><?php echo $self_ex_returns_cnt ?></td>
    </tr>
    <tr>
      <td>Self-exclusions made:</td><td> <?= $selfexclusions['amount']; ?></td>
    </tr>
    <tr class="fill-odd">
      <td>Timeouts:</td><td> <?= count($timeouts); ?></td>
    </tr>
    <tr>
      <td>Restrictions made on individual products:</td><td> <?= count($restrictions) ?></td>
    </tr>
    <tr class="fill-odd">
      <td>Number of complaints:</td><td> <?php echo $complaints['num']; ?></td>
    </tr>
    <tr>
      <td>Number of comments about responsible gaming:</td><td> <?php print $limitstalks['num'] ?></td>
    </tr>
    <tr class="fill-odd">
        <td>Total gambling new (registered in period) active customers:</td><td> <?= count($new_active); ?></td>
    </tr>
    <tr class="">
        <td>Total number of suspended customers:</td><td> <?= $block_cnt ?></td>
    </tr>
    <tr class="">
        <td>Lost accounts:</td><td> <?= $lost_cnt ?></td>
    </tr>
  </table>

  <table>
    <tr>
      <td>
        <table class="stats_table">
          <tr class="stats_header">
 	    <th>Age</th>
 	    <th>Percentage</th>
 	    <th>Amount</th>
          </tr>
          <?php foreach ($groups as $group => $amount) : ?>
	    <tr>
	      <col width="120"/>
	      <col width="120"/>
	      <col width="120"/>
	      <td><?= $group ?></td>
	      <td><?= round($cnts[$group] / count($accounts) * 100, 2) ?>%</td>
	      <td><?= $cnts[$group] ?></td>
	    </tr>
          <?php endforeach ?>
          <tr>
 	    <th></th>
 	    <th></th>
 	    <td><strong><?= count($accounts) ?></strong></td>
          </tr>
        </table>
      </td>
      <td style="vertical-align: top;">
        <table class="stats_table">
          <tr class="stats_header">
            <th>Gender</th>
            <th>Percentage</th>
            <th>Amount</th>
          </tr>
          <?php foreach ($genders as $g => $a) : ?>
	    <tr>
	      <col width="120"/>
	      <col width="120"/>
	      <col width="120"/>
	      <td><?= $g ?></td>
	      <td><?= round($a / count($accounts) * 100, 2) ?>%</td>
	      <td><?= $a ?></td>
	    </tr>
          <?php endforeach ?>
          <tr>
 	    <th></th>
 	    <th></th>
 	    <td><strong><?= count($accounts) ?></strong></td>
          </tr>
        </table>
      </td>
    </tr>
  </table>



  <?php foreach ($games as $type => $data) : ?>
    <table class="stats_table">
      <tr class="stats_header">
	<th><?= ucfirst($type) ?></th>
	<th>Bets</th>
	<th>Wins</th>
      </tr>
      <?php foreach ($data as $cur => $v) : ?>
	<tr>
	  <col width="120"/>
	  <col width="120"/>
	  <col width="120"/>
	  <td><?= $cur ?></td>
	  <td><?= nfCents($v['bets']) ?></td>
	  <td><?= nfCents($v['wins']) ?></td>
	</tr>
      <?php endforeach ?>
    </table>
  <?php endforeach ?>

  <table class="stats_table">
    <tr class="stats_header">
      <th>Currency</th>
      <th>Bets (in EUR)</th>
      <th>Wins (in EUR)</th>
      <th>Gross</th>
      <th>Jackpots</th>
      <th>Rewards</th>
      <th>GGY</th>
      <th>TAX</th>
    </tr>
    <?php foreach ($network_stats as $row) : ?>
      <tr>
	<col width="120"/>
	<col width="120"/>
	<col width="120"/>
	<col width="120"/>
	<col width="120"/>
	<col width="120"/>
	<col width="120"/>
	<col width="120"/>
	<td><?= nfCents($row['currency']) ?></td>
	<td><?= nfCents($row['bets']) ?></td>
	<td><?= nfCents($row['wins']) ?></td>
	<td><?= nfCents($row['gross']) ?></td>
	<td><?= nfCents($row['jp_contrib']) ?></td>
	<td><?= nfCents($row['rewards']) ?></td>
	<td><?= nfCents($row['net']) ?></td>
	<td><?= nfCents($row['tax']) ?></td>
      </tr>
    <?php endforeach ?>	
    <tr>
      <td></td>
      <td><?= nfCents(phive()->sum2d($network_stats, "bets")) ?></td>
      <td><?= nfCents(phive()->sum2d($network_stats, "wins")) ?></td>
      <td><?= nfCents(phive()->sum2d($network_stats, "gross")) ?></td>
      <td><?= nfCents(phive()->sum2d($network_stats, "jp_contrib")) ?></td>
      <td><?= nfCents(phive()->sum2d($network_stats, "rewards")) ?></td>
      <td><?= nfCents(phive()->sum2d($network_stats, "net")) ?></td>
      <td><?= nfCents(phive()->sum2d($network_stats, "tax")) ?></td>
    </tr>
  </table>

</div>
