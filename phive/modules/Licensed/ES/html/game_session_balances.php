<?php
$u_obj = cuPl();
$user_id = $u_obj->getId();
$start_date = phive()->validateDate($_GET['start_date']) ? phive()->fDate($_GET['start_date']) : phive()->modDate(null,
    '-30 day');
$end_date = phive()->validateDate($_GET['end_date']) ? phive()->fDate($_GET['end_date']) : phive()->modDate(null,
    '+1 day');
$balances = lic("getGameSessionBalancesByUserId",
    [$user_id, "{$start_date} 00:00:00", "{$end_date} 23:59:59", $_GET['page']], $user_id);
?>
<div class="simple-box pad-stuff-ten">
    <h3><?= t('game.session.history') ?></h3>
    <table class="account-tbl">
        <tr>
            <td style="vertical-align: top;">
                <table class="zebra-tbl">
                    <col width="180"/>
                    <col width="170"/>
                    <col width="170"/>
                    <col width="140"/>

                    <tr class="zebra-header">
                        <td><?= t('trans.time') ?></td>
                        <td><?= t('session.balance') ?></td>
                        <td><?= t('session.wagered') ?></td>
                        <td><?= t('session.won') ?></td>
                    </tr>
                    <?php $i = 0;
                    foreach ($balances as $row) : ?>
                        <tr class="<?= $i % 2 == 0 ? 'even' : 'odd' ?>">
                            <td><?= phive()->lcDate($row['created_at']) . ' ' . t('cur.timezone') ?></td>
                            <td><?= cs() . ' ' . $row['stake'] / 100 ?></td>
                            <td><?= cs() . ' ' . $row['bet_amount'] / 100 ?></td>
                            <td><?= cs() . ' ' . $row['win_amount'] / 100 ?></td>
                        </tr>
                        <?php $i++; endforeach ?>
                </table>
            </td>
        </tr>
    </table>
</div>
<br/>
