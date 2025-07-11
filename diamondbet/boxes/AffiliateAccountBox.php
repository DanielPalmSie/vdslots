<?php
require_once __DIR__.'/AccountBox.php';
class AffiliateAccountBox extends AccountBox{

  function setup($route){
    if(count($route) == 4){
      if(strlen($route[0]) == 2)
        list($lang, $aff, $acc, $username) = $route;
      else
        list($aff, $acc, $username, $page) = $route;
    }else if(count($route) == 3){
      list($aff, $acc, $username) = $route;

      if(strlen($aff) == 2 && $username == 'account'){
        $username = $_SESSION['mg_username'];
      }else if($username == 'signup'){
        $page = $username;
        $username = '';
      }
    }else if(count($route) == 2){
      $username = $_SESSION['mg_username'];
    }else
      list($lang, $aff, $acc, $username, $page) = $route;

    return array($username, $page);
  }

  function is404($args){
    return count($args) > 4;
  }

  public function printHTML(){

    setCur($this->cur_user);

    if($_GET['signout'] == 'true'){
      phive('UserHandler')->logout();
      $this->jsRedirect('/');
      return;
    }

    if($this->canView() !== false){
      $acc_menu = phive('Menuer')->forRender('affiliate-menu', 'my-profile', true, $this->username);
      ?>
      <div class="profile-menu">
        <span style="font-size: 20px; float: left; padding-top: 10px; margin-left: 10px; "><?php echo t('affiliate.profile') ?></span>
        <ul>
          <?php foreach($acc_menu as $item): ?>
            <li>
              <a <?php echo str_replace("[user/]", urlencode($this->username)."/", $item['params']) ?>>
                <?php echo $item['current'] ? '&raquo;' : '' ?>
                <?php echo $item['txt']?>
                <?php echo $item['current'] ? '&laquo;' : '' ?>
              </a>
            </li>
          <?php endforeach ?>

          <?php if(p('affiliate.account.admin')): ?>
            <li>
              <a href="<?php echo llink("/affiliate/account/{$this->username}/admin/") ?>">
                <?php echo t('admin') ?>
              </a>
            </li>
          <?php endif ?>
        </ul>
      </div>
      <?php

      switch($this->page){
        case 'affadmin':
          $this->printAdmin();
          break;
        case 'admin':
          if(p('account.admin'))
            $this->printAffAdmin();
          break;
        case 'update-account':
          $this->jsRedirect('/affiliate');
          break;
        case 'signup':
          if(!isLogged()){
            $this->jsRedirect('/affiliate');
          }else
            $this->jsRedirect('/affiliate/account');
          break;
        default:
          if($this->canView() == false){
            $this->jsRedirect('/affiliate');
          }else{
            $user_id = $this->cur_user->getId();
            if(!empty($user_id))
              $this->printAffiliate();
            else
            $this->jsRedirect('/affiliate');
          }
          break;
      }
    }else
      $this->jsRedirect('/affiliate');

  }

  function manageBonusCodes(){

    $form_type = empty($_GET['form_type']) ? 'add' : $_GET['form_type'];

    if($form_type == 'update')
      $cur_code = phive('Affiliater')->getBonusCode($_GET['bonus_id']);

     if(!empty($_POST['submit_code']) && !empty($_POST['bonus_code'])){

       if($form_type == 'add'){
         if(!phive('Affiliater')->insertBonusCode($this->cur_user->getId(), $_POST['bonus_code'], $_POST['description']))
           $error = t('err.bonus_code.already');
       }

       if($form_type == 'update'){
         if(!phive('Affiliater')->updateBonusCode($_GET['bonus_id'], $this->cur_user->getId(), $_POST['bonus_code'], $_POST['description']))
           $error = t('err.bonus_code.already');
       }

     }

     if(!empty($_GET['delete'])){
       phive('Affiliater')->deleteBonusCode($_GET['delete'], $this->cur_user->getId());
     }
    ?>
    <div style="margin-left: 100px;">
    <div id="errorZone" class="errors">
      <?php echo $error ?>
    </div>
    <div>
      <table class="account-tbl">
        <tr>
          <td>
            <form class="registerform" action="?form_type=<?php echo $form_type ?>&bonus_id=<?php echo htmlspecialchars($_GET['bonus_id']) ?>" method="post">
              <p>
                 <h2> <?php et("$form_type.bonus.code") ?> </h2>
              </p>
              <p>
                <?php dbInput('bonus_code', $cur_code['bonus_code']) ?>
              </p>
              <p>
                 <h2> <?php et("$form_type.bonus.description") ?> </h2>
              </p>
              <p>
                <?php dbInput('description', $cur_code['description']) ?>
              </p>
              <input type="submit" value="<?php et('submit') ?>" name="submit_code" class="submit"/>
              <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
            </form>
          </td>
          <td style="width: 100px;"></td>
          <td style="vertical-align: top;">
            <p>
               <h2> <?php et('your.bonus.codes') ?> </h2>
            </p>
            <table class="zebra-tbl">
              <col width="100"/>
              <col width="200"/>
              <?php $i = 0; foreach(phive('Affiliater')->getBonusCodes($this->cur_user->getId()) as $b): ?>
              <tr class="<?php echo $i % 2 == 0 ? 'even' : 'odd' ?>" >
                <td><?php echo $b['bonus_code'] ?></td>
                <td><?php echo $b['description'] ?></td>
                <td> <a href="?bonus_id=<?php echo $b['id'] ?>&form_type=update"><?php et('update') ?></a> </td>
                <td> <a href="?delete=<?php echo $b['id'] ?>"><?php et('delete') ?></a> </td>
              </tr>
              <?php $i++; endforeach; ?>
            </table>
          </td>
        </tr>
      </table>
    </div>
    </div>
   <?php }

  function printAffiliate(){
    $_GET['user_id'] = $this->cur_user->getId();
    phive('ExtAffiliater')->setACreds($this->cur_user->getUsername(), $this->cur_user->getId());
    require_once __DIR__.'/../../phive/modules/Affiliater/html/ext-affiliate-admin.php';
    require_once __DIR__.'/../../phive/modules/Affiliater/html/affiliate-stats.php';
    require_once __DIR__.'/../../phive/modules/Affiliater/html/bcode-stats.php';
    $action = $_GET['action'] = $this->page;
    if(!phive('Affiliater')->isCasinoAffiliate($this->cur_user->getId()))
      phive('Affiliater')->insertCasinoAffiliate($this->cur_user->getId());
    ?>
      <?php if(empty($action) || $action == 'my-profile'): ?>
        <div class="frame-block">
          <div class="frame-holder2">
            <?php myAccount($this->cur_user->getId()); ?>
          </div>
        </div>
      <?php else: ?>
        <div class="frame-block">
          <div class="frame-holder<?php echo $action == 'campaignadmin' ? '' : 2 ?>">
            <?php
              switch($action){
                case 'bannerstats':
                  bannerStats();
                  break;
                case 'playerstats':
                  playerStats( $this->cur_user->getId() );
                  break;
                case 'my-players':
                  bCodeStats( $this->cur_user->getId() );
                  break;
                case 'bonuscodes':
                  $this->manageBonusCodes();
                  break;
                case 'campaignadmin':
                  $codes = phive('Affiliater')->getBonusCodes($this->cur_user->getId());
                  if(empty($codes))
                    echo t('no.codes.html').' <a href="'.$this->loc->langLink('', "/affiliate/account/{$this->username}/bonuscodes/").'">'.t('here').'</a>.';
                  else
                    extAdmin($this->cur_user->getId());
                  break;
              }
            ?>
          </div>
        </div>
      <?php endif ?>
  <?php }
}
