<?php
require_once __DIR__ . '/../../api/PhModule.php';
class UserSearch extends PhModule{

  function __construct(){
    $this->sql = phive('SQL');
  }

  function renderUsersSimple($cols, $users, $lang = false, $playcheck = false){
      $cell_perm = p('account.view.cellphone');
      $email_perm = p('account.view.email');
      $row = 0;
      foreach($users as $user): ?>
      <tr class="<?php echo ($row % 2 == 0) ? "fill-odd" : "fill-even" ?>">
        <?php foreach($cols as $field): ?>
          <td class="field-<?php echo $field ?>">
            <?php if ($field == 'backend'): ?>
              <a href="/admin/userprofile/?username=<?=$user['username']?>">Go to profile</a>
            <?php elseif ($field == 'username'): ?>

              <?php if($lang): ?>
                <a href="<?php echo llink("/account/{$user['username']}/") ?>"><?=$user['username']?></a>
              <?php else: ?>
                <a href="/account/<?=$user['username']?>/"><?=$user['username']?></a>
              <?php endif ?>
              
            <?php elseif ($field == 'affiliate'): ?>
              <?php if($lang): ?>
                <a href="<?php echo llink("/affiliate/account/{$user['affiliate']}/") ?>"><?=$user['affiliate']?></a>
              <?php else: ?>
                <a href="/affiliate/account/<?=$user['affiliate']?>/"><?=$user['affiliate']?></a>
              <?php endif ?>
            <?php elseif ($field == 'cellphone' || $field == 'mobile'): ?>
              <?php if($user['hide_phone'] == 0 || $cell_perm): ?>
                <?=$user[$field]?>
              <?php endif ?>
            <?php elseif ($field == 'email'): ?>
              <?php if($email_perm): ?>
                <?=$user['email']?>
              <?php endif ?>
            <?php elseif ($field == 'playcheck'): ?>
              <a target="_blank" rel="noopener noreferrer" href="<?php echo "/phive/modules/Micro/playcheck.php?uid={$user['id']}" ?>">
                PlayCheck
              </a>
            <?php else:?>
              <?php echo $user[ $field ] ?>
            <?php endif ?>
          </td>
        <?php endforeach ?>
      </tr>
      <?php
      $row++;
      endforeach;
  }

  function prSendToBrowseUsersForm($arr, $url = '/admin/browseusers/'){
    $user_ids = implode(',', array_unique(phive()->arrCol($arr, 'user_id')));
    ?>
      <form action="<?php echo htmlspecialchars($url); ?>" method="post">
        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
        <input name="search" type="submit" value="Send users to browse users" />
        <input name="user_ids" type="hidden" value="<?php echo $user_ids ?>" />
      </form>
    <?php
  }

  function csvBtn(){
    if(!p('download.stats.csv'))
      return;
    ?>
    <input name="as_csv" type="submit" value="As CSV" />
  <?php }

  function handleCsv($arr, $keys = array(), $labels = array()){
    if(empty($keys))
      $keys = array_keys(current($arr));
    if(empty($arr) || empty($_REQUEST['as_csv']))
      return;
    require_once __DIR__ . '/../../vendor/autoload.php';

      if(!empty($keys))
          $arr = phive()->moveit($keys, $arr, array());

    foreach($arr as &$r){
      foreach($r as $field => &$val){
        if(is_numeric($val) && !in_array($field, array('cnt', 'user_id', 'ndeposits', 'nwithdrawals', 'newsletter')))
          $val = round($val / 100, 2);
      }
    }
    $uniq = uniqid();
  ?>
    <h2><a href="/phive/modules/Filer/download_csv.php?fbody=<?php echo $uniq ?>" target="_blank" rel="noopener noreferrer">Download CSV</a></h2>
  <?php }

  function showCsv($stats, $csv_cols = []){ ?>
    <?php if(!empty($_REQUEST['as_csv'])): ?>
      <br>
      <br>
      <?php $this->handleCsv($stats, $csv_cols, $csv_cols) ?>
    <?php endif ?>
  <?php }
}
