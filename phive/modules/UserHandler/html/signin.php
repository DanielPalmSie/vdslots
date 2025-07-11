<?php
require_once __DIR__ . '/../../../phive.php';

$domain = phive()->getSetting('domain');
$full_domain = phive()->getSetting('full_domain');

$admin_log = phive()->getSetting('admin_log');
$user = cu();

if(phive()->moduleExists('IpGuard'))
    phive('IpGuard')->check($domain );

if (phive('BrandedConfig')->isProduction() && !$user) {
    if(isset($admin_log['domain']) && $admin_log['domain'] != $full_domain){
        ?>
        <script>
            window.location = 'https://<?= $admin_log['domain'].'/'.$admin_log['path']; ?>';
        </script>
        <?php
    }
}


require_once __DIR__ . '/login.php';
$uh = phive('UserHandler');

if($user):
    setSid(session_id(), uid($user), $user);
?>


User logged in: <?=$user->getUsername()?><br />
<a href="/admin_log/?logout">Log out</a>

<?php else: ?>
<form method="post" id="login_form" action="">
  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
  <table>
    <tr class="topwindow_login_row">
      <td class="topwindow_login_label">
	Username:
      </td>
      <td class="topwindow_login_input">
	<div style="float: right">
	  <input type="text" name="login_name" />
	</div>
      </td>
    </tr>
    <tr class="topwindow_login_row">
      <td class="topwindow_login_label">
	Password:
      </td>
      <td class="topwindow_login_input">
	<div style="float: right">
	  <input type="password" name="password" />
	</div>
      </td>
    </tr>		
    <tr class="topwindow_login_buttons_row">
      <td colspan="2" class="topwindow_login_buttons">
	<div>
	  <div style="float: left">
	    <input type="submit" name="login" value="Submit" />
	  </div>
	</div>
	<?php if($_SESSION['login_failed']): ?>
	  <div>
	    <table class="capsule">
	      <tr>
		<td class="capsule_left"></td>
		<td class="capsule_middle">
		  <p class="topwindow_login_wrongpassword error">
		    <?=$_SESSION['login_failed']?>
		  </p>
		</td>
		<td class="capsule_right"></td>
	      </tr>
	    </table>
	  </div>
	<?php endif; ?>
      </td>
    </tr>
  </table>
</form>
<?php endif; ?>
