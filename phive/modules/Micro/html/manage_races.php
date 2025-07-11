<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../api/crud/crud.php';
?>
<div class="pad10">
<p><strong>Race type</strong>: spins (atm only spins is doable).</p>
<p><strong>Display as</strong>: race (atm only race is doable).</p>
<p><strong>Levels</strong>: single number, for instance 25 which then acts as a threshold, OR for instance 25:1|100:2|200:3 where a bet of 25 cents generates one spin, 100 generates 2 and so on.</p>
<p><strong>Prizes</strong>: iPad:Galaxy S4 etc, OR 100:50:25. Note that this field controls how many people will show in the race box too.</p>
<p><strong>Game categories</strong>: for example slots,videoslots note the comma separation.</p>
<p><strong>Games</strong>: the Ext game name of the game, separated with commas, for instance mgs_cops_and_robbers,mgs_billion_dollar_gran. If this field is set it will override Game categories completely, in fact if this field is set Game categories should be empty.</p>
<p><strong>End time</strong>: on the format yyyy-mm-dd hh:mm:ss, for example 2014-05-05 23:59:59.</p>
<p><strong>Start time</strong>: on the format yyyy-mm-dd hh:mm:ss, for example 2014-05-04 00:00:00.</p>
<p><strong>Closed</strong>: used by the logic to mark which races have been paid or not, <strong>leave this one as it is, it is set automatically by the system</strong>.</p>
</div>
<?php

$crud = Crud::table('races');
$crud->renderInterface('id', array(), true, array(
  'race_type'       => 'spins',
  'display_as'      => 'race',
  'game_categories' => 'slots,videoslots'
));
