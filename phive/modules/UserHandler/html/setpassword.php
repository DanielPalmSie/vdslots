<?php
require_once __DIR__ . '/../../../admin.php';

if (isset($_POST['username']) && isset($_POST['password'])){
    $user = cu($_POST['username']);

    if (!$user)
        $msg = "No such user";
    else if(p('admin_top', $user))
        $msg = "Not a player";
    else if (phive()->moduleExists('Permission') && !p('users.reset_passwords'))
        $msg = "You don't have permission to reset passwords";
    else if(lic('disableBoPwdChange', [], $user)){
        $msg = "Password changes by admin staff is not allowed for {$user->getCountry()} players.";
    } else {
        $user->setPassword($_POST['password'], true);
        $msg = "Password updated successfully.";
    }
}
if(p('users.reset_passwords')):
?>
    <div class="pad-stuff-ten">
        <p><strong><?php echo $msg ?></strong></p>
        <p>Change someone's password (username / new password)</p>
        <form method="post">
            <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
            Username: <input type="input" name="username" />
            New Password: <input type="password" name="password" />
            <input type="submit" value="Submit">
        </form>
    </div>
<?php endif ?>
