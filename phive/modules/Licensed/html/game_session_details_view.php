<?php
$trans = ['opened' => t('session.balance.opened'), 'closed' => t('session.balance.closed'), 'addon' => t('session.balance.addon')];
$user = cu()->data;
$session_id = intval($_POST['session_id']);
$participations = lic('getExternalGameSessionDetails', [$user['id'], $session_id], $user);

$code1 = ''; // Example: 6031980020711734
$code2 = ''; // Example: C.F TRVSFN88D051628U


?>

<?php foreach($participations as $participation): ?>
<div class="game-session-summary">
    <div class="game-session-summary__header">
        <b><?= t('session.balance.ticket.id') ?></b>
        <span><?= $participation['external_game_session_id'] ?></span>
    </div>
    <div class="game-session-summary__info">
        <div><?= $code1 ?></div>
        <div><?= $code2 ?></div>
        <div><?= "{$user['firstname']} {$user['lastname']}" ?></div>
        <div><?= "SP {$participation['cn_id']} - IP {$participation['cn_id']}" ?></div>
    </div>
    <div class="game-session-summary__footer">
        <b>Importo <?= efEuro($participation['stake'], true) ?></b>
    </div>
</div>

<table class="game-session-details">
    <col width="100">
    <col width="120">
    <col width="150">
    <col width="150">
    <col width="70">
    <col width="70">
    <col width="70">
    <tr>
        <th><?= t('trans.time') ?></th>
        <th><?= t('game.name') ?></th>
        <th><?= t('session.balance.aams.session.id') ?></th>
        <th><?= t('session.balance.participation.id') ?></th>
        <th><?= t('amount') ?></th>
        <th><?= t('win') ?></th>
        <th><?= t('status') ?></th>
    </tr>
    <tr>
        <td><?= phive()->lcDate($participation['ended_at']) .' '.t('cur.timezone') ?></td>
        <td><?= $participation['game_name'] ?></td>
        <td><?= $participation['session_id'] ?></td>
        <td><?= $participation['participation_id'] ?></td>
        <td>&nbsp;</td>
        <td><?= efEuro($participation['balance'] - $participation['stake'], true) ?></td>
        <td><?= $trans['opened'] ?></td>
    </tr>
    <?php foreach($participation['increments'] as $i => $increment): ?>
        <tr>
            <td><?= phive()->lcDate($increment['created_at']) .' '.t('cur.timezone') ?></td>
            <td><?= $participation['game_name'] ?></td>
            <td><?= $participation['session_id'] ?></td>
            <td><?= $participation['participation_id'] ?></td>
            <td><?= efEuro($increment['increment'], true) ?></td>
            <td>&nbsp;</td>
            <?php if($i != count($participation['increments']) - 1): ?>
                <td><?= $trans['addon'] ?></td>
            <?php else: ?>
                <td><?= $trans['closed'] ?></td>
            <?php endif ?>
        </tr>
    <?php endforeach ?>
</table>
<?php endforeach ?>