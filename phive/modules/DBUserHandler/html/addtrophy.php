<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

function addTrophy(){
  if(empty($_REQUEST['trophy_id']))
    return;
  if(isset($_REQUEST['user_id'])){
    $msg = 'Trophy was added successfully';
    $ud = cu($_POST['user_id'])->data;
    $t = phive('Trophy')->get($_REQUEST['trophy_id']);
    $bonus = phive('Trophy')->awardTrophy($t, $ud);    
    jsReload("?username=".$ud['username']."&action=trophies");
  }
}

function chooseTrophy(){
  $th = phive('Trophy');
  $trophies = $th->getByCategory($_REQUEST['type']);
  $user = cu($_REQUEST['user_id']);
  if(empty($user))
    return;
?>
  <p>Add trophy to player: <?php echo $user->getUsername(); ?></p>
  <form action="" method="post" accept-charset="utf-8">
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
    <input type="hidden" name="user_id" value="<?php echo $_REQUEST['user_id'] ?>"/>
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
  
$user = cu($_REQUEST['user_id']);
$cats = phive('Trophy')->getCategories($user, 'category', '', 'trophy');  
  
?>
  <div class="pad10">
    <table>
      <tr>
        <td>
            Choose Trophy Type:
            <form action="?user_id=<?php echo $_REQUEST['user_id'] ?>" method="POST">
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
        </td>
      </tr>
    </table>
  </div>

