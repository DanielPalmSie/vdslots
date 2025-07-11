<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

// TODO henrik remove this.

function addAward(){
  if(empty($_REQUEST['award_id']))
    return;
  if(isset($_REQUEST['user_id'])){
    $msg = 'Reward was added successfully';
    $comment = $_POST['comment'];
    $ud = cu($_POST['user_id'])->data;
    $bonus = phive('Trophy')->giveAward($_POST['award_id'], $ud);    
    if ($bonus)  {
      phive("UserHandler")->logAction(cu($_POST['user_id']), "give_award");
      cu($_POST['user_id'])->addComment($comment, 0, 'trophy_awards_ownership', $bonus, 'id');
    }
    if (empty($bonus)) $msg = 'Could not add reward! :(';
    echo "<h2>$msg.</h2><br>";
    $link2 = "/account/{$ud['username']}/"; 
    $link = "/admin/userprofile/?username={$ud['username']}&action=bonuses"; ?>
    <div style="text-decoration: underline;" onclick="parent.goTo('<?= $link2 ?>')">To user profile.</div>
    <div style="text-decoration: underline;" onclick="parent.goTo('<?= $link ?>')">To user bonus page in backoffice.</div>
<?php
  }
}

function chooseAward(){
  $th = phive('Trophy');
  $rewards = $th->getAwardsByType($_REQUEST['type']);
  $rewards = phive()->sort2d($rewards, 'description');
  $user = cu($_REQUEST['user_id']);
  if(empty($user))
    return;
?>
  <p>Add reward to player: <?php echo $user->getUsername(); ?></p>
  <form action="" method="post" accept-charset="utf-8">
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
    <input type="hidden" name="user_id" value="<?php echo $_REQUEST['user_id'] ?>"/>
    <p>
      <label for="choose_bonus">Choose reward:</label>
      <?php dbSelectWith("award_id", $rewards, 'id', 'description', 'Select Reward') ?>
    </p>
   <p>Comment :<input type="text" name="comment" value=""></p>
   <p><input type="submit" value="Add reward"></p>
  </form>
  <br>
  </form>
  
  <?php
}

  $tmp = phive('Trophy')->getAwardTypes();
  $atypes = array();
  foreach($tmp as $at){
    if(p("reward.$at"))
      $atypes[$at] = $at;
  }
  
  
?>
<style>
  body { font-family: Arial, Helvetica, sans-serif; font-size: 12px !important; }
  table { font-size: 12px; }
</style>
  <div class="pad10">
    <table>
      <tr>
        <td>
            Choose Reward Type:
            <form action="?user_id=<?php echo $_REQUEST['user_id'] ?>&action=rewards" method="POST">
                  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">

              <?php
              dbSelect('type', $atypes, $_REQUEST['type']);
              dbSubmit('submit');
              ?>
            </form>
            <?php
            addAward();
            if(!empty($_REQUEST['type']) && $_REQUEST['action'] == 'rewards')
              chooseAward();
            ?>
        </td>
      </tr>
    </table>
  </div>

