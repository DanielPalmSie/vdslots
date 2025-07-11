<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../api/m/Form.php';
use MVC\Form as F;

class GameWiz {
  function __construct() {
    $this->deviceList = phive()->getDeviceMap();
    $this->networks = phive('SQL')->loadKeyValues("SELECT network FROM micro_games WHERE network != '0' GROUP BY network", 'network', 'network');
    $this->select_networks = array_merge(array(0 => "&lt;SELECT&gt;"), $this->networks);
    $this->op_map = array();
    foreach($this->networks as $n)
      $this->op_map[$n] = phive('SQL')->loadArray("SELECT operator, op_fee, blocked_countries FROM micro_games WHERE network = '$n' GROUP BY operator", 'ASSOC');

    $this->tags = phive('SQL')->loadKeyValues("SELECT DISTINCT tag FROM micro_games", 'tag', 'tag');
  }
}

$W = new GameWiz();
$networks = $W->networks;
$deviceList = $W->deviceList;
$tagList = $W->tags;
$languages = phive('Localizer')->getLangSelect();
array_shift($languages);
$edit = false;
$brand_game_sync_enabled = phive('Distributed')->getSetting('brand_games_sync_enabled');

if (isset($_GET['action']) && $_GET['action'] == "e") {
  $edit = true;
  $game = phive('SQL')->loadObject("SELECT * FROM micro_games WHERE id = " . (int)$_GET["id"], 'ASSOC');
  $mg = phive('MicroGames');
  $aAdditionalValues = array();
  $resolutions = phive('SQL')->load1DArr("SELECT CONCAT(width, 'x', height) AS res FROM micro_games WHERE operator = '{$game->operator}' GROUP BY res HAVING res != '0x0'", 'res', 'res');
  $operators = phive('SQL')->load1DArr("SELECT operator FROM micro_games WHERE network = '{$game->network}' GROUP BY operator HAVING operator != ''", 'operator', 'operator');
}
function Selected($game, $var, $else = null) {
  $return = (isset($game) && is_object($game)) ? $game->$var : "";
  return ($return == "" && $else != null) ? $else : $return;
}

function imginfo($img) {
  $dir = phive('Filer')->getSetting("UPLOAD_PATH") . "/";
  if (!file_exists($dir . $img))
    return sprintf("%s doesn't exist", $img);
  else
    return sprintf('%s exists', $img);
}

if (!empty($_GET["ql"])) {
  $search = $_GET["ql"];
  $games = phive('SQL')->loadArray("SELECT id,game_name,ext_game_name,device_type FROM micro_games WHERE game_name LIKE '%{$search}%'");

  echo '<table class="mt" width="300px"><tr><th>Game</th><th>Type</th><th>Edit</th><tr/>';
  $i = 0;
  foreach ($games as $game) {
    $class = (0 == ++$i % 2) ? 'fill-even' : 'fill-odd';
    echo '<tr class="' . $class . '"><td>' . $game["game_name"] . '</td><td>' . $game["device_type"] . '</td><td><a href="/admin/gamewizard/?action=e&id=' . $game["id"] . '">Edit</a></td></tr>';

  }
  echo '</table>';
  exit;
}

?>
<?php loadJs('/phive/js/underscore.js') ?>
<?php loadJs('/phive/js/jquery.json.js') ?>
<script type="text/javascript">
 var apiKey = '<?=  phive('MicroGames')->getSetting('api_key');?>';
 var videoUrl = '<?= phive('MicroGames')->getSetting('server_api_url');?>'
 var screenshotUrl = '<?= phive('MicroGames')->getSetting('screenshot_remote_url_dir');?>'
 function getOpObj(op){
   return _.findWhere(opMap, {operator: op});
 }

 function getGres(obj){
   return obj.width+'x'+obj.height;
 }

 function checkEmpty(sel){
   $(sel).blur(function(){
     if ($(this).val().length < 1)
       $(this).addClass("warning");
     else
       $(this).removeClass("warning");
   });
 }

 function checkEmptyOrNan(sel){
   $(sel).blur(function(){
     if ($(this).val().length < 1 || isNaN($(this).val()))
       $(this).addClass("warning");
     else
       $(this).removeClass("warning");
   });
 }
 var gameid = '<?= Selected($game, 'game_id')?>'
 var canShareAcrossBrand = <?= phive()->getJsBool($brand_game_sync_enabled) ?>;
 $(document).ready(function(){
   var validationErrors=false;
   <?php if ($edit != true):?>
     var edit=false;
   <?php else:?>
     var curGame = <?php echo json_encode($game) ?>;
     var edit = true;
     var langs = <?=json_encode(explode(',', $game->languages))?>;
     $("select[id=languages]").val(langs);
     $("#wizardform").show();
     $("#video_section").show();
     $(".btn-generic-attr").show()
   <?php endif;?>
  // Wait for device type text to initialize
   var setGwizardForm = setInterval(function() {
       if ($("#gwizardform").find("[name='device_type_text']").val() !== "") {
           window.initial_gwizardform = JSON.stringify($("#gwizardform").serializeArray());
           clearInterval(setGwizardForm);
       }
   }, 100);
   canShareAcrossBrand = canShareAcrossBrand && edit;
   var resolutions;
   var network;
   var opfee_b;
   var opfee_n;
   var gameid_suffix='';
   var opMap = <?php echo json_encode($W->op_map) ?>;


     function changeNetwork(network) {

         var available_resolutions = ['1024x576', '1280x720 ', '1920x1080', '2560x1440', '1280x800', '1920x1200', '1024x768', '1920x1440'];

         if (typeof network == 'undefined') {
             network = $('#network option:selected').text();
         }

         switch (network) {
             case 'evolution':
                 $("#mg_ids").show();
                 break;
             case 'microgaming':
                 $("#mg_ids").show();
                 onNetworkChange('microgaming', '', 0.15, 0.15, available_resolutions, false);
                 break;
             case 'nyx':
                 onNetworkChange('nyx', 'nyx', 0.15, 0.15, available_resolutions, true);
                 break;
             case 'netent':
                 $("#mg_ids").show();
                 onNetworkChange('netent', 'netent_', 0.15, 0.15, available_resolutions, false);
                 break;
             case 'gamesos':
                 onNetworkChange('gamesos', 'gamesos', 0.15, 0.15, available_resolutions, true);
                 break;
             default:
                 onNetworkChange('relax', 'relax', 0.15, 0.15, available_resolutions, false);
                 break;
         }
     }

   function onNetworkChange(network, pre_ext, b, n, res, hide_brandsel) {
     getOperators();
     opfee_b = b;
     opfee_n = n;
     resolutions = res;
     if (!edit)
       $('#op_fee').val(''+n);
     if (hide_brandsel)
       $('.branded_select').hide();
   }

   $('#network').change(function(){
     network = $("#network option:selected").text();
     $("#wizardform").show();
     $("#game_name").focus();
     if ($("#network option:selected").val()==0)
       $("#wizardform").hide();
     $('#mg_ids').hide();
     $('.jackpot_contrib').hide();
     $('.operator').show();

     var curOp = opMap[network][0].operator;

     $('#operator_text').val(curOp);
     $('#blocked_countries').val(opMap[network][0].blocked_countries);

     changeNetwork(network);

     $('#resolutions').empty();
     var list = $('#resolutions')[0];

     $.each(resolutions, function(index, text) {
       list.options[list.options.length] = new Option(text, text);
     });
   });

   var getOperators = (function() {
     $('.operator').show();
     $('#operator').empty();
     _.each(opMap[network], function(el) {
       $('#operator').append($("<option></option>").text(el.operator).val(el.op_fee));
     });
   });

   $('#operator').change(function(){
     if (network != "microgaming")
       $('#op_fee').val($('#operator option:selected').val());
     $('#blocked_countries').val(getOpObj($("#operator option:selected").text()).blocked_countries);
   });

   $('#unlock_opfee').click(function(){
     var c = confirm('Unlock opfee?');
     if (c==true) {
       $('#op_fee').prop('disabled',false);
       $('#unlock_opfee').hide();
     }
   });

   $('#op_fee').blur(function(){
     if($(this).val() < 0.125){
       $("#op_fee").addClass("warning");
       $(".opfee_warning").html("Warning, low opfee!").show();
     }else if ($(this).val() >= 0.3) {
       $('#op_fee').addClass("warning");
       $('.opfee_warning').html("ERROR! Too high op_fee!").show();
     }else {
       $(".opfee_warning").hide();
       $("#op_fee").removeClass("warning");
     }
   });

   checkEmpty('#game_name');
   checkEmpty('#ext_game_name');
   checkEmptyOrNan('#client_id');
   checkEmpty('#module_id');

   $('#languages').blur(function(){
     if (!$("#languages option:selected").length)
       $(this).addClass("warning");
     else
       $(this).removeClass("warning");
   });

   $('#jackpot_contrib').blur(function(){
     if ($(this).val() >= 0.5 || $(this).val() < 0)
       $(this).addClass("warning");
     else
       $(this).removeClass("warning");
   });

   $("#form_reset").click(function(){$("#notification").hide();$("#gwizardform").reset();});

   $('#payout_percent').blur(function(){
     if ($(this).val() < 0.96){
       if ($(this).val()=="")
         $(".payout_warning").html("Error! Payout % missing");
       else if ($(this).val() < 0.50)
         $(".payout_warning").html("Warning! Low payout %");
       $(".payout_warning").show();
       $("#payout_percent").addClass("warning");
     }else if ($(this).val() < 1 && $(this).val() >= 0.96) {
       $(".payout_warning").hide();
       $("#payout_percent").removeClass("warning");
     }else if ($(this).val() > 1) {
       $("#payout_percent").addClass("warning");
       $(".payout_warning").html("Error, too high payout %!");
     }
   });

   $('#game_name, #network').on('change blur',function(){
     if (edit==1)
       return false;
     var val = $('#game_name').val().toLowerCase().replace(/\W+/g, '');
     var game_url = $('#game_name').val().toLowerCase().replace(/\W/g, '-');
     val = val.replace(/\s+/g, '');
     var meta = network+"-"+game_url;
     $('#game_url').val(game_url+"-"+network);
     $('#meta_descr').val('#game.meta.descr.'+meta);
     $('#html_title').val('#game.meta.title.'+meta);
   });
   $("#tag").change(function(){
     if ($("#tag option:selected").text().indexOf("jackpot") >= 0) {
       $(".jackpot_contrib").show();
     } else $(".jackpot_contrib").hide();
   });

   $("#device_type").change(function(){
     if (network != "microgaming" || network != "netent") {
       //gameid_suffix=$("#device_type option:selected").text();
       //$("#gameid_suffix").text($("#device_type option:selected").text());
     }
     $("#device_type_text").val($("#device_type option:selected").text());
     $("#device_type_num").val($("#device_type option:selected").val());
     if ($("#device_type option:selected").val() >= 1) {
       $("#edit-metadescr").hide();
       $("#edit-htmltitle").hide();
       $("#resolutions").hide();
     } else {
       $("#edit-metadescr").show();
       $("#edit-htmltitle").show();
       $("#resolutions").show();
     }
   });

   $("#formsubmit").click(function(event){
     event.preventDefault();
     validationErrors=false;
     if ($('#game_name').val().length < 1)
       validationErrors = true;

     if ($('#ext_game_name').val().length < 1)
       validationErrors = true;

     if ($('#payout_percent').val() >= 1.01) {
       validationErrors = true;
       $("#payout_percent").trigger('blur');
     }

     if($('#jackpot_contrib').val() >= 0.5) {
       validationErrors = true;
       $('#jackpot_contrib').addClass("warning");
     }

     if ($('#op_fee').val() >= 0.3) {
       validationErrors = true;
       $("#op_fee").trigger('blur');
     }

     if (!$("#languages option:selected").length) {
       validationErrors = true;
       $('#languages').trigger('blur');
     }

     if (validationErrors == true) {
       $("#notification").html("Validation errors present, check your inputs!").show();
       return false;
     }

     $("#game_name").prop('disabled',false);
     $('#notification').hide();
     $('#op_fee').prop('disabled',false);
     $('#device_type_num').prop('disabled',false);
     //if (edit != true) $('#gameid').val($('#gameid').val()+gameid_suffix);
     var oper_input = $("#operator_input").val();
     var oper_selected = $('#operator option:selected').text();
     $('#operator_text').val( (typeof oper_input !== 'undefined') ? oper_input : oper_selected );

     $("#device_type_text").val($("#device_type option:selected").text());
     $("#device_type_num").val($("#device_type option:selected").val());
     $('#submit_insert').val("1");
     if ($('#device_type_num').val()>=1) {
       $('#meta_descr').val('');
       $('#game_url').val('');
       $('#resolutions').val('0x0');
       $('#html_title').val('');
     }
     $(".progress").show();
     $("#gwizardform").submit();
   });

   $('input[type=radio][name=branded_game]').change(function(){
     if ($("#op_fee").prop('disabled') == false) return false;
     if ($(this).val() == "true") $("#op_fee").val(opfee_b);
     else if ($(this).val() == "false") $("#op_fee").val(opfee_n);
   });

   $("#game").keypress(function(event){
     if (event.which == 13) $("#gfetch_submit").trigger('click');
   });

   $("#gfetch_submit").click(function(event){
     $.get("/phive/modules/Micro/html/gamewizard.php?ql="+$("#game").val(), function (data){
       $("#searchres").html(data);
       $("#wizardform").hide();
       $("#video_section, #game_attributes_section, #add_game_attr_field, #game_attributes_specific_section, #gfetch, #video_section, #gwizardform, #new_game_attr_field").hide();
     });
   });


   $("#edit-htmltitle").click(function(){
     var alias = $("#html_title").val();
     $("#game_name").prop('disabled',1);
     $.get("/phive/modules/Localizer/html/editstrings.php?arg0=en&arg1="+alias.replace('#',''), function(data){
       $("#area_edit_tr").html(data);
       $("#area_edit_tr").show();
     });
   });

   $("#edit-metadescr").click(function(){
     $("#game_name").prop('disabled',1);
     var alias = $("#meta_descr").val();
     $.get("/phive/modules/Localizer/html/editstrings.php?arg0=en&arg1="+alias.replace('#',''), function(data){
       $("#area_edit_tr").html(data);
       $("#area_edit_tr").show();
     });
   });

   $("#operator_other").click(function(){
     $("#operator_other").hide();
     $("#operator").replaceWith('<input type="text" name="operator_input" id="operator_input" size="32" />');
   });

   if (edit===true){
     //changeNetwork();
     //$("#network").trigger('change');
     $("#device_type").trigger('change');
     //console.log();
     res = getGres(curGame);
     $('#resolutions > option').eq(res).prop('selected', true);
     $('input[type=radio]').each(function(){
       if ($('#op_fee').val() == opfee_b && this.value=='true') $(this).prop('checked',true);
       else if ($('#op_fee').val() == opfee_n && this.value=='false') $(this).prop('checked',true);
     });
   }

   var bar = $('.bar');
   var percent = $('.percent');
   var uploadstatus = $('#uploadstatus');

   $('#gwizardform').ajaxForm({
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
         uploadstatus.html('Successfully inserted game.');
       }
       else if (xhr.responseText == -1) {
         uploadstatus.html('Database query failed. Reload page and try again.');
       }
       else {
         uploadstatus.html('Successfully updated.');
       }
     }
   });

    $("#submitVideoFile").click(function(){
        if(!check_video_file()){
          return false;
        }

        $('#video-section-info').html('');
        var objFile = $('#videoFile')[0].files[0]
        var fd = new FormData();
        fd.append('apiKey',apiKey);
        fd.append('gameid',gameid);
        fd.append('file',objFile);
        $.ajax({
            url: videoUrl + "uploadVideos/"
            ,enctype: 'multipart/form-data'
            ,processData: false
            ,contentType: false
            ,data: fd
            ,processData: false
            ,beforeSend: function() {
              // setting a timeout
              $("#loadingImgVideo").show()
            }
            ,type: 'POST'
        })
        .done(function(data) {
          $("#loadingImgVideo").hide()
          var status = data.status
          var message = data.message
          var messages = ''
          for(var i = 0;i < message.length;i++){
              messages += message[i] + '<br>'
          }
          $('#video-section-info').removeClass (function (index, css) {
            return (css.match (/(^|\s)status-\S+/g) || []).join(' ');
          });
          $('#video-section-info').addClass("status-"+status)

          get_video_info('getVideos','#getVideos','<br>');
          get_video_info('countVideos','#countVideos','');
          $('#video-section-info').append(messages);
        })
    });

    $("#videoFile").change(function(){
      check_video_file();
    });

    get_game_attributes() // generic
    get_game_attributes('<?= Selected($game, 'id')?>') //this game


    get_video_info('getVideos','#getVideos','<br>');
    get_video_info('countVideos','#countVideos','');
 });
  function get_game_attributes(id){
    var data = 'get_game_generic_attr=1'
    var container = 'game_attributes_section'
    if(id != 'undefined' && id != '' && id != null){
      data = 'get_game_specific_attr=1' + '&gameid=' + id
      container = 'game_attributes_specific_section'
    }
    $.ajax({
      url: "/phive/modules/Micro/json/gamewizard_xhr.php"
      ,type: 'POST'
      ,data: data
      ,
      type: 'POST'
    })
    .done(function(res) {
      $('#' + container).html(res)
    });
  }

  function delete_game_attributes(id) {
    var data = 'attrId='+id+'&delete_game_attr=1'
    if(confirm('Are you sure?')){
      $.ajax({
        url: "/phive/modules/Micro/json/gamewizard_xhr.php"
        ,type: 'POST'
        ,data: data
        ,dataType: 'json'
        ,
        type: 'POST'
      })
      .done(function(ret) {
        $('.message_ajax').removeClass('ok error')
        var status = ret.status
        var message = ret.message
        $(".message_ajax").html(message)
        $(window).scrollTop($('#searchres').offset().top);
        $(".message_ajax").addClass(status)
        get_game_attributes() // generic
        get_game_attributes('<?= Selected($game, 'id')?>') //this game
      });
    }
  }
  function uploadScreenshot(id) {

    var objFile = $('[name="s' + id + '_'+gameid+'"]')[0].files[0]
    if(objFile.type != 'image/jpg' && objFile.type != 'image/jpeg' && objFile.type != 'image/png' ) {
         alert("File must is an Image Jpg / Jpeg / Png.");
         return false;
    }
    var fd = new FormData();
    fd.append('apiKey',apiKey);
    fd.append('gameid',gameid);
    fd.append('pos',id);
    fd.append('file',objFile);
    $.ajax({
        url: videoUrl + "uploadScreenshot/"
        ,dataType: 'json'
        ,enctype: 'multipart/form-data'
        ,processData: false
        ,contentType: false
        ,data: fd
        ,beforeSend: function() {
          // setting a timeout
          $("#loadingImgVideo").show()
        }
        ,
        type: 'POST'
    })
    .done(function(data) {
        $("#loadingImgVideo").hide()
        var status = data.status
        var message = data.message
        var messages = ''
        for(var i = 0;i < message.length;i++){
            messages += message[i] + '<br>'
        }
        $('#video-section-info').removeClass (function (index, css) {
          return (css.match (/(^|\s)status-\S+/g) || []).join(' ');
        });
        $('#video-section-info').addClass("status-"+status)
        get_video_info('getVideos','#getVideos','<br>');
        get_video_info('countVideos','#countVideos','');
        $('#video-section-info').append(messages);
    });
  }
  function submit_game_attributes(which_form,new_field){
     var is_new = '';
     if(new_field != 'undefined' && new_field != '' && new_field != null){
       is_new = '&is_new=1';
     }
     if(which_form === 'game_attributes_specific_section'){
       var data = $("#" + which_form).find("select,textarea, input").serialize()+ '&save_specific_game_attributes=1'
     }else{
       var data = $("#" + which_form).find("select,textarea, input").serialize()+ '&save_game_attr=1' + is_new
     }
     $.ajax({
       url: "/phive/modules/Micro/json/gamewizard_xhr.php"
       ,type: 'POST'
       ,data: data
       ,dataType: 'json'
       ,
       type: 'POST'
     })
     .done(function(data) {
        $('.message_ajax').removeClass('ok error')
        var status = data.status
        var message = data.message
        $(".message_ajax").html(message)
        $(".message_ajax").addClass(status)
        $(window).scrollTop($(".message_ajax").offset().top - 130);
        get_game_attributes() // generic
        get_game_attributes('<?= Selected($game, 'id')?>') //this game
      });
  }
 /**
  * This method will transfer the game to a other brand
  */
 function send_to_brand() {
     if (!canShareAcrossBrand || (window.initial_gwizardform !== JSON.stringify($("#gwizardform").serializeArray()))) {
         alert('Game needs to be saved before you can share it to other brands.');
         return;
     }
     var post_data = $("#gwizardform").serializeArray().concat({name: "action_type", value: "move_to_brand"});
     $.post('/phive/modules/Micro/json/gamewizard_xhr.php', post_data, function (data) {
         if (data.hasOwnProperty('message')) {
             return alert(data.message);
         }
         var message = '';
         for (var brand in data) {
             if (data.hasOwnProperty(brand)) {
                 message += brand + ": " + data[brand]['message'] + "\n";
             }
         }
         alert(message);
     }, 'json');
 }
  function check_video_file(){//check if empty or different from MP4 file
    var objFile = $('#videoFile')[0].files[0]
    if(objFile === undefined || objFile === '' || objFile === null || objFile.type === undefined || objFile.type != 'video/mp4') {
      alert("File must be a Video MP4");
      return false;
    }
    return true;
  }
  function get_video_info(action,container,br){
    $(container).html('')
    $('#video-section-info').html('');
    var gamename = gameid
    $.ajax({
       url: videoUrl + action + '/'
       ,data: {'apiKey':apiKey, 'gamename': gamename}
       ,dataType: 'json',
       type: 'POST'
    })
    .done(function(data) {
      var status = data.status
      var message = data.message
      var messages = ''
      var messageSplit = ''
      if(action === 'getVideos'){
        for(var video_filename in message[gamename]){
            messages += '<tr><td>'+video_filename+'</td>'
            messages += '<td>&nbsp;&nbsp;\n\<a href="javascript:void(0)" onclick="deleteVideo(\'' + video_filename + '\')">Remove</a></td>'
            messages += '<td><table><tr><td style="height:100px;width:150px" id="screenshotv"></td></tr><tr><td><input type="file" name="sv_'+gameid+'" onChange="uploadScreenshot(\'v\')" style="color:black" ></td></tr></table></td>'
            messages += '<td><table><tr><td style="height:100px;width:150px" id="screenshot1"></td></tr><tr><td><input type="file" name="s1_'+gameid+'" onChange="uploadScreenshot(1)" style="color:black" ></td></tr></table></td>'
            messages += '<td><table><tr><td style="height:100px;width:150px" id="screenshot2"></td></tr><tr><td><input type="file" name="s2_'+gameid+'" onChange="uploadScreenshot(2)" style="color:black" ></td></tr></table></td>'
            messages += '<td><table><tr><td style="height:100px;width:150px" id="screenshot3"></td></tr><tr><td><input type="file" name="s3_'+gameid+'" onChange="uploadScreenshot(3)" style="color:black" ></td></tr></table></td>'
            messages += '</tr>'
        }
        getScreenshots()
      }else{
        for(var i = 0;i < message.length;i++){
          messages += message[i] + br
        }
      }
      $(container).removeClass (function (index, css) {
        return (css.match (/(^|\s)status-\S+/g) || []).join(' ');
      });
      $(container).append(messages);
    });
  }
  function getScreenshots(){
    var gamename = gameid
    $.ajax({
      url: videoUrl + "getScreenshots/"
      ,data: { 'gameid' : gamename }
      ,dataType: 'json',
      type: 'POST'
   })
   .done(function(data) {
      var status = data.status
      var message = data.message
      var messages = ''
      if(status === 'error'){
        $("#videosContainer").hide()
      }else{
        for(i = 0;i < message.length;i++){
          if(message[i].split('.')[1] == 'v')
            $('#screenshotv').html("<img style='width:150px' src='" + screenshotUrl + message[i] + "'>")
          if(message[i].split('.')[1] == '1')
            $('#screenshot1').html("<img style='width:150px' src='" + screenshotUrl + message[i] + "'>")
          if(message[i].split('.')[1] == '2')
            $('#screenshot2').html("<img style='width:150px' src='"+screenshotUrl + message[i] + "'>")
          if(message[i].split('.')[1] == '3')
            $('#screenshot3').html("<img style='width:150px' src='"+screenshotUrl + message[i] + "'>")
        }
      }
    });
  }

  function deleteVideo(filename){
    $('#video-section-info').html('')
    var r = confirm("Do you want to erase " + filename + "?");
    if (r === true) {
        var gamename = gameid
        $.ajax({
            url: videoUrl + 'deleteVideo/'
            ,data: {'apiKey':apiKey, 'filename': filename}
            ,dataType: 'json',
            type: 'POST'
        })
        .done(function(data) {
          var status = data.status
          var message = data.message
          var messages = ''
          $("#video-section-info").removeClass (function (index, css) {
            return (css.match (/(^|\s)status-\S+/g) || []).join(' ');
          });
          for(var i = 0;i < message.length;i++){
          messages += message[i]
          }
          get_video_info('getVideos','#getVideos','<br>');
          get_video_info('countVideos','#countVideos','');
          $('#video-section-info').addClass("status-"+status)
          $('#video-section-info').append(messages);
        });
      }
    }


    function generic_attributes(btn){ //switch from Default Generic Attributes to Specific and Viceversa
      $(btn).text(function(i, text){
          return text === "Set Generic Attributes" ? "Game Wizard" : "Set Generic Attributes";
      })
      $("#game_attributes_section, #add_game_attr_field, #game_attributes_specific_section, #gfetch, #video_section, #gwizardform, #new_game_attr_field").toggle()
      $('.message_ajax').html('')
      $(window).scrollTop($(btn).offset().top -30);
    }
</script>
<style>
 label {padding:10px 0 10px 0;display:inline-block;width:150px;}
 input {}
 #wizardform {display:none;}
 .microgaming {display:none;}
 .opfee_warning{display:none;color:#cc0000;font-size:16px;}
 .payout_warning{display:none;color:#cc0000;font-size:16px;}
 .warning {border:2px dotted #cc0000;}
 .alertnotification {border:5px solid #cc0000;color:#990000;font-size:18px;display:none;}
 .linkbtn {cursor:pointer;text-decoration:underline;}
 .status-ok { color: green;    font-size: 18px;    margin: 10px;    border: 1px solid green;    width: auto;    padding: 10px;    text-align: center;}
 .status-error { color: red;    font-size: 18px;    margin: 10px;    border: 1px solid red;    width: auto;    padding: 10px;    text-align: center;}
 #getVideos td { text-align: center}
</style>
<div class="pad10">
  <a href="/admin/gamewizard/">Add new game / reset form</a>
  <form id="gfetch" method="post" onsubmit="return false;">
  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">

    <?=F::labelInput("Search game", "game", array("size" => 32))?><?=F::input(array("type" => "button", "id" => "gfetch_submit", "value" => "Search"));?>


  </form>
  <br /><br/>
  <div id="searchres"></div>


  <form id="gwizardform" action="/phive/modules/Micro/json/gamewizard_xhr.php" method="post" enctype="multipart/form-data" onsubmit="return false;">
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
    <label for="network">Network:</label><?=F::selectList('network', $networks, Selected($game, 'network'));?>
    <br />
    <div id="wizardform">
      <?=F::labelInput("Game name", "game_name", array("size" => 32, "value" => Selected($game, 'game_name')))?><br />
      <?=F::labelInput("Game ID", "gameid", array("size" => 32, "value" => Selected($game, 'game_id')))?><span id="gameid_suffix"></span><br />
      <label for="ext_game_name">Ext game name</label>
      <?=F::input(array("size" => "64", "id" => "ext_game_name", "name" => "ext_game_name", "value" => Selected($game, 'ext_game_name'), "disabled" => $edit ? true : null));?><br />
      <?=F::labelInput("Game URL", "game_url", array("size" => "32", "value" => Selected($game, 'game_url')))?><br />
      <?=F::input(array("type" => "hidden", "id" => "meta_descr", "name" => "meta_descr", "value" => Selected($game, 'meta_descr')))?>
      <?=F::input(array("type" => "hidden", "id" => "html_title", "name" => "html_title", "value" => Selected($game, 'html_title')))?>
      <?=F::labelSelectList("Tag", "tag", $tagList, Selected($game, 'tag', 'videoslots'))?><br />
      <div id="mg_ids" style="display: <?php echo (empty($game->module_id) && !in_array($game->network, ['evolution', 'microgaming', 'netent', 'playtech', 'swintt']) ) ? 'none' : 'block' ?>;">
        <?=F::labelInput("Client ID", "client_id", array("value" => Selected($game, 'client_id')));?><br />
        <?=F::labelInput("Module ID", "module_id", array("value" => Selected($game, 'module_id')));?><br />
      </div>
      <br/>
      <p class="operator">
        <?=F::labelSelectList("Operator", "operator", $operators, Selected($game, 'operator'))?>
        <button id="operator_other">other?</button>
        <br />
        <?=F::input(array("name" => "operator_text", "id" => "operator_text", "type" => "hidden"))?>
      </p>
      <?=F::labelSelectList("Device type", "device_type", $deviceList, Selected($game, 'device_type_num'));?>
      <?=F::input(array("type" => "hidden", "id" => "device_type_num", "name" => "device_type_num", "value" => Selected($game, 'device_type_num'), "disabled" => "disabled", "size" => "3"));?><br />
      <?=F::input(array("type" => "hidden", "id" => "device_type_text", "name" => "device_type_text"));?>
      <p class="jackpot_contrib">
        <?=F::labelInput("Jackpot contribution", "jackpot_contrib", array("value" => Selected($game, 'jackpot_contrib')));?><br />
      </p>
      <?=F::labelSelectMultipleList("Languages", "languages", "languages[]", 6, $languages, 'selected');?><br />
      <p class="branded_select">
        <?=F::labelInput("Branded game", "branded_game", array("type" => "radio", "name" => "branded_game", "value" => "true"))?>Yes
        <?=F::input(array("type" => "radio", "name" => "branded_game", "value" => "false"))?>No<br />
      </p>
      <?php if(p('settings.games.section.op_fee')): ?>
          <?=F::labelInput("Op fee", "op_fee", array("disabled" => "disabled", "value" => Selected($game, 'op_fee', 0.15)))?>
          <?=F::input(array('type' => 'button', 'id' => 'unlock_opfee', 'value' => 'Unlock'))?>
          <span class="opfee_warning">Opfee under 0.125</span>
          <br />
      <?php else: ?>
          <?=F::input(array("id" => "op_fee", "type" => "hidden", "value" => Selected($game, 'op_fee', 0.15)))?>
      <?php endif; ?>
      <?=F::labelInput("Payout %", "payout_percent", array("value" => Selected($game, 'payout_percent')));?>
      <span class="payout_warning">Warning, payout percent is under 0.96.</span>
      <br />
      <?php if(p('settings.games.section.payout_extra_percent')): ?>
         <?=F::labelInput("Booster Extra (For an extra 10% needs to be 1.1 To deactivate we put it to 0)", "payout_extra_percent", ["size" => 20, "value" => Selected($game, 'payout_extra_percent', 0)])?>
      <?php endif; ?>
      <br />
      <?=F::labelInput("Min bet", "min_bet", array("value" => Selected($game, 'min_bet')));?><br />
      <?=F::labelInput("Max bet", "max_bet", array("value" => Selected($game, 'max_bet')));?><br />
      <?=F::labelInput("Volatility (1 - 9)", "volatility", array("value" => Selected($game, 'volatility')));?><br />
      <?=F::labelInput("# of lines", "num_lines", array("value" => Selected($game, 'num_lines')));?><br />
      <?=F::labelInput("Max win (times bet amount)", "max_win", array("value" => Selected($game, 'max_win')));?><br />

      <?=F::labelInput('Stretch BG (1 = yes, 0 = no)', "stretch_bkg", array("value" => Selected($game, 'stretch_bkg')));?><br />
      <?=F::labelInput('Enabled (1 = yes, 0 = no) 0 will show "Under Construction" sign', "enabled", array("value" => Selected($game, 'enabled')));?><br />
      <?=F::labelInput("Ribbon Pic (put new if pics are called new_en.png, new_sv.png etc)", "ribbon_pic", array("value" => Selected($game, 'ribbon_pic')));?><br />
      <?=F::labelInput('Multi Channel (1 = yes, 0 = no), if 1 use mobile URL to open game on PC', "multi_channel", array("value" => Selected($game, 'multi_channel')));?><br />
      <br />
      <?
      $gWidth  = trim($game->width);
      $gHeight = trim($game->height);
      ?>
      <?php if($edit): ?>
        <?=F::labelSelectList("Resolutions", "resolutions", $resolutions, "{$gWidth}x{$gHeight}");?>
      <?php else: ?>
        <label for="resolutions">Resolution</label>
        <select name="resolutions" id="resolutions"></select>
      <?php endif; ?>
      <br />
      <?=F::labelSelectList("Game active", "active", array("0" => "Inactive", "1" => "Active"), Selected($game, 'active', "1"));?>
      <br />
      <?=F::labelInput("Blocked countries", "blocked_countries", array("size" => 64, "value" => Selected($game, 'blocked_countries')))?>
      <br />
      <?=F::labelInput("Blocked provinces", "blocked_provinces", array("size" => 64, "value" => Selected($game, 'blocked_provinces')))?>
      <br />
      <?=F::labelInput("Included countries", "included_countries", array("size" => 64, "value" => Selected($game, 'included_countries')))?>
      <br />
      <?=F::labelInput("Mobile ID", "mobile_id", array("size" => 20, "value" => Selected($game, 'mobile_id')))?>
      <br />

      <?=F::labelInputFile("Background image", "img_bg", array(), "backgrounds")?><?php echo imginfo("backgrounds/" . Selected($game, 'bkg_pic'));?><br />
      <?=F::labelInputFile("Screenshot image", "img_ss", array(), "screenshots")?><?php echo imginfo('screenshots/' . Selected($game, 'game_id') . '_big.jpg');?><br />
      <?=F::labelInputFile("Thumbnail image", "img_tn", array(), "thumbs")?><?php echo imginfo('thumbs/' . Selected($game, 'game_id') . '_c.jpg');?><br />
      <?=F::labelInputFile("Thumbnail image (High)", "img_tnh", array(), "thumbs")?><?php echo imginfo('thumbs/' . Selected($game, 'game_id') . '_c2.jpg');?><br />
      <?=F::labelInputFile("Mobile game banner image", "img_mb", array(), "thumbs")?><?php echo imginfo('thumbs/' . Selected($game, 'game_id') . '_mb.jpg');?><br />
      <?=F::labelInputFile("Desktop game banner image", "img_db", array(), "thumbs")?><?php echo imginfo('thumbs/' . Selected($game, 'game_id') . '_db.jpg');?><br />
      <?=F::labelInputFile("Game highlights banner image", "img_gh", array(), "thumbs")?><?php echo imginfo('thumbs/' . Selected($game, 'game_id') . '_gh.jpg');?><br />

      <br />
       <?php loadJs("/phive/js/jquery.form.min.js")?>
      <h3>Sidebar Images</h3>
      <?=F::labelInputFile("Sidebar image 1", "img_sr_1", array(), "thumbs")?><?php echo imginfo('thumbs/' . Selected($game, 'game_id') . '_sr1.jpg');?><br />
      <?=F::labelInputFile("Sidebar image 2", "img_sr_2", array(), "thumbs")?><?php echo imginfo('thumbs/' . Selected($game, 'game_id') . '_sr2.jpg');?><br />
      <?=F::labelInputFile("Sidebar image 3", "img_sr_3", array(), "thumbs")?><?php echo imginfo('thumbs/' . Selected($game, 'game_id') . '_sr3.jpg');?><br />
      <?=F::labelInputFile("Sidebar image 4", "img_sr_4", array(), "thumbs")?><?php echo imginfo('thumbs/' . Selected($game, 'game_id') . '_sr4.jpg');?><br />
      <h3>Update</h3>
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
      <?=F::input(array("type" => "hidden", "id" => "id", "name" => "id", value => Selected($game, 'id')))?>
      <?=F::input(array("type" => "hidden", "id" => "save", "name" => "save", value => "1"))?>
      <?php if ($edit !== true):?>
        <?=F::labelInput("Action", "formsubmit", array("type" => "submit", "value" => "Add"));?>
        <?=F::input(array("type" => "button", "id" => "edit-metadescr", value => "Edit/translate meta description"))?>
        <?=F::input(array("type" => "button", "id" => "edit-htmltitle", value => "Edit/translate meta html title"))?>
      <?php else:?>
        <?=F::input(array("type" => "hidden", "id" => "bkg_pic", "name" => "bkg_pic", "value" => Selected($game, 'bkg_pic')))?>
        <?=F::labelInput("Action", "formsubmit", array("type" => "submit", "value" => "Update"));?><?=F::input(array("type" => "button", "id" => "edit-metadescr", value => "Edit/translate meta description"))?><?=F::input(array("type" => "button", "id" => "edit-htmltitle", value => "Edit/translate meta html title"))?>
        <?php if ($brand_game_sync_enabled && $edit && $game->device_type == 'flash' && p('settings.games.section.copy_games_to_brand')): // TODO enable this for mobile only games ?>
          <button type="button" onclick="send_to_brand()">Send to brand</button>
        <?php endif;?>
      <?php endif;?>
      <br />

      <div id="status"></div>
    </div>
  </form>
  <div id="video_section" style="display:none; margin-top: 34px; border-top: 1px solid rgb(204, 204, 204);">
    <h3>Videos (<span class="info" id="countVideos"></span>)</h3>
    <div id="video-section-info"></div>
      <div class="info" >
        <table>
            <thead>
              <th>Video Filename</th>
              <th>Remove</th>
              <th style="width:100px">Screenshot Video</th>
              <th style="width:100px">Screenshot 1</th>
              <th style="width:100px">Screenshot 2</th>
              <th style="width:100px">Screenshot 3</th>
            </thead>
            <tbody id="getVideos"></tbody>
        </table>
      </div>
    <h4>Upload Video</h4>
    <div style="margin:20px;display: none" id="loadingImgVideo" >
      <img src="../../../../phive/js/jcarousel/skins/ie7/loading-small.gif">
    </div>
    <input name="videoFile" id="videoFile" type="file" style="color:black">
    <input type="submit" value="submit file" id="submitVideoFile">
  </div>
  <hr>
  <h4><a href='javascript:void(0)' onclick='generic_attributes(this)' class="btn-generic-attr" style="display:none">Set Generic Attributes</a></h4>
  <div id="game_attributes_specific_section"></div><!-- do not erase this div - container for game attribute field specific game -->
  <div id="new_game_attr_field" style="display:none">
      <h3>New Field</h3>
    <table>
      <thead>
        <tr>
          <th style="width: 150px;">Field</th>
          <th style="width: 150px;">Default Value</th>
          <th style="width: 150px;">Possible Values (comma separated)</th>
          <th style="width: 40px;">Input</th>
          <th style="width: 40px;">Visible Front End</th>
          <th style="width: 40px;">Tab Front End</th>
        </tr>
      </thead>
      <tbody>
          <tr style="background-color:#dadada">
              <td><textarea name="label[]" style="width:90%"></textarea></td>
              <td style="text-align: center;"><input size="15"  id="" name="default_value[]"></td>
              <td style="text-align: center;"><input size="15" id="possible_values[]" name="possible_values[]"></td>
              <td style="text-align: center;">
                  <select name="html_type[]">
                      <option value="text" selected="selected" >text</option>
                      <option  value="radio">radio</option>
                      <option value="select">select</option>
                  </select>
              </td>
              <td style="text-align: center;">
                  <select name="visible_front_end[]">
                      <option value="1">Yes</option>
                      <option value="0" selected="selected" >No</option>
                  </select>
              </td>
              <td style="text-align: center;">
                  <select name="tab_front_end[]">
                      <option value="" selected=""></option>
                      <option value="Overview">Overview</option>
                      <option value="Features">Features</option>
                      <option value="More Info">More Info</option>
                  </select>
              </td>
          </tr>
      </tbody>
    </table>
    <div style="width:100%; text-align: center; margin-top: 20px"><input type="button" onclick="submit_game_attributes('new_game_attr_field','newfield')" value='Submit Field'></div>
  </div>
  <h3 class="message_ajax"></h3>
  <div id="game_attributes_section" style="display: none"></div><!-- do not erase this div - container for game attribute field default games -->
  <div id="area_edit_tr">
  </div>
</div>
