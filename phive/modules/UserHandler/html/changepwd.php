<?php
require_once __DIR__ . '/../../../phive.php';
//require_once __DIR__ . '/login.php';
require_once __DIR__ . '/../../Former/Validator.php';

if(phive()->moduleExists('IpGuard'))
    phive('IpGuard')->check();

$uh = phive('UserHandler');
$r = $_REQUEST;

if(!empty($r['submit'])){
  if($r['new_password'] != $r['new_password_check'])
    $err = 'Passwords do not match.';
  else if($r['new_password'] == $r['password'])
    $err = 'New password is same as old.';
  else
    $err = PhiveValidator::start($r['new_password'])->strictPassword(8);
  
    $u = $uh->getUserByUsername($r['login_name']);
    
    $old = $uh->encryptPassword($r['password']);
    if($u->data['password'] != $old)
        $err = 'Wrong old pwd';
    
  if(!is_object($u))
    $err = 'No user.';
  
  if(empty($err)){
    $u->setAttr('password', $uh->encryptPassword($r['new_password']));
    $u->setSetting('last_pwd_update', phive()->today());
    $msg = 'Password successfully changed. <a href="/admin_log/">Login</a>';
  }
}

?>
<div class="pad10">
<p>
  <strong><?php echo $msg.$err ?></strong>
</p>

<form method="post" action="">
  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
  <table>
    <tr class="topwindow_login_row">
      <td class="topwindow_login_label">
	Username:
      </td>
      <td class="topwindow_login_input">
	<input type="text" name="login_name" />
      </td>
    </tr>
    <tr class="topwindow_login_row">
      <td class="topwindow_login_label">
	Old Password:
      </td>
      <td class="topwindow_login_input">
	<input type="password" name="password" />
      </td>
    </tr>
    <tr class="topwindow_login_row">
      <td class="topwindow_login_label">
        New Password:
      </td>
      <td class="topwindow_login_input">
	<input type="password" name="new_password" />
      </td>
    </tr>
    <tr class="topwindow_login_row">
      <td class="topwindow_login_label">
	New Password Again:
      </td>
      <td class="topwindow_login_input">
	<input type="password" name="new_password_check" />
      </td>
    </tr>
    <tr class="topwindow_login_buttons_row">
      <td colspan="2" class="topwindow_login_buttons">
	<div>
	  <div style="float: left">
	    <input type="submit" name="submit" value="Submit" />
	  </div>
	</div>
      </td>
    </tr>
  </table>
</form>

</div>
