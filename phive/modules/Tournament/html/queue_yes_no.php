<?php
// 
?>
<div class="mp-popup-header gradient-default">
    <?php et('mp.registration.headline') ?>
</div>
<div class="mp-popup-content-wrapper">
    <p>
        <?php et('mp.do.you.want.to.queue') ?>
    </p>
    <table style="width: 100%;">
        <tr>
            <td>
                <?php btnDefaultL(t('cancel'), '', 'mboxClose()', 170) ?>
            </td>
            <td>
                <?php btnActionL(t('mp.join.queue'), '', "joinQueue('{$_POST['t_id']}', '{$_POST['use_ticket']}')", 170) ?>
            </td>
        </tr>
    </table>
</div>
