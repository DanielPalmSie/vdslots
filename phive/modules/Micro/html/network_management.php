<?php
require_once __DIR__ . '/../../../admin.php';
if(!empty($_POST['network']) && isset($_POST['active'])){
  $active  = phive('SQL')->escape($_POST['active'],false);
  $network = phive('SQL')->escape($_POST['network'],false);
  if($active == 1)
    $where = " AND retired = 0 ";
  phive("SQL")->shs()->query("UPDATE micro_games SET active = $active WHERE network = '$network' $where");
  echo "<br>Games were updated, <strong>don't forget to clear the query cache if need be</strong>.<br>";
}

if(!empty($_POST['network']) && isset($_POST['enabled'])){
  $enabled  = phive('SQL')->escape(trim($_POST['enabled']),false);
  $network = phive('SQL')->escape(strtolower(trim($_POST['network'])),false);
  phive("SQL")->shs()->query("UPDATE micro_games SET enabled = $enabled WHERE network = '$network' $where");
  echo "<br>Games were updated, <strong>don't forget to clear the query cache if need be</strong>.<br>";
}

?>
<div style="padding: 10px;">
  <?php if(p('hide.network')): ?>
    Show / Hide games:<br/>
    <form method="post" action="">
      <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
      Network (nyx, bsg, sheriff, playngo or microgaming): <?php dbInput("network") ?>
      <br/>
      <br/>
      Hide / Show action, 0 to deactivate, 1 to activate: <?php dbInput("active") ?>
      <br/>
      <br/>
      <?php dbSubmit("Submit") ?>
    </form>
  <?php endif ?>    
  <br/>
  <br/>
  <br/>
  <?php if(p('disable.network')): ?>
    Show Under Construction pic:<br/>
    <form method="post" action="">
      <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
      Network (nyx, bsg, sheriff, playngo or microgaming): <?php dbInput("network") ?>
      <br/>
      <br/>
      1 to hide pic (enable game), 0 to show pic (disable game): <?php dbInput("enabled") ?>
      <br/>
      <br/>
      <?php dbSubmit("Submit") ?>
    </form>
  <?php endif ?>    
</div>
