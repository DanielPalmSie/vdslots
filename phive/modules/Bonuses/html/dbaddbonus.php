<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

function chooseBonus($user_id, $type){  
  $bh = phive('Bonuses');
  if($user_id):
    $user = cu($user_id);
  if($user):
    $nondeposits = $bh->getNonDeposits(date('Y-m-d'), $type);
    $deposits = phive('Bonuses')->getReloadsAndDeposits(phive()->today(), $type);
?>
  <p>Add Normal bonus to user: <?php echo $user->getUsername(); ?></p>
  <form action="" method="post" accept-charset="utf-8">
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>"/>
    <p>
      <label for="choose_bonus">Choose bonus:</label>
      <?php dbSelectWith("bonus_id", $bh->filterBlockedCountries($user, $nondeposits), 'id', 'bonus_name') ?>
    </p>
    <p>Comment :<input type="text" name="comment" value=""></p>
    <p><input type="submit" value="Add bonus"></p>
  </form>
  <br>
  <br>
  <p>Add deposit/reload bonus to user: <?php echo $user->getUsername(); ?></p>
  <form action="" method="post" accept-charset="utf-8">
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>"/>
    <p>
      <label for="choose_bonus">Choose bonus:</label>
      <?php dbSelectWith("bonus_id", $bh->filterBlockedCountries($user, $deposits), 'id', 'bonus_name') ?>
    </p>
    <p>Deposit amount <strong>in cents/öre:</strong><input type="text" name="amount" value=""></p>
    <p>Comment :<input type="text" name="comment" value=""></p>
    <p><input type="submit" value="Add bonus"></p>
  </form>
  
<?php
endif;
endif;
}
  
function addBonus(){
  $uh = phive('UserHandler');
    if(isset($_POST['user_id'])){
        $uid = $_POST['user_id'];
    $msg = 'Bonus was added successfully';
    $comment = $_POST['comment'];
    $bonus = phive('Bonuses')->getBonus($_POST['bonus_id'], false);

    if(!empty($_POST['amount'])){
      $entry_id = phive('Bonuses')->addDepositBonus($_POST['user_id'], $_POST['bonus_id'], $_POST['amount']);
      $res = true;
      if(!phive('Bonuses')->isAddedError($entry_id)){        
        if(phive('Bonuses')->activatePendingEntry($entry_id, $uid))
          $uh->logAction($_POST['user_id'], " activated bonus with id ".$_POST['bonus_id'], 'activated-bonus', true, cu());
      }else
        $msg = ucfirst($entry_id);
    }else{        
        $entry_id = phive('Bonuses')->addUserBonus($_POST['user_id'],$_POST['bonus_id'], true);
        if(phive('Bonuses')->isAddedError($entry_id))
            $msg = $entry_id;
        else
            $uh->logAction($_POST['user_id'], " activated bonus with id ".$_POST['bonus_id'], 'activated-bonus', true, cu());                
    }

    if ($entry_id)  
      cu($_POST['user_id'])->addComment($comment, 0, 'bonus_entries', $entry_id, 'entry_id');
    
    $username = ud($_POST['user_id'])['username'];
    echo "<h2>$msg.</h2><br>";
    
    $link2 = "/account/$username/my-bonuses/"; 
    $link = "/admin/userprofile/?username=$username&action=bonuses"; ?>
    <div style="text-decoration: underline;" onclick="parent.goTo('<?= $link2 ?>')">To users bonuses in account page.</div>
    <div style="text-decoration: underline;" onclick="parent.goTo('<?= $link ?>')">To user bonuspage in backoffice.</div><?php
  }
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
          Choose Bonus Type:
          <form action="?user_id=<?php echo $_REQUEST['user_id'] ?>&action=bonuses" method="POST">
            <?php
            $types = phive('Bonuses')->getBonusTypes();
            dbSelect('type', $types, $_REQUEST['type']);
            dbSubmit('submit');
            ?>
          </form>
          <br/>
          <?php
          addBonus();
          if(!empty($_REQUEST['type']))
            chooseBonus($_GET['user_id'], $_REQUEST['type']);
          ?>
        </td>
      </tr>
    </table>
  </div>

