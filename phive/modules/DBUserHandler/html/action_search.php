<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../html/display_base_diamondbet.php';

// TODO henrik remove if not used

$sdate = empty($_REQUEST['sdate']) ? date('Y-m-d 00:00:00') : $_REQUEST['sdate'];
$edate = empty($_REQUEST['edate']) ? date('Y-m-d 23:59:59') : $_REQUEST['edate'];

if(!empty($_REQUEST['descr']))
    $where_descr = "AND descr LIKE '%{$_REQUEST['descr']}%'";
    
if(!empty($_REQUEST['tag']))
    $where_tag = "AND tag = '{$_REQUEST['tag']}'";

if(!empty($_REQUEST['user_id']))
    $where_uid = "AND target = {$_REQUEST['user_id']}";

$rows = phive('UserHandler')->getActions($sdate, $edate, '0,500', $where_tag, $where_descr, $where_uid);

?>
<div class="pad10">
    <?php drawStartEndJs() ?>
    <form action="" method="get">
        <table border="0" cellspacing="5" cellpadding="5">
            <?php drawStartEndHtml() ?>
            <tr>
                <td>User ID:</td>
                <td>
                    <?php dbInput('user_id', $_REQUEST['user_id']) ?>
                </td>
            </tr>
            <tr>
                <td>Tag:</td>
                <td>
                    <?php dbInput('tag', $_REQUEST['tag']) ?>
                </td>
            </tr>
            <tr>
                <td>And / Or part of description:</td>
                <td>
                    <?php dbInput('descr', $_REQUEST['descr']) ?>
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
    <br>
    <table id="stats-table" class="stats_table">
        <thead>
            <tr class="stats_header">
                <th>Created At</th>
                <th>Actor ID</th>
                <th>User ID</th>
                <th>Descr. Text</th>
                <th>Tag</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 0; foreach($rows as $r): ?>
                <tr class="<?php echo $i % 2 == 0 ? 'fill-odd' : 'fill-even' ?>">
                    <td><?php echo $r['created_at'] ?></td>
                    <td><?php echo $r['actor'] ?></td>
                    <td><a href="/admin2/userprofile/<?php echo $r['target'] ?>/"><?php echo $r['target'] ?></a></td>
                    <td><?php echo $r['descr']  ?></td>
                    <td><?php echo $r['tag'] ?></td>
                </tr>
            <?php $i++; endforeach ?>
        </tbody>
    </table>
    <br>
</div>
