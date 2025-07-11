<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../api/crud/crud.php';
$t = phive('Trophy')->get((int)$_GET['id']);

if(isset($_REQUEST['filter']))
    $_SESSION['trophy_filter'] = $_REQUEST['filter'];

if(!empty($_FILES["files"]))
  phive('Filer')->uploadMulti();
?>
<script>
 $(document).ready(function(){
   $("#edit-string").click(function(){
     var alias = $("input[name='alias']").val();
     goToBlank("<?php echo phive('UserHandler')->getSiteUrl() ?>/phive/modules/Localizer/html/editstrings.php?arg0=en&arg1=trophyname."+alias);
   });
 });
</script>
<div class="pad10">
  <button id="edit-string">Translate / Edit Trophy name</button>
  <br/>
  <?php if(!empty($t)): ?>
    <img src="<?php echo getMediaServiceUrl() . "/file_uploads/events/{$t['alias']}_event.png" ?>" />
    <img src="<?php echo getMediaServiceUrl() . "/file_uploads/events/grey/{$t['alias']}_event.png" ?>" />
  <?php else: ?>
    <p>Trophy pictures will show here if you're in update mode.</p>
  <?php endif ?>
  <p>
    Choose what type upload, <strong>do this before you select files and make sure it's correct otherwise you'll end up uploading a grey picture in the coloured folder!</strong><br/>
    <?php dbSelect('folder', array('events' => 'Coloured', 'events/grey' => 'Grey'), '') ?>
    <?php multiUpload(array('extra' => array('folder'))) ?>
  </p>
  <p>
      Tip: Use "?filter=game_ref" in the URL to filter on game_ref below. Filter is saved in the session. Use "?filter=" to clear the filter.
  </p>
</div>
<?php
$crud = Crud::table('trophies', true)->setWhere("game_ref LIKE '%{$_SESSION['trophy_filter']}%'");
$crud->renderInterface('id', array(
  'award_id' => array('table' => 'trophy_awards', 'idfield' => 'id', 'dfield' => 'description', 'defkey' => '', 'defval' => 'Select Reward'),
  'award_id_alt' => array('table' => 'trophy_awards', 'idfield' => 'id', 'dfield' => 'description', 'defkey' => '', 'defval' => 'Select Alternative Reward')
), true, array(
	'in_row'      => 0,
	'time_period' => 0
));
