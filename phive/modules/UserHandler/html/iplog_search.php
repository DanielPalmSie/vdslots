<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';
$uh = phive("UserHandler");

if(!empty($_REQUEST['submit'])){
  if(empty($_REQUEST['sdate'])){
    $sdate = phive()->hisMod('-1 day');
    $edate = phive()->hisNow();
  }else{
    $sdate = $_REQUEST['sdate'];
    $edate = $_REQUEST['edate'];
  }
  
  $where = '';
  if(!empty($_REQUEST['username'])){
    $user = cu($_REQUEST['username']);
    if(is_object($user))
      $where .= " AND il.target = {$user->getId()} ";
  }
  
  if(!empty($_REQUEST['tag']))
    $where .= " AND il.tag = '{$_REQUEST['tag']}'";
  
  if(!empty($_REQUEST['descr']))
    $where .= " AND il.descr LIKE '%{$_REQUEST['descr']}%'";

    //    LEFT JOIN users AS a ON a.id = il.actor
    // a.username AS actor
  $str = "
    SELECT il.* FROM ip_log il
    -- LEFT JOIN users AS t ON t.id = il.target
    WHERE created_at >= '$sdate' 
    AND created_at <= '$edate' $where";

    $rows = phive('SQL')->shs('merge', '', null, 'ip_log')->loadArray($str);
}

?>
<?php drawStartEndJs() ?>
<div class="pad10">
  <?php drawTable($rows) ?>
  <form action="" method="get">
    <table>
      <?php drawStartEndHtml() ?>
      <tr>
        <td>Username:</td>
        <td><?php dbInput('username') ?></td>
      </tr>
      <tr>
        <td>Tag:</td>
        <td><?php dbSelect('tag', array('login' => 'Login', 'cash_transaction' => 'Cash', 'group' => 'Group')) ?></td>
      </tr>
      <tr>
        <td>Part of description:</td>
        <td><?php dbInput('descr') ?></td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>
          <?php dbSubmit('Submit') ?>
        </td>
      </tr>
    </table>
  </form>
</div>

