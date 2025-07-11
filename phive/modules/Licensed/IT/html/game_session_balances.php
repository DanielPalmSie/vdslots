<?php
$trans = ['opened' => t('session.balance.opened'), 'closed' => t('session.balance.closed'), 'addon' => t('session.balance.addon')];
$u_obj = cuPl();
$user_id = $u_obj->getId();
$start_date = phive()->validateDate($_GET['start_date']) ? phive()->fDate($_GET['start_date']) : phive()->modDate(null, '-30 day');
$end_date = phive()->validateDate($_GET['end_date']) ? phive()->fDate($_GET['end_date']) : phive()->modDate(null, '+1 day');
$participations = lic("getGameSessionBalancesByUserId", [$user_id, "{$start_date} 00:00:00", "{$end_date} 23:59:59"], $user_id);
?>
<div class="simple-box pad-stuff-ten">
    <h3><?= t('session.balance.header') ?></h3>
    <table class="account-tbl">
        <tr>
        <td style="vertical-align: top;">
            <table class="zebra-tbl">
            <col width="170"/>
            <col width="80"/>
            <col width="150"/>
            <col width="120"/>
            <col width="80"/>
            <col width="60"/>
            <tr class="zebra-header">
                <td><?= t('trans.time') ?></td>
                <td><?= t('session') ?></td>
                <td><?= t('game.name') ?></td>
                <td><?= t('session.balance.balance') ?></td>
                <td><?= t('win') ?></td>
                <td></td>
            </tr>

            <?php $i = 0; ?>
            <?php foreach($participations as $participation): ?>
                <tr class="<?= $i % 2 == 0 ? 'even' : 'odd' ?>">
                    <td><?= phive()->lcDate($participation['ended_at']) .' '.t('cur.timezone') ?></td>
                    <td><?= $trans['closed'] ?></td>
                    <td><?= $participation['game_name'] ?></td>
                    <td>&nbsp;</td>
                    <td><?= efEuro(trim($participation['balance'] - $participation['stake']), true) ?></td>
                    <td>
                        <input onclick="lic('gameSessionHistoryPopup', [<?= $participation['external_game_session_id'] ?>])" value="View" class="btn btn-xs btn-default-xs w-40">
                    </td>
                </tr>
                <?php $i++; ?>
                <?php foreach($participation['increments'] as $j => $increment): ?>
                <tr class="<?= $i % 2 == 0 ? 'even' : 'odd' ?>">
                    <td><?= phive()->lcDate($increment['created_at']) .' '.t('cur.timezone') ?></td>
                    <td><?= ($j == count($participation['increments']) - 1) ? $trans['opened'] : $trans['addon'] ?></td>
                    <td><?= $participation['game_name'] ?></td>
                    <td><?= isset($increment['balance']) ? efEuro(trim($increment['balance']), true) : '&nbsp' ?></td>
                    <td>&nbsp;</td>
                    <td>
                        <input onclick="lic('gameSessionHistoryPopup', [<?= $participation['external_game_session_id'] ?>])" value="View" class="btn btn-xs btn-default-xs w-40">
                    </td>
                </tr>
                <?php $i++; ?>
                <?php endforeach ?>

            <?php endforeach ?>
            </table>
        </td>
        </tr>
    </table>
</div>
<br/>
