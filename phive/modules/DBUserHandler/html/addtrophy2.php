<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

$user = phive('UserHandler')->getUserByUsername($_REQUEST['username']);

function addTrophy(){
  if(empty($_REQUEST['trophy_id']))
    return;
  if(isset($_REQUEST['username'])){
    $msg = 'Trophy was added successfully';
    $user = phive('UserHandler')->getUserByUsername($_REQUEST['username']);
    $ud = $user->data;
    $t = phive('Trophy')->get($_REQUEST['trophy_id']);
    phive("UserHandler")->logAction($user, "add_trophy");
    $bonus = phive('Trophy')->awardTrophy($t, $ud);    
    jsReload("");
  }
}

function chooseTrophy(){
  $th = phive('Trophy');
  $trophies = $th->getByCategory($_REQUEST['type']);
  $user = phive('UserHandler')->getUserByUsername($_REQUEST['username']);
  if(empty($user))
    return;
?>
  <p>Add trophy to player: <?php echo $user->getUsername(); ?></p>
  <form action="" method="post" accept-charset="utf-8">
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
    <input type="hidden" name="username" value="<?php echo $_REQUEST['username'] ?>"/>
    <input type="hidden" name="action" value="<?php echo $_REQUEST['action'] ?>"/>
    <p>
      <label for="choose_bonus">Choose Trophy:</label>
      <?php dbSelectWith("trophy_id", $trophies, 'id', 'alias', 'Select Reward') ?>
    </p>
    <p><input type="submit" value="Give Trophy"></p>
  </form>
  <br>
  </form>
  
  <?php
}

if(!p('give.trophy'))
  die('No permission');
  
$cats = phive('Trophy')->getCategories($user, 'category', '', 'trophy');  
  
?>
            Choose Trophy Type:
            <form method="POST">
              <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
              <input type="hidden" name="username" value="<?php echo $_REQUEST['username'] ?>"/>
              <input type="hidden" name="action" value="<?php echo $_REQUEST['action'] ?>"/>
              <?php
              dbSelect('type', $cats, $_REQUEST['type']);
              dbSubmit('submit');
              ?>
            </form>
            <?php
            addTrophy();
            if(!empty($_REQUEST['type']))
              chooseTrophy();
            ?>

