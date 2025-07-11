<?php
require_once __DIR__ . '/../../../../phive/html/display_base_diamondbet.php';
require_once __DIR__ . '/../../../../phive/admin.php';

die('This file needs some permissions, contact Henrik if you see this.');

  $c = phive('Config');
  if (!empty($_GET['edit'])){
    $user = phive('SQL')->loadAssoc("SELECT * FROM vip_notifications WHERE name LIKE '{".phive('SQL')->escape($_GET['edit'],false)."}'");
  }
  if (!empty($_GET['delete'])){
    phive('SQL')->query("DELETE FROM vip_notifications WHERE name LIKE '".phive('SQL')->escape($_GET['delete'],false)."'");
    jsRedirect('/admin/vip-notification');
  }
  if (!empty($_POST['adduser'])) {
    $insert = array(
      'name' => $_POST['name'],
      'email' => $_POST['email'],
      'phone' => $_POST['phone'],
      'receives' => $_POST['receives']
      );
    unset($user);
    phive('SQL')->save('vip_notifications', $insert);
    jsRedirect('/admin/vip-notification');
  }
  if (!empty($_POST['update_rules'])) {
    $insert = array(
      'config_tag' => 'vip-notification',
      'config_name' => 'deposit_amount_24h',
      'config_value' => $_POST['deposit_amount_24h']
      );
    phive('SQL')->save('config', $insert);
    $insert = array(
      'config_tag' => 'vip-notification',
      'config_name' => 'win_amount_one_spin',
      'config_value' => $_POST['win_amount_one_spin']
      );
    phive('SQL')->save('config', $insert);
    $insert = array(
      'config_tag' => 'vip-notification',
      'config_name' => 'cash_balance_over_x_amount',
      'config_value' => $_POST['cash_balance_over_x_amount']
      );
    phive('SQL')->save('config', $insert);
    jsRedirect('/admin/vip-notification');
  }
  $deposit_amount_24h = $c->getValue('vip-notification', 'deposit_amount_24h');
  $win_amount_one_spin = $c->getValue('vip-notification', 'win_amount_one_spin');
  $cash_balance_over_x_amount = $c->getValue('vip-notification', 'cash_balance_over_x_amount');
  $notifiees = phive('SQL')->loadArray("SELECT * FROM vip_notifications");
?>
<div class="pad10">
<h3>VIP Notification Settings</h3>
<form method="post">
<input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
<ol type="A">
  <li><input type="text" placeholder="5000" pattern="[0-9]+" value="<?= $deposit_amount_24h ?>" required name="deposit_amount_24h"/> Deposit amount in EUR <b>cents</b> in 24 hours</li>
  <li><input type="text" placeholder="5000" pattern="[0-9]+" value="<?= $win_amount_one_spin ?>" required name="win_amount_one_spin"/> Win amount in EUR <b>cents</b> in 1 spin</li>
  <li><input type="text" placeholder="5000" pattern="[0-9]+" value="<?= $cash_balance_over_x_amount ?>" required name="cash_balance_over_x_amount"/> Cash balance in EUR <b>cents</b> goes over X amount</li>
  <li style="list-style: none;"><input type="submit" name="update_rules" value="Update"/></li>
</ol>
</form>
<p></p>
<form method="post">
  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
  <table class="stats_table">
    <tr class="stats_header">
      <th>Name</th>
      <th>Email</th>
      <th>Phone</th>
      <th>Receives</th>
      <th>Action</th>
    </tr>
    <?php foreach ($notifiees as $n) : ?>
      <tr>
        <td><?= $n['name'] ?></td>
        <td><?= $n['email'] ?></td>
        <td><?= $n['phone'] ?></td>
        <td><?= $n['receives'] ?></td>
        <td><a href="?delete=<?= $n['name'] ?>">x</a> <a href="?edit=<?= $n['name'] ?>">e</a></td>
      </tr>
    <?php endforeach ?>
    <? if (!empty($user)) : ?>
      <tr>
        <td><input type="text" required name="name" value="<?= $user['name'] ?>"/></td>
        <td><input type="email" required name="email" value="<?= $user['email'] ?>"/></td>
        <td><input type="tel" required name="phone" value="<?= $user['phone'] ?>"/></td>
        <td><input type="text" required name="receives" value="<?= $user['receives'] ?>"/></td>
        <td><input type="submit" value="Update" name="adduser" /></td>
      </tr>
    <? else : ?>
      <tr>
        <td><input type="text" required name="name" placeholder="Alex"/></td>
        <td><input type="email" required name="email" placeholder="Email"/></td>
        <td><input type="tel" required name="phone" placeholder="0035699083066"/></td>
        <td><input type="text" required name="receives" placeholder="A C"/></td>
        <td><input type="submit" value="Add" name="adduser" /></td>
      </tr>
    <? endif ?>
  </table>
</form>
</div>
