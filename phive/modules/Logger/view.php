<?php
require_once __DIR__ . '/../../admin.php';
require_once __DIR__ . '/../../html/display_base_diamondbet.php';

$sdate = empty($_REQUEST['sdate']) ? date('Y-m-d 00:00:00') : $_REQUEST['sdate'];
$edate = empty($_REQUEST['edate']) ? date('Y-m-d 23:59:59') : $_REQUEST['edate'];

if(!empty($_REQUEST['tag']))
  $tags = phive('SQL')->makeIn($_REQUEST['tag']);
else
  $liketags = $_REQUEST['liketag'];

$tags_sel = array('netent_frbwin_failure', 'bonusmails');

if(empty($tags) && empty($liketags))
  $tags = phive('SQL')->makeIn($tags_sel);

$where_tags = empty($tags) ? "LIKE '%$liketags%'" : "IN($tags)";

if(!empty($_REQUEST['dump_like'])){
  $where_dump = "AND dump_txt LIKE '%{$_REQUEST['dump_like']}%'";
}

if(!empty($_REQUEST['user_id'])){
  $where_uid = "AND user_id = {$_REQUEST['user_id']}";
}


$sql = "SELECT * FROM trans_log WHERE created_at >= '$sdate' AND created_at <= '$edate' AND tag $where_tags $where_dump $where_uid";

$rows = phive('SQL')->loadArray($sql);
$tags = array_combine($tags_sel, $tags_sel);

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
        <td>Tags:</td>
        <td>
          <?php dbSelect('tag[]', $tags, '', '', '', true) ?>
        </td>
      </tr>
      <tr>
        <td>Or part of tag:</td>
        <td>
          <?php dbInput('liketag', $_REQUEST['liketag']) ?>
        </td>
      </tr>
      <tr>
        <td>And / Or part of content:</td>
        <td>
          <?php dbInput('dump_like', $_REQUEST['dump_like']) ?>
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
        <th>User ID</th>
        <th>Dump Text</th>
        <th>Tag</th>
    </tr>
  </thead>
  <tbody>
    <?php $i = 0; foreach($rows as $r): ?>
    <tr class="<?php echo $i % 2 == 0 ? 'fill-odd' : 'fill-even' ?>">
      <td><?php echo $r['created_at'] ?></td>
      <td><?php echo $r['user_id'] ?></td>
      <td><textarea><?php echo $r['dump_txt']  ?></textarea></td>
      <td><?php echo $r['tag'] ?></td>
    </tr>
    <?php $i++; endforeach ?>
  </tbody>
</table>
<br>
</div>
