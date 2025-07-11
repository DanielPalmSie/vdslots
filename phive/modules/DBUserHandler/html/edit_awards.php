<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../api/crud/crud.php';
$crud = Crud::table('trophy_awards', true);
$cur_date = date('Y-m-d');

?>
<div class="pad10">
<strong>Type is one of the following:</strong>
<br>
<br>
<strong>top-up</strong> is free cash being awarded on next deposit. Ex: a multiplicator value of 1.5 tops up a 20 EUR deposit with an extra 10 EUR. The amount value is used to set a cap on how much money that can be topped up. Ex: setting it to 500 will cap the top up to 5 EUR or 50 SEK.
<br>
<br>
<strong>race-spins</strong> adds the value in amount to the player's spins in all active races the player is participating in.
<br>
<br>
<strong>race-multiply</strong> amount is the amount of spins being multiplied, after that many spins have been spun the multiplicator stops multiplying. Multiplicator is the multiplicator, setting it to 2 results in 2 spins being recorded for a 25 cent bet.
<br>
<br>
<strong>cashback-multiply</strong> same as race-multiply but applies to the amount of Weekend Booster being given for X number of spins.
<br>
<br>
<strong>xp-multiply</strong> multiplies the amount of xp given by the value in multiplicator, is permanent but unique which means that if one award sets the value to 1.5 and a subsequent award sets it to 2 the resulting XP multiplication that the player enjoys is 2, not 3.5.
<br>
<br>
<strong>deposit</strong> if deposit bonus, needs the bonus id field to be set.
<br>
<br>
<strong>freespin</strong> if freespin bonus, needs the bonus id field to be set.
<br>
<p><strong>Valid days and Own valid days</strong> controls the amount of time the award can be owned without being activated before it expires and the amount of time the award is active while being owned. Own valid days currently applies to race-multiply and cashback-multiply awards.</p>
<p><strong>Description</strong> used in drop downs.</p>
</div>
<?php
$crud->renderInterface('id', array('bonus_id' => array('table' => 'bonus_types', 'idfield' => 'id', 'dfield' => 'bonus_name', 'defval' => 'Select Bonus', 'where' => "AND `type` = 'reward' AND expire_time > '$cur_date'")), true, array(
  'valid_days'        => 7,
  'own_valid_days'    => 7
));

