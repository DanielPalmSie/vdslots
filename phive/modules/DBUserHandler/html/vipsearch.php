<?php

// TODO henrik remove

require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

function printWinLoseTable($array, $header = 'Top players') { ?>
    <table class="stats_table">
        <tr class="stats_header">
            <th colspan="4"><?= $header ?></th>
        </tr>
        <tr class="stats_header">
            <th>User</th>
            <th>Win</th>
            <th>Bet</th>
            <th>Result</th>
        </tr>
        <?php foreach ($array as $w) : ?>
            <tr>
                <td><a href="/admin2/userprofile/<?= $w['username'] ?>"><?= $w['username']?></a></td>
                <td><?= $w['currency'].' '.nfCents($w['winsum'],true) ?></td>
                <td><?= $w['currency'].' '.nfCents($w['betsum'],true) ?></td>
                <td><?= $w['currency'].' '.nfCents($w['ressum'],true) ?></td>
            </tr>
        <?php endforeach ?>
    </table>
<?php }

function vipGetMaxSql($tbl){
    $str = "SELECT amount, t2.timestamp, t2.user_id FROM $tbl 
                INNER JOIN (
            SELECT user_id, MAX(timestamp) AS timestamp FROM $tbl 
            GROUP BY user_id
            ) t2 ON $tbl.timestamp = t2.timestamp AND $tbl.user_id = t2.user_id 
                ORDER BY `t2`.`user_id` DESC";
    return $str;
}

function vipGetDailySql($user_where_col, $user_where_val, $sums){
    $str = "SELECT DATE(u.register_date) AS register_date, $sums, u.*, q.amount AS queued_loyalty
            FROM users_daily_stats us
            LEFT JOIN users AS u ON u.id = us.user_id
            LEFT JOIN queued_transactions AS q ON q.user_id = us.user_id
            WHERE us.$user_where_col IN ($user_where_val) GROUP BY $user_where_col";
    return $str;
}

$uh   = phive('DBUserHandler');
$sql  = phive('SQL');
$currencies = $sql->loadArray("SELECT * from currencies WHERE 1");

$sdate = phive('SQL')->escape(empty($_REQUEST['sdate']) ? phive()->hisMod('-1 day', '', 'Y-m-d') : $_REQUEST['sdate'],false);
$edate = phive('SQL')->escape(empty($_REQUEST['edate']) ? date('Y-m-d') : $_REQUEST['edate'],false);

$gross = empty($_REQUEST['gross']) ? 0 : $_REQUEST['gross'];
$gross *= 100;
$win = empty($_REQUEST['win']) ? 0 : $_REQUEST['win'];
$win *= 100;

if (!empty($_REQUEST['currency'])) 
    $where_currency = " AND us.currency = '".phive('SQL')->escape($_REQUEST['currency'],false)."' ";

$having = $win > 0 ? " HAVING gross < -$win " : " HAVING gross >= $gross ";
$cols = phive('UserHandler')->casinoAllStatsNumCols();
$sums = phive('SQL')->makeSums($cols);


if (!empty($_POST['username'])) {
    $usernames = phive('SQL')->makeIn(phive('SQL')->escape($_POST['username'],false));
    $str = vipGetDailySql('username', $usernames, $sums);
}else if (!empty($_POST['user_id'])) {
    $uid = $_POST['user_id'];
    $str = vipGetDailySql('username', intval($_POST["user_id"]),false, $sums);
} else {
    $str = "SELECT DATE(u.register_date) AS register_date, $sums, u.*, q.amount AS queued_loyalty
            FROM users_daily_stats us
            LEFT JOIN users AS u ON u.id = us.user_id
            LEFT JOIN queued_transactions AS q ON q.user_id = us.user_id
            WHERE us.date >= '$sdate' AND us.date <= '$edate' AND u.active = 1 $where_currency 
            GROUP BY us.user_id $having ORDER BY gross DESC
            LIMIT 0, 300";
}

$res = $sql->shs('merge', '', null, 'users_daily_stats')->loadArray($str, 'ASSOC', 'id');

uasort($res, function ($a, $b) {
    return $b['gross'] - $a['gross'];
});

$daily_stats = array_slice($res, 0, 300, true);

$usr_ids     = array_keys($daily_stats);
$usr_ids_in  = $sql->makeIn($usr_ids);

//preprocessing
foreach($daily_stats as $uid => &$dstat_row){
    $u = cu($uid);
    $segment = $u->getCurrentSegment(0);
    if(empty($segment)){
        $tmp = $u->getSegment();
        $dstat_row['segment'] = $tmp['level'];
    }else
        $dstat_row['segment'] = $segment;

}

$sd = date('Y-m-01');
$ed = date('Y-m-t');
$str_this_month = "SELECT $sums, u.*
                   FROM users_daily_stats us, users u
                   WHERE us.user_id = u.id 
                   AND us.date >= '$sd'
                   AND us.date <= '$ed'
                   AND u.active = 1
                   AND u.id IN($usr_ids_in)
                   $where_currency 
                   GROUP BY us.user_id $having ORDER BY gross DESC
                   LIMIT 0, 300";

$tm = $sql->shs('merge', '', null, 'users_daily_stats')->loadArray($str_this_month, 'ASSOC', 'id');


if (!empty($_POST['segment'])) {
    foreach ($daily_stats as $uid => $value) {
        if ($value['segment'] != $_POST['segment'])
            unset ($daily_stats[$uid]);
    }
}

$segments = phive('UserHandler')->getAllSegments();
$sets = [];
$str = "SELECT user_id, setting, value FROM users_settings WHERE (setting = 'sms' OR setting = 'calls') AND user_id IN($usr_ids_in) ORDER BY user_id DESC";
$s = $sql->loadArray($str);
foreach ($s as $i => $us)
    $sets[$us['user_id']] = $us;

$str = vipGetMaxSql('deposits');
$deps = $sql->shs('merge', 'user_id', 'desc', 'deposits')->loadArray($str, 'ASSOC', 'user_id');

$str = vipGetMaxSql('pending_withdrawals');
$wids = $sql->shs('merge', 'user_id', 'desc', 'pending_withdrawals')->loadArray($str, 'ASSOC', 'user_id');

$str = "SELECT be.start_time AS start_time, be.user_id, bt.bonus_name FROM bonus_entries be 
        INNER JOIN (
          SELECT user_id, MAX(start_time) AS start_time FROM bonus_entries 
          GROUP BY user_id
        ) t2 ON be.start_time = t2.start_time AND be.user_id = t2.user_id 
        LEFT JOIN bonus_types bt ON be.bonus_id = bt.id 
        WHERE be.user_id IN($usr_ids_in)
        ORDER BY `t2`.`user_id` DESC";
$bons = $sql->shs('merge', 'user_id', 'desc', 'bonus_entries')->loadArray($str, 'ASSOC', 'user_id');

$gs = phive('SQL')->loadArray('SELECT * FROM micro_games', 'ASSOC', 'ext_game_name');

$str = "SELECT user_id, game_ref, end_time FROM users_game_sessions WHERE user_id IN($usr_ids_in) GROUP BY user_id ORDER BY end_time DESC";
$g = $sql->shs('merge', 'end_time', 'desc', 'users_game_sessions')->loadArray($str);
foreach ($g as $i => $ugs) {
    $gams[$ugs['user_id']] = $ugs;
    $gams[$ugs['user_id']]['game_name'] = $gs[$ugs['game_ref']]['game_name'];
}

$str = "SELECT gross, rewards, paid_loyalty, user_id FROM users_lifetime_stats us WHERE user_id IN($usr_ids_in) ORDER BY gross DESC";
$life_time_stats = $sql->loadArray($str, 'ASSOC', 'user_id');

$begmon = date('Y-m-01');
$str_ = "SELECT sum(gross) AS gross, sum(rewards) as rewards, sum(paid_loyalty) as paid_loyalty, user_id
         FROM users_daily_stats us
         WHERE us.user_id IN($usr_ids_in) 
         AND us.date >= '$begmon'
         GROUP BY user_id";

// No need for shs here since we aggregate users daily stats in the master
$j = $sql->loadArray($str_, 'ASSOC', 'user_id');

$z = $sql->shs('merge', 'last_change', 'asc', 'bonus_entries')->loadArray("SELECT SUM(balance) AS balance, user_id FROM bonus_entries WHERE status = 'active' AND user_id IN($usr_ids_in) GROUP BY user_id ORDER BY last_change", 'ASSOC', 'user_id');

$allcols     = phive('SQL')->getColumns('users_daily_stats');
$usercols    = phive('SQL')->getColumns('users');
$allcols     = array_merge($allcols, $usercols);

$winners     = phive('UserHandler')->getTopWinnersOrLosers(24, "DESC");
$losers      = phive('UserHandler')->getTopWinnersOrLosers(24, "ASC");

$not_these = array('aff_rate', 'date', 'before_deal', 'password', 'bonus_code', 'bank_deductions', 'jp_contrib', 'real_aff_fee', 'chargebacks', 'transfer_fees', 'nbusts', 'jp_fee', 'frb_ded', 'tax', 'frb_cost', 'ip', 'id');
foreach ($not_these as $not_v)
    unset($allcols[$not_v]);
$extra_cols = [
    'gross_lifetime',   'bonus_percentage',      'bonus_percentage_this_month',
    'rewards_lifetime', 'paid_loyalty_lifetime', 'net_loss',
    'net_this_month',   'net_loss_lifetime',     'last_deposit_amount',
    'last_deposit_time','last_withdrawal_amount','last_withdrawal_time',
    'last_bonus_name',  'last_bonus_time',       'last_game_name',
    'last_game_time',   'sms',                   'calls'
];

foreach($extra_cols as $col)
    $allcols[$col] = $col;

$kk      = array_values($daily_stats);
$kk      = array_keys($kk[0]);
if (!empty($_REQUEST['show_col'])) {
    $kk = array_intersect($kk, $_REQUEST['show_col']);
    $cols = array();
}
foreach ($kk as $key => $k)
    $cols[$k] = $k;

foreach($extra_cols as $col)
    $cols[$col] = $col;

$cols['segment'] = 'segment';
$cols['bonus_percentage_this_month'] = 'bonus_percentage_this_month';

foreach($daily_stats as $uid => &$v) {
    $v['segment']                     = $v['segment']*100;
    $v['sms']                         = empty($sets[$uid]['sms'])? 0 : 1;
    $v['calls']                       = empty($sets[$uid]['calls'])? 0 : 1;
    $v['last_game_time']              = $gams[$uid]['etime'];
    $v['last_game_name']              = $gams[$uid]['game_name'];
    $v['last_deposit_amount']         = $deps[$uid]['amount'];
    $v['last_deposit_time']           = $deps[$uid]['timestamp'];
    $v['last_withdrawal_amount']      = $wids[$uid]['amount'];
    $v['last_withdrawal_time']        = $wids[$uid]['timestamp'];
    $v['last_bonus_name']             = $bons[$uid]['bonus_name'];
    $v['last_bonus_time']             = $bons[$uid]['start_time'];
    $v['gross_lifetime']              = $life_time_stats[$uid]['gross'];
    //echo $v['gross_lifetime'];
    //echo " $uid ";
    //exit;
    $v['rewards_lifetime']            = $life_time_stats[$uid]['rewards'];
    $v['paid_loyalty_lifetime']       = $life_time_stats[$uid]['paid_loyalty'];
    $v['net_loss']                    = $v['gross'] - $v['rewards'] - $v['paid_loyalty'];
    $v['net_this_month']              = $j[$uid]['gross'] - $j[$uid]['rewards'] - $j[$uid]['paid_loyalty'];
    $v['net_loss_lifetime']           = $life_time_stats[$uid]['gross'] - $life_time_stats[$uid]['rewards'] - $life_time_stats[$uid]['paid_loyalty'];
    $v['bonus_percentage']            = ($tm[$uid]['rewards'] + $tm[$uid]['paid_loyalty']) / $tm[$uid]['gross'] * 10000;
    $v['bonus_percentage_this_month'] = ($v['rewards'] + $v['paid_loyalty']) / $v['gross'] * 10000;
}


drawStartEndJs();
?>
<div class="pad10">
    <h1>Search users</h1>
    <form action="" method="post">
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">

        <table>
            <tr>
                <td>
                    <table>
                        <?php drawStartEndHtml() ?>
                        <tr>
                            <td>Using currency</td>
                            <td>
                                <select name="currency">
                                    <option value="">Any currency</option>
                                    <?php foreach ($currencies as $currency): ?>
                                        <option value="<?= $currency['code'] ?>"><?= $currency['code']?></option>
                                    <?php endforeach ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Who are in segment</td>
                            <td>
                                <select name="segment">
                                    <option value="">Any</option>
                                    <?php foreach ($segments as $i => $segment): ?>
                                        <option <?= $i == $_POST['segment'] ? 'selected="selected"' : '' ?> value="<?= $i ?>"><?= $segment?></option>
                                    <?php endforeach ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Who have lost minimum</td>
                            <td><input type="text" name="gross" placeholder="Amount" value="<?php echo $gross/100 ?>"/></td>
                        </tr>
                        <tr>
                            <td>Who have won minimum</td>
                            <td><input type="text" name="win" placeholder="Amount" value="<?php echo $win/100 ?>"/></td>
                        </tr>
                        <tr>
                            <td>Choose which columns to show (for CSV)
                            </td>
                            <td><?php dbSelect('show_col[]', array_merge($allcols, $allcols), $cols, array(), '', true) ?></td>
                        </tr>
                        <tr>
                            <td/>
                            <td>
                                <input type="submit" name="submit_search" value="Search"/>
                                <?php if(p('vipsearch.download.csv')): ?>
                                    <input type="submit" name="as_csv" value="As CSV"/>
                                <?php endif ?>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="vertical-align: top;">
                    <table>
                        <tr>
                            <td>By username or usernames (comma-separated)</td>
                            <td>
                                <textarea rows="10" cols="35" name="username" id="username"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td>By user IDs (comma-separated)</td>
                            <td>
                                <textarea rows="10" cols="35" name="user_id" id="userid"></textarea>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="vertical-align: top;">
                    <table>
                        <tr>
                            <td>
                                <?php printWinLoseTable($winners, 'Top winners') ?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <?php printWinLoseTable($losers, 'Top losers') ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </form>

    <strong>Showing date range: <?php echo $sdate.' - '.$edate ?></strong><br/><br/>
    <?php phive('UserSearch')->showCsv($daily_stats, array_merge($cols, $cols)); ?>

    <?php if (isset($busts)) : ?>
        <p>Busts from last hour</p>
        <table id="stats-table" class="stats_table">
            <tr class="stats_header">
                <th>Timestamp</th>
                <th>User</th>
                <th>Bust treshold</th>
                <th>Currency</th>
            </tr>
            <?php $i = 0; foreach ($busts as $i => $bust) : ?>
                <tr class="<?php echo $i % 2 == 0 ? 'fill-odd': 'fill-even'?>">
                    <td><?= $bust['tstamp'] ?></td>
                    <td>
                        <!--<a href="/admin/userprofile/?username=<?= $bust['username'] ?>"><?= $bust['username']?></a>-->
                        <a href="/admin2/userprofile/<?= $bust['username'] ?>"><?= $bust['username']?></a>
                    </td>
                    <td><?= nfCents($bust['bust_th']) ?></td>
                    <td><?= $bust['currency'] ?></td>
                </tr>
            <?php endforeach ?>
        </table>
    <?php else: ?>
        <p>Showing data from <?= $sdate ?> to <?= $edate ?>, using currency: <?php echo empty($_REQUEST['currency'])?"any" : $_REQUEST['currency'] ?></p>
        <table id="stats-table" class="stats_table">
            <tr class="stats_header">
                <th>Username</th>
                <th>Sign up date</th>
                <th>Loss</th>
                <th>Loss (lifetime)</th>
                <th>Rewards</th>
                <th>Rewards (lifetime)</th>
                <th>Paid loyalty</th>
                <th>Paid loyalty (lifetime)</th>
                <th>Net loss</th>
                <th>Net loss (this month)</th>
                <th>Net loss (lifetime)</th>
                <th>Currency</th>
                <th>Segment</th>
                <th>Bonus % (this month)</th>
                <th>Bonus %</th>
                <th>Pending Weekend Booster</th>
                <th>View profile</th>
            </tr>
            <?php $i = 0; foreach($daily_stats as $uid => $value):
            ?>
                <tr class="<?php echo $i % 2 == 0 ? 'fill-odd' : 'fill-even' ?>">
                    <td>
                        <!--<a href="/admin/userprofile/?username=<?= $value['username'] ?>"><?= $value['username']?></a>-->
                        <a href="/admin2/userprofile/<?= $value['username'] ?>"><?= $value['username']?></a>
                    </td>
                    <td><?= $value['register_date']?></td>
                    <td><?php nfCents($value['gross']) ?></td>
                    <td><?php nfCents($life_time_stats[$uid]['gross']) ?></td>
                    <td><?= nfCents($value['rewards'])?></td>
                    <td><?= nfCents($life_time_stats[$uid]['rewards'])?></td>
                    <td><?= nfCents($value['paid_loyalty'])?></td>
                    <td><?= nfCents($life_time_stats[$uid]['paid_loyalty'])?></td>
                    <td><?= nfCents($value['gross'] - $value['rewards'] - $value['paid_loyalty']) ?></td>
                    <td><?= nfCents($j[$uid]['gross'] - $j[$uid]['rewards'] - $j[$uid]['paid_loyalty']) ?></td>
                    <td><?= nfCents($life_time_stats[$uid]['gross'] - $life_time_stats[$uid]['rewards'] - $life_time_stats[$uid]['paid_loyalty']) ?></td>
                    <td><?= $value['currency']?></td>
                    <td><?= $segments[$value['segment']] ?></td>
                    <td><?= nfCents($value['bonus_percentage']) ?>%</td>
                    <td><?= nfCents($value['bonus_percentage_this_month']) ?>%</td>
                    <td><?= nfCents($value['queued_loyalty']) ?></td>
                    <td>
                        <a href="/account/<?= $value['username'] ?>/profile">View profile</a>
                    </td>
                </tr>
            <?php $i++; endforeach; ?>
        </table>
    <?php endif; ?>
</div>
