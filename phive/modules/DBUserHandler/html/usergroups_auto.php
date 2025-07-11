<?php

// TODO henrik remove

require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';
$uh = phive('UserHandler');
$groups = $uh->getAllGroups();
$res = array();
$fields = array('firstname', 'lastname', 'username', 'created_at', 'extend', 'terminate');
foreach($groups as $g)
  $res[$g->getName()] = array_map(function($u) use ($fields){
    $ud = $u->data;
    $ret = array();
    foreach($fields as $f)
      $ret[$f] = $ud[$f];
    $ret['created_at'] = phive()->hisMod('-7 day');
    $ret['extend'] = '<a href="">extend</a>';
    $ret['terminate'] = '<a href="">terminate</a>';
    return $ret;
  }, $g->getMembers());
?>
<div class="pad10">
  <?php $i = 0; foreach($res as $gname => $members): ?>
    <p><strong><?php echo $gname ?></strong></p>
    <?php drawTable($members, $fields) ?>
  <?php $i++; endforeach; ?>
</div>
