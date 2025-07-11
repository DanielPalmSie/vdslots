<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

function printMtsTransactionsUsers($trss){ ?>
    <?php foreach($trss as $uid => $trs): ?>
        <br/>
        User ID: <strong><?php echo $uid ?></strong>
        <?php printMtsTransactions($trs) ?>
        <br/>
    <?php endforeach ?>
<?php }


function printMtsTransactions($trs){ ?>
    <table class="stats_table">
        <tr class="stats_header">
            <td>Timestamp</td>
            <td>Supplier</td>
            <td>Error</td>
        </tr>
        <?php $i = 0; foreach($trs as $tr): ?>
            <tr class="<?php echo $i % 2 == 0 ? 'odd' : 'even' ?>">
                <td> <?php echo $tr['created_at']->date ?> </td>
                <td> <?php echo strtoupper($tr['supplier']) ?> </td>
                <td> <?php echo implode('<br/>', phive()->flatten($tr['reasons']))  ?> </td>
            </tr>
        <?php $i++; endforeach ?>
    </table>
<?php }

if(!empty($_REQUEST['sdate'])){
    $mts   = new Mts();
    $edate = empty($_REQUEST['edate']) ? phive()->hisNow() : $_REQUEST['edate'];
    $call  = http_build_query(['date_from' => $_REQUEST['sdate'], 'date_to' => $edate, 'limit' => 1000000]);
    $result = $mts->request('user/transfer/deposit/get-failed?'.$call, [], 'GET');
    $res   = phive()->group2d($result['result'], 'user_id');
}

?>
<?php drawStartEndJs() ?>
<div style="padding: 10px;">
    <form action="" method="get">
        <table>
            <?php drawStartEndHtml() ?>
        </table>
        <?php dbSubmit('submit', 'Submit') ?>
    </form>
    <br/>
    <br/>
    <?php
    if(!empty($res))
        printMtsTransactionsUsers($res);
    ?>
</div>
