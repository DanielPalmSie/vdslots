<?

// TODO henrik remove this

require_once __DIR__ . '/../../../admin.php';

$uh = phive('UserHandler');
$perpage = 30;

if ($_GET['page'])
  $page = (float)$_GET['page'];
else
  $page = 0;
$limit = 'LIMIT ' . $page*$perpage . ', ' . $perpage;

if ($_GET['group']){
  $group = $uh->getGroup($_GET['group']);
  $users = $group->getMembers($limit);
  $count = $group->memberCount();
  echo "<p>Members of group {$group->getName()} (total: $count)</p>";
}
?>
<?php if ($count > $perpage): ?>
  <hr />
  <?php for ($i=0; $i < ceil($count/$perpage); ++$i): ?>
    <?php $_GET['page'] = $i; ?>
    <a href="<?=Pager::getGets()?>"><?=$i?></a>
  <?php endfor; ?>
<?php endif; ?>
<hr />
<table>
  <?php $first = true; 
  foreach ($users as $user):
    $data = $user->getData(1);
  if ($first):
    $first=false; ?>
    <tr>
      <?php foreach($data as $key=>$element): ?>
	<?php if ($key!='password'): ?>
	  <th align="left"><?=$key?></th>
	<?php endif; ?>
      <?php
      endforeach; 
      endif;
      ?>
    </tr>
    <tr>
    <?php
    foreach ($data as $key=>$element):
    if($key=='password')
      continue;
    ?>
	<td>
	  <?php if ($key=='username'): ?>
	    <a href="?p=editusers&amp;id=<?=$user->getId()?>"><?=$element?></a>
	  <?php else: ?>
	    <?=$element?>
	  <?php endif; ?>
	</td>
      <?php endforeach; ?>
    </tr>
  <?php endforeach; ?>
</table>
