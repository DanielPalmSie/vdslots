<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

function chooseBonus($username, $type){  
  $user = phive('UserHandler')->getUserByUserName($_REQUEST['username']);
  $user_id = $user->getId();

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
    <input type="hidden" name="username" value="<?php echo $_REQUEST['username']; ?>"/>
    <p>
      <label for="choose_bonus">Choose bonus:</label>
      <?php dbSelectWith("bonus_id", $bh->filterBlockedCountries($user, $nondeposits), 'id', 'bonus_name') ?>
    </p>
    <p><input type="submit" name="submit" value="Add bonus"></p>
  </form>
  <br>
  <br>
  <p>Add deposit/reload bonus to user: <?php echo $user->getUsername(); ?></p>
  <form action="" method="post" accept-charset="utf-8">
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
    <input type="hidden" name="username" value="<?php echo $_REQUEST['username']; ?>"/>
    <p>
      <label for="choose_bonus">Choose bonus:</label>
      <?php dbSelectWith("bonus_id", $bh->filterBlockedCountries($user, $deposits), 'id', 'bonus_name') ?>
    </p>
    <p>Deposit amount <strong>in cents/öre:</strong><input type="text" name="amount" value=""></p>
    <p><input type="submit" name="submit" value="Add bonus"></p>
  </form>
  
<?php
  endif;
  endif;
}
  
function addBonus(){
    $uh = phive('UserHandler');
    if(isset($_REQUEST['submit'])){
        $msg = 'Bonus was added successfully';
        $user = phive('UserHandler')->getUserByUserName($_REQUEST['username']);
        $uid = $user->getId();
        $bonus = phive('Bonuses')->getBonus($_REQUEST['bonus_id'], false);

        if(!empty($_REQUEST['amount'])){
            $entry_id = phive('Bonuses')->addDepositBonus($uid, $_REQUEST['bonus_id'], $_REQUEST['amount']);
            
            $res = true;
            if(!phive('Bonuses')->isAddedError()){        
                if(phive('Bonuses')->activatePendingEntry($entry_id, $uid))
                    $uh->logAction($user->getId(), " activated bonus with id ".$_REQUEST['bonus_id'], 'activated-bonus', true, cu());
            }else
            $msg = ucfirst($entry_id);
        }else if ($_REQUEST['bonus_id']){
            $entry_id = phive('Bonuses')->addUserBonus($user->getId(),$_REQUEST['bonus_id'], true);
            if(phive('Bonuses')->isAddedError($entry_id))
                $msg = ucfirst($entry_id);
            else
                $uh->logAction($user->getId(), " activated bonus with id ".$_REQUEST['bonus_id'], 'activated-bonus', true, cu());
        }
        
        if ($msg)
            echo "<h2>$msg.</h2><br>";
        $link2 = "/account/$username/my-bonuses/"; 
        $link = "/admin/userprofile/?username=$username&action=bonuses";
?>
<div style="text-decoration: underline;" onclick="parent.goTo('<?= $link2 ?>')">To user bonuspage in backoffice.</div>
<div style="text-decoration: underline;" onclick="parent.goTo('<?= $link ?>')">To user bonuspage.</div>
<?php
}
}

?>
  <div class="pad10">
    <h3>Add a new bonus</h3>
    <table>
      <tr>
        <td style="vertical-align: top;">
          Choose Bonus Type:
          <form action="?username=<?php echo $_REQUEST['username'] ?>&action=addbonus" method="POST">
                <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
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
            chooseBonus($_GET['username'], $_REQUEST['type']);
          ?>
        </td>
      </tr>
    </table>
  </div>


  
