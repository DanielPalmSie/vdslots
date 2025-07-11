<?php

require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../api/m/Form.php';
use MVC\Form as F;

$edit = false;
$sql = phive('SQL');

if (isset($_GET['gg'])) {
  $arr = $sql->loadArray("SELECT ext_game_name,game_name,device_type,device_type_num FROM micro_games WHERE network='" . addslashes($_GET['gg']) . "' GROUP BY game_name");
  echo json_encode($arr);
  die();
}

if (isset($_GET['atypes'])) {
  if (isset($_GET['nw'])) {
    $obj = $sql->loadArray("
      SELECT ta.id AS id,ta.description AS description FROM trophy_awards ta 
      LEFT JOIN bonus_types bt ON ta.bonus_id=bt.id 
      LEFT JOIN micro_games mc ON mc.game_id=bt.game_id 
      WHERE ta.type='freespin-bonus' AND mc.network='{$_GET['nw']}'");
  } else {
    $obj = $sql->loadArray("SELECT id,description FROM trophy_awards WHERE type='" . addslashes($_GET['atypes']) . "' GROUP BY description");
  }

  echo json_encode($obj);
  die();
}

if (isset($_GET['action']) && $_GET['action'] == "e") {
  $edit = true;
  $sql = phive('SQL');
  $xt = $sql->loadObject("SELECT * FROM trophies WHERE id = {$_GET['id']}", 'ASSOC');
  $network = $sql->loadObject("SELECT network FROM micro_games WHERE ext_game_name='{$xt->game_ref}'");
  $trids = array("0", "0");
  if (!empty($xt->award_id) || !empty($xt->award_id_alt)) {
    $taids = $sql->makeIn(array($xt->award_id, $xt->award_id_alt));
    $trids = $sql->loadKeyValues("SELECT id,type FROM trophy_awards WHERE id IN ({$taids})", 'id', 'type');
    if (isset($xt->award_id) && $trids[$xt->award_id] == 'freespin-bonus') 
      $award_id_network = getNetwork($xt->award_id, 'award_id');

    if (isset($xt->award_id_alt) && $trids[$xt->award_id_alt] == 'freespin-bonus') 
      $award_id_alt_network = getNetwork($xt->award_id_alt, 'award_id_alt');
  }
}

function imgshow($img) {
  $dir = phive('Filer')->getSetting("UPLOAD_PATH") . "/";
  if (!file_exists($dir . $img)) {
    return sprintf("%s doesn't exist", $dir . $img);
  } else {
    return sprintf('<img src="'.getMediaServiceUrl().'/file_uploads/%s" alt="" />', $img);
  }
}

function getNetwork($id, $col = 'award_id') {
  return phive('SQL')->getValue("
    SELECT COALESCE(mc.network) FROM trophies t 
    JOIN trophy_awards aw ON t.$col = aw.id 
    INNER JOIN bonus_types b ON aw.bonus_id = b.id 
    INNER JOIN micro_games mc ON b.game_id = mc.ext_game_name 
    WHERE t.$col = $id ORDER BY network LIMIT 1");
}

function Selected($xt, $var, $else = null) {
  $return = (isset($xt) && is_object($xt)) ? $xt->$var : "";
  return ($return == "" && $else != null) ? $else : $return;
}

$types = $sql->loadKeyValues("SELECT DISTINCT type FROM trophies", 'type', 'type');
$subtypes = $sql->loadKeyValues("SELECT DISTINCT subtype FROM trophies", 'subtype', 'subtype');
$trophyawardstypes = $sql->load1DArr("SELECT DISTINCT type FROM trophy_awards", 'type');
$categories = $sql->loadKeyValues("SELECT DISTINCT category FROM trophies", 'category', 'category');
array_unshift($trophyawardstypes, '&lt;empty&gt;');

$networks = array(0 => "&lt;select network&gt;", "bsg" => "bsg", "microgaming" => "microgaming", "netent" => "netent", "nyx" => "nyx", "yggdrasil" => "yggdrasil", "playngo" => "playngo", "multislot" => "multislot");
$timespans = array(0 => "", "hour" => "hour", "day" => "day");
$devicetypes = array(0 => "flash", "html5");

if (!empty($_GET["ql"])) {
  $sql = phive('SQL');
  $search = $_GET["ql"];
  $trophies = $sql->loadArray("SELECT id,alias FROM trophies WHERE alias LIKE '%{$search}%'");

  echo '<table><tr><th>Trophy</th><th>Actions</th><tr/>';
  foreach ($trophies as $trophy) {

    echo '<tr><td>' . $trophy["alias"] . '</td><td><a href="/admin/trophy-wizard/?action=e&id=' . $trophy["id"] . '">Edit</a></td></tr>';

  }
  echo '</table>';
  die();
}

?>
<script type="text/javascript">
 $(document).ready(function(){
   var network;
   var game_name;
   <?php if ($edit === true): ?>
     $('#formsubmit').val("Update");
     var game_ref = '<?=$xt->game_ref?>';
     var rew1_id = '<?=$xt->award_id?>' || '0';
     <?php if (isset($award_id_network)): ?>
       var rew1_net = '<?=$award_id_network?>';
     <?php endif ?>
     <?php if (isset($award_id_alt_network)):?>
       var altrew_net = '<?=$award_id_alt_network?>';
     <?php endif ?>
     var altrew_id ='<?=$xt->award_id_alt?>' || '0';
     var rew1_t = '<?=$trids[$xt->award_id]?>' || '0';
     <?php if(!empty($xt->award_id_alt)): ?>
       var altrew_t = '<?=$trids[$xt->award_id_alt]?>';
     <?php endif ?>
   <?php endif ?>
   $('#strophybyalias').focus();

   $('#alias').keypress(function(event){
     if (event.which == 32) {
       event.preventDefault();
       $(this).val($(this).val()+"_");
     }
   });

   $("#strophybyalias").keypress(function(event){
     if (event.which == 13) $("#tfetch_submit").trigger('click');
   });

   $("#tfetch_submit").click(function(event){
     $.get("/phive/modules/DBUserHandler/html/trophywizard.php?ql="+$("#strophybyalias").val(), function (data){
       $("#searchres").html(data);
     });
   });

   $('.addnew').click(function(){
     $('#twform').show();
     $('#tfform').hide();
     clearForm();
     $('#id').val(0);
     $('#formsubmit').val('Add');

   });
   $('#backtos').click(function(){
     $('#twform').hide();
     $('#tfform').show();
   });

   $('#alias').blur(function(){
     if ($(this).val().length<1) {
       $(this).addClass("warning");
     } else {
       $(this).val($(this).val().replace(/[^\w\s]/gi, ''));
       $(this).removeClass("warning");
     }
   });

   $('#network').change(function(){
     network = $('#network option:selected').val();
     populateGames();
   });

   var clearForm =(function(){
     $(':input').not(':button, :submit, :reset, :checkbox, :hidden, :radio').val('');
     $(':checkbox, :radio').prop('checked', false);
   });

   var populateGames =(function() {
     $.getJSON("/phive/modules/DBUserHandler/html/trophywizard.php?gg="+network, function(jsr) {
       $('#game_name').empty();
       $('#game_name').append($("<option></option>").text('Select game').val(0));
       $.each(jsr, function() {
         var textRep = (this.device_type_num >= 1) ? this.game_name + " ("+this.device_type+")" : this.game_name;
         $('#game_name').append($("<option></option>").text(textRep).val(this.ext_game_name));
       });
       if (typeof game_ref !== 'undefined') {
         $('#game_name').val(game_ref);
       }
     });
   });

   $('#game_name').change(function(){
     $('#game_ref').val($('#game_name option:selected').val());
     $('#sub_category').val($('#game_name option:selected').val());

   });

   var selectAwardsNets = (function(){
     if (typeof rew1_net !== 'undefined') {
       $('#trophy_awards_network option:contains(' + rew1_net + ')').each(function(){
         if ($(this).text() == rew1_net) {
           $(this).attr('selected', 'selected');
         }
       });
     }
     if (typeof altrew_net !== 'undefined') {
       $('#trophy_awards_network option:contains(' + altrew_net + ')').each(function(){
         if ($(this).text() == altrew_net) {
           $(this).attr('selected', 'selected');
         }
       });
     }
   });

   $('select[name=award_network]').change(function(){
     var tann = $(this).attr('id');
     if (tann=="trophy_awards_network") populateAwardsAliases($('#trophy_awards_network option:selected').text());
     if (tann=="trophy_awards_network2") populateAwardsAliases2($('#trophy_awards_network2 option:selected').text());
   });

   $('#trophy_awards_type').change(function(){
     var text = $('#trophy_awards_type option:selected').text();
     if (text=='<empty>' && typeof rew1_t === 'undefined') return false;
     if (typeof rew1_t !== 'undefined') {
       $('#trophy_awards_type option:contains(' + rew1_t + ')').each(function(){
         if ($(this).text() == rew1_t) {
           $(this).attr('selected', 'selected');
           if (rew1_t=='freespin-bonus') selectAwardsNets();
           populateAwardsAliases();
           return false;
         }
         return true;
       });
       rew1_t=text;
     }
     if (text == 'freespin-bonus') {
       $('.trophy_awards_net1').show();
     } else {
       $('.trophy_awards_net1').hide();
       populateAwardsAliases();
     }
   });

   var populateAwardsAliases =(function(n) {
     n = typeof n !== 'undefined' ? '&nw='+n : '';
     var this_type = $('#trophy_awards_type option:selected').text();
     $.getJSON("/phive/modules/DBUserHandler/html/trophywizard.php?atypes="+this_type+n, function(jsr) {
       $('#trophy_award_id').empty();
       $.each(jsr, function() {
         $('#trophy_award_id').append($("<option></option>").text(this.description).val(this.id).attr('selected',(typeof rew1_id !== 'undefined' && this.id == rew1_id)?'selected':''));
       });
     });
   });

   $('#trophy_awards_type2').change(function(){
     var text = $('#trophy_awards_type2 option:selected').text();
     if (text=='<empty>' && typeof altrew_t === 'undefined') return false;
     if (typeof altrew_t !== 'undefined') {
       $('#trophy_awards_type2 option:contains(' + altrew_t + ')').each(function(){
         if ($(this).text() == altrew_t) {
           $(this).attr('selected', 'selected');
           if (altrew_t=='freespin-bonus') selectAwardsNets();
           populateAwardsAliases2();
           return false;
         }
         return true;
       });
     }
     altrew_t=text;
     if (text == 'freespin-bonus') {
       $('.trophy_awards_net2').show();
     } else {
       $('.trophy_awards_net2').hide();
       populateAwardsAliases2();
     }
   });

   var populateAwardsAliases2 =(function(n) {
     n = typeof n !== 'undefined' ? '&nw='+n : '';
     var this_type = $('#trophy_awards_type2 option:selected').text();
     $.getJSON("/phive/modules/DBUserHandler/html/trophywizard.php?atypes="+this_type+n, function(jsr) {
       $('#trophy_award_id2').empty();
       $.each(jsr, function() {
         $('#trophy_award_id2').append($("<option></option>").text(this.description).val(this.id).attr('selected',(typeof altrew_id !== 'undefined' && this.id == altrew_id)?'selected':''));
       });
     });
   });



   $('#threshold').blur(function(){ if ($(this).val().length<1 || isNaN($(this).val())) { $(this).addClass("warning"); } else { $(this).removeClass("warning"); } });

   $('#copy').click(function(){
     $('#alias').val('').prop('disabled',false);
     $('#id').val(0);
     $('#formsubmit').val('Add');
   });

   $('#formsubmit').click(function(event){
     event.preventDefault();
     $('#twf').submit();
   });

   var bar = $('.bar');
   var percent = $('.percent');
   var uploadstatus = $('#uploadstatus');

   $('#twf').ajaxForm({
     beforeSend: function() {
       uploadstatus.empty();
       var percentVal = '0%';
       bar.width(percentVal)
       percent.html(percentVal);
     },
     uploadProgress: function(event, position, total, percentComplete) {
       var percentVal = percentComplete + '%';
       bar.width(percentVal)
       percent.html(percentVal);
     },
     success: function() {
       var percentVal = '100%';
       bar.width(percentVal)
       percent.html(percentVal);
     },
     complete: function(xhr) {
       if (xhr.responseText > 0 && xhr.responseText < 2147483647) {
         edit=true;
         $('input[name=id]').val(xhr.responseText);
         $('#formsubmit').val('Update');
         uploadstatus.html('Successfully inserted trophy.');
       }
       else if (xhr.responseText == -1) {
         uploadstatus.html('Database query failed. Reload page and try again.');
       }
       else {
         uploadstatus.html('Successfully updated trophy.');
       }
     }
   });

   $("#edit-tr").click(function(){
     $("#alias").prop('disabled',1);
     var alias = $("#alias").val();
     $.get("/phive/modules/Localizer/html/editstrings.php?arg0=en&arg1=trophyname."+alias.replace('#',''), function(data){
       $("#area_edit_tr").html(data);
       $("#area_edit_tr").show();
     });
   });



   <?php if (is_object($network) || $edit === true):?>
   var network ='<?=$network->network?>';
   populateGames();
   $('#trophy_awards_type').trigger('change');
   $('#trophy_awards_type2').trigger('change');
   <?php endif?>
 });
</script>

<style>
 label {padding:10px 0 10px 0;display:inline-block;width:240px;}
 .warning {border:3px dotted #FF0000;}
 <?php if ($edit != true):?>
 #twform {display:none;}
 #tfform {display:block;}
 <?php else:?>
 #tfform {display:none;}
 #twform {display:block;}
 <?php endif?>
 .trophy_awards_net1 {display:none;}
 .trophy_awards_net2 {display:none;}
 button {margin:0 0 0 0;vertical-align:center;}
 input {margin:0 0 0 0;vertical-align:center;}
</style>
<div class="pad10">
  <div id="tfform">
    <button class="addnew">Add new trophy</button><br />
    <form id="tfetch" method="post" onsubmit="return false;">
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
      <?=F::labelInput("Search trophy by alias", "strophybyalias", array("size" => 70))?><?=F::input(array("type" => "button", "id" => "tfetch_submit", "value" => "Search"));?>
      <br />
    </form>
    <br /><br/>
    <div id="searchres"></div>


  </div>
  <div id="twform">
    <button id="backtos">Back to search</button>
    <button class="addnew">Add new trophy</button><br />
    <form id="twf" method="post" action="/phive/modules/DBUserHandler/xhr/trophywizard_xhr.php" enctype="multipart/form-data" onsubmit="return false;">
      <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
      <?=F::labelInput("Alias", 'alias', array("size" => 70, "value" => Selected($xt, 'alias')))?><br />
      <?=F::labelSelectList("Select ref game", 'network', $networks, Selected($network, 'network'))?><?=F::selectList("game_name", array())?><br />
      <?=F::labelInput("OR fill ext_game_name", 'game_ref', array("size" => 50, "value" => Selected($xt, 'game_ref')))?><br />
      <?=F::labelSelectList("Category", 'category', $categories, Selected($xt, 'category'))?><br />
      <?=F::labelInput("Subcategory", 'sub_category', array("size" => 50, "value" => Selected($xt, 'sub_category')))?><br />
      <?=F::labelSelectList("Type", 'type', $types, Selected($xt, 'type'))?><br />
      <?=F::labelSelectList("Subtype", 'subtype', $subtypes, Selected($xt, 'subtype'))?><br />
      <?=F::labelInput("Completed Ids, ex: 1,2,3", 'completed_ids', array("size" => 70, "value" => Selected($xt, 'completed_ids')))?><br />
      <?=F::labelInput("Valid from (YYYY-MM-DD):", 'valid_from', array("size" => 70, "value" => Selected($xt, 'valid_from')))?><br />
      <?=F::labelInput("Valid to (YYYY-MM-DD):", 'valid_to', array("size" => 70, "value" => Selected($xt, 'valid_to')))?><br />
      <?=F::labelInput('Threshold', 'threshold', array("size" => 6, "value" => Selected($xt, 'threshold')))?><br />
      <?=F::labelInput('Time period', 'time_period', array("size" => 6, "value" => Selected($xt, 'time_period')))?><br />
      <?=F::labelSelectList("Time span", 'time_span', $timespans, array("size" => 6), Selected($xt, 'time_span'))?><br />
      <?=F::labelInput('Hidden', 'hidden', array("type" => "checkbox", "value" => 1, (Selected($xt, 'hidden') != '1') ?: "checked" => "checked"))?><br />
      <?=F::labelInput('In row', 'in_row', array("type" => "checkbox", "value" => 1, (Selected($xt, 'in_row') != '1') ?: "checked" => "checked"))?><br />
      <?=F::labelInput('Trademark', 'trademark', array("type" => "checkbox", "value" => 1, (Selected($xt, 'trademark') != '1') ?: "checked" => "checked"))?><br />
      <?=F::labelInput('Repeatable', 'repeatable', array("type" => "checkbox", "value" => 1, (Selected($xt, 'repeatable') != '1') ?: "checked" => "checked"))?><br />
      <?=F::labelInput('Only mobile', 'device_type', array("type" => "checkbox", "value" => 1, (Selected($xt, 'device_type') != '1') ?: "checked" => "checked"))?><br />
      <?php//=F::labelSelectList("Device type", 'device_type', $devicetypes, Selected($xt, 'device_type'))?>
      <?=F::labelSelectList("Reward type", 'trophy_awards_type', $trophyawardstypes)?><br />
      <span class="trophy_awards_net1">
        <?=F::labelSelectList("Select network", 'trophy_awards_network', $networks, null, 'award_network')?><br />
      </span>
      <?=F::labelSelectList("Select reward", 'trophy_award_id', array("select type first"))?><br />
      <?=F::labelSelectList("Alternative reward type", 'trophy_awards_type2', $trophyawardstypes)?><br />
      <span class="trophy_awards_net2">
        <?=F::labelSelectList("Select network", 'trophy_awards_network2', $networks, null, 'award_network')?><br />
      </span>
      <?=F::labelSelectList("Alternative reward", 'trophy_award_id2', array("select type first"))?><br />
      <?=F::labelInputFile("Grey image", "img_grey", array(), "events/grey/")?><?php
                                                                               echo imgshow('events/grey/' . Selected($xt, 'alias') . "_event.png");?><br />
      <?=F::labelInputFile("Color image", "img_color", array(), "events/")?><?php
                                                                            echo imgshow('events/' . Selected($xt, 'alias') . "_event.png");?><br />
      <?php
      loadJs("/phive/js/jquery.form.min.js")?>
      <style>
       .progress { position:relative; width:400px; border: 1px solid #ddd; padding: 1px; border-radius: 3px; }
       .bar { background-color: #B4F5B4; width:0%; height:20px; border-radius: 3px; }
       .percent { position:absolute; display:inline-block; top:3px; left:48%; }
      </style>
      <div class="progress">
        <div class="bar"></div >
        <div class="percent">0%</div >
      </div>
      <div id="uploadstatus"></div>

      <div id="notification" class="alertnotification"></div>

      <?=F::input(array("type" => "hidden", "name" => "id", "id" => "id", "value" => Selected($xt, 'id')));?>
      <?=F::labelInput("Action", "formsubmit", array("type" => "submit", "value" => "Add"));?>

      <?=F::input(array("type" => "button", "id" => "edit-tr", "value" => "Edit/translate trophy name"))?>
      <?=F::input(array("type" => "button", "id" => "copy", "value" => "Clone"))?>
      <br />

      <div id="status"></div>
      <div id="area_edit_tr"></div>

    </form>
  </div>
</div>
