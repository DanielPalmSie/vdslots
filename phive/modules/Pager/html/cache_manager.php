<?php
require_once __DIR__ . '/../../../admin.php';

if(!empty($_POST['clear_query_cache']))
    $res = mCluster('qcache')->delAll("*.qcache");

//if(!empty($_POST['clear_localizer_cache']))
//  $res = phM('delAll', "localizer*");
?>
<div style="padding: 10px;">
  <strong>Delete cache with the following keys:</strong><br/>	
  <?php if(!empty($_POST['submit']) || !empty($_POST['clear_query_cache']) || !empty($_POST['clear_localizer_cache'])): ?>
    <strong>Deleted keys:</strong>
    <br/>
    <?php foreach($res as $key): ?>
      <?php echo $key ?><br/>
    <?php endforeach ?>
  <?php endif ?>
  <form action="" method="post">
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
    <table class="v-align-top">
      <tr>
	<td>
	  Languages:<br/>
	  <?php foreach(phive("Localizer")->getAllLanguages() as $l): ?>
	    <?php echo $l['language'].": ";  dbCheck("lang[{$l['language']}]", 1); ?><br/>
	  <?php endforeach ?>
	</td>
	<td>
	  Currencies:<br/>
	  <?php foreach(phive("Currencer")->getAllCurrencies() as $c): ?>
	    <?php echo $c['code'].": ";  dbCheck("currency[{$c['code']}]", 1); ?><br/>
	  <?php endforeach ?>
	</td>
	<td>
	  States:<br/>
	  Logged in: <?php dbCheck("state[loggedin]", 1); ?><br/>
	  Logged out: <?php dbCheck("state[loggedout]", 1); ?><br/>
	</td>
      </tr>
      <tr>
	<td>
	  <?php dbSubmit("Clear Page Cache") ?>
	</td>
	<td>
	  <?php dbSubmit("Clear Query Cache", 'clear_query_cache') ?>
	</td>
      </tr>
    </table>
  </form>
</div>
