<?php
require_once __DIR__ . '/../../../admin.php';
$_POST = array_map("stripslashes", $_POST);
if($_POST['action'] == "ajax"){
  switch($_POST['method']){
    case 'getCompatibleBoxesFor':
      echo implode("\n", phive("BoxHandler")->getCompatibleBoxesFor($_POST['container']));
      break;
    case 'getBoxAttributes':
      ajax_getBoxAttributes($_POST['box_id']);
      break;
    case 'updateAttribute':
      if(!phive("BoxHandler")->getBoxById($_POST['box_id'])->setAttribute($_POST['name'], $_POST['value']))
        echo "error";
      break;
    case 'deleteAttribute':
      if(!phive("BoxHandler")->getBoxById($_POST['box_id'])->deleteAttribute($_POST['name']))
        echo "error";
      break;
    case 'getBoxesForPage':
      ajax_getBoxesForPage($_POST['page_id']);
      break;
    case 'move_up':
      phive("BoxHandler")->moveUp($_POST['box_id']);
      break;
    case 'move_down':
      phive("BoxHandler")->moveDown($_POST['box_id']);
      break;
    case 'delete':
      phive("BoxHandler")->deleteBox($_POST['box_id']);
      break;
    case 'addbox':
      $boxclass = $_POST['type'];
      $container = $_POST['container'];
      $page_id = $_POST['page_id'];
      if(!phive("BoxHandler")->addBox($boxclass, $container, $page_id))
        echo "error";
      break;
    case 'transfer':
      phive("BoxHandler")->transfer($_POST['box_id'], $_POST['newpage']);
      break;
  }
  exit();
}

echo '<?xml version="1.0" charset="utf-8" ?>';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html>
<head>
  <meta http-equiv="Content-type" content="text/html; charset=utf-8">
  <title>Edit Boxes</title>
  <style type="text/css" >
    .controller{
      text-decoration: none;
    }
    .green{
      color: green;
    }
    .red{
      color: red;
    }
    table.boxlister	{
      border-collapse: collapse;
    }
    table.boxlister td{
      border-left: 1px solid black;
      padding-left: 10px;
      padding-right: 10px;
      vertical-align: top;
    }
    table.boxlister td:first-child{
      border-left: 0;
    }
    .container_hdr{
      text-decoration: underline;
      font-weight: bold;
      margin: 0;
    }

    #popupStyler table{
      width: 100%;
      border-spacing: 0px;
      border-collapse: collapse;
    }
    #attr_list_tbl td{
      border-top: 1px solid black;
      border-bottom: 1px solid black;
    }
    #popupStyler{
      width: 600px;
      border: 1px solid black;
    }
  </style>
  <script src="<?=phive()->getPath()?>/modules/BoxHandler/BoxActions.js" type="text/javascript" charset="utf-8"></script>
  <script type="text/javascript" charset="utf-8">
    function highlightAttribute(attrname, highlight)
    {
      if(highlight)
        var clr = '#c9c9c9';
      else
        var clr = 'white';
      $(attrname+'_tr').setStyle({backgroundColor: clr});
    }


    function checkSelection()
    {
      el = document.getElementById("container");
      el2 = document.getElementById("othercontainer");
      if(el.value == '__other__')
        el2.style.display = "inline";
      else
        el2.style.display = "none";
    }

    function limitBoxtypes()
    {
      var container = document.getElementById("container").value;
      if(container == '__other__')
        container = document.getElementById("othercontainer").value;

      var newoptions;

      new Ajax.Request("<?=$_SERVER['REQUEST_URI']?>", {
        parameters: {action: 'ajax', method: 'getCompatibleBoxesFor', container: container},
        onSuccess: function(transport){
             newoptions = transport.responseText.split("\n");
          var selector = document.getElementById("type");
          var options = selector.getElementsByTagName("option");

          while(options.length > 0)
          {
            selector.removeChild(options[0]);
          }

          for(var i = 0; i<newoptions.length; ++i)
          {
            var newopt = document.createElement('option');
            newopt.setAttribute("value", newoptions[i]);
            newopt.innerHTML = newoptions[i];
            selector.appendChild(newopt);
          }
          if(!$('type').getValue())
            $('submit_addbox').setAttribute('disabled', 'disabled')
          else
            $('submit_addbox').removeAttribute('disabled');
        },
          onFailure: function(){ alert('Ajax Error: Could not retrieve compatible boxes...') }
        });
    }

    function editAttr(name, value)
    {
      $('attr_name').setValue(name);
      $('attr_value').setValue(value);
      $('attr_value').select();
    }

    function openAttrEditor(box_id)
    {
      $('attributes_editor').innerHTML = "Loading...";
      new Ajax.Request("<?=$_SERVER['REQUEST_URI']?>", {
        parameters: {action: 'ajax', method: 'getBoxAttributes', box_id: box_id},
        onSuccess: function(transport){
          $('attributes_editor').innerHTML = transport.responseText;
          $("attributes_editor").clonePosition($("box_link_"+box_id), {setWidth: false, setHeight: false, offsetTop: -4, offsetLeft: -3});
          $("attributes_editor").show();
          $("attributes_editor").focus();
        },
          onFailure: function(){ alert('Ajax Error: Could not retrieve box attributes...') }
        });
    }
    function closeAttrEditor()
    {
      $("attributes_editor").hide();
    }

    function getBoxesForPage(page_id)
    {
      $("boxes_editor").innerHTML = "Loading...";
      new Ajax.Updater("boxes_editor", "<?=$_SERVER['REQUEST_URI']?>", {parameters: {
        action: 'ajax', method: 'getBoxesForPage', page_id: page_id },
        onComplete: function(transport){
          checkSelection();
          limitBoxtypes();
        }});
    }
    function finishLoading()
    {
      getBoxesForPage($('page').getValue());

      document.observe('keypress',
        function(event)
        {
          if(event.keyCode == Event.KEY_ESC)
            closeAttrEditor();
        });
    }
  </script>
</head>
<body id="editboxes" onload="finishLoading()">
  <?php
  $pager = phive("Pager");
  $pages = $pager->getHierarchy();
  ?>
  <label for="page">Page:</label>
  <select name="page" id="page" onchange="getBoxesForPage($('page').getValue()); closeAttrEditor();">
  <?php	foreach ($pages as $page):?>
    <option value="<?=$page['page_id']?>" <?php if($_GET['page_id'] == $page['page_id']) echo 'selected="selected"'?>><?=str_replace(' ', '&nbsp;&nbsp;',str_pad('', $page['depth'])).$page['alias']?></option>
    <?php
  endforeach;
  ?>
  </select>

  <div id="boxes_editor">
    <?php
    function ajax_getBoxesForPage($page_id) {
      $page = phive("Pager")->getPage($page_id);
    ?>
    <fieldset>
      <legend><a style="color: blue;" href="<?=phive("Pager")->getPath($page_id);?>"><?=phive("Pager")->getPath($page_id);?></a></legend>
      <table class="boxlister">
        <tr>
        <?php
        $containers = phive("BoxHandler")->getBoxesInPage($page['page_id'], true);
        foreach ($containers as $container => $boxes):?>
        <td>
          <p class="container_hdr"><?=$container?></p>
          <?php foreach ($boxes as $box): ?>
              <a label="Edit attributes" id="box_link_<?=$box->getId()?>" href="javascript: openAttrEditor(<?=$box->getId()?>)"><?=$box->getType()?></a><span style="font-size: 9px;">[<?=$box->getId()?>]</span>
              <a class="controller green" href="javascript:boxAction(<?=$box->getId()?>, 'move_down', function(){getBoxesForPage($('page').getValue());});">&darr;</a>
              <a class="controller green" href="javascript:boxAction(<?=$box->getId()?>, 'move_up', function(){getBoxesForPage($('page').getValue());});">&uarr;</a>
              <a class="controller red" href="javascript:boxAction(<?=$box->getId()?>, 'delete', function(){getBoxesForPage($('page').getValue());});"><span style="font-family: sans-serif;">x</span></a>
              <br/>
          <?php endforeach;?>
        </td>
        <?php endforeach; ?>
        </tr>
      </table><br/>
      <table>
        <tr>
          <td>
            <label for="container">Container:</label>
          </td>
          <td>
            <select name="container" id="container" onchange="checkSelection(); limitBoxtypes();">
              <?php
              $containers = phive("BoxHandler")->getAllUsedContainers();
              sort($containers);
              foreach ($containers as $container):?>
              <option value="<?=$container?>"><?=$container?></option>
              <?php endforeach ?>
              <option value="__other__">Other</option>
            </select>
            <input style="display: none;" type="text" id="othercontainer" name="othercontainer" value="" onchange="limitBoxtypes()" onkeyup="limitBoxtypes()"/>
          </td>
        </tr>
        <tr>
          <td>
            <label for="type">Type:</label>
          </td>
          <td>
            <select name="type" id="type">
            </select>
          </td>
        </tr>
      </table>
      <input onclick="if($F('container') != '__other__') c = $F('container'); else c = $F('othercontainer'); addBox($F('type'), c, $F('page'), function(){getBoxesForPage($('page').getValue());} );" id="submit_addbox" type="submit" value="Add box" disabled="disabled"/>
    </fieldset>
    <?php
    }
    ?>
  </div><br/>
  <div id="attributes_editor">
    <?php
    function ajax_getBoxAttributes($box_id)
    {
      $boxpage = phive("BoxHandler")->getBoxPage($box_id);
      ?><div id="popupStyler"><?php
      $box = phive("BoxHandler")->getBoxById($box_id, true);
      if(!$box)
        die("No such box");?>

      <?php
      $pager = phive("Pager");
      $pages = $pager->getHierarchy();
      ?>
      <label for="page">Move box to page:</label>
      <select name="page" id="page" onchange="transferBox(<?=$box_id?>, this.value, function(){getBoxesForPage($('page').getValue()); closeAttrEditor();});">
      <?php	foreach ($pages as $page):
        if($page['alias'] == "editboxes")
          continue;
        ?>
        <option value="<?=$page['page_id']?>" <?php if($boxpage == $page['page_id']) echo 'selected="selected"'?>><?=str_replace(' ', '&nbsp;&nbsp;',str_pad('', $page['depth'])).$page['alias']?></option>
        <?php
      endforeach;
      ?>
      </select>

      <div style="text-align: center; font-weight: bold;">Attributes for <?=$box->getType()?><span style="font-size: 9px;">[<?=$box->getId()?>]</span></div>

      <table id="attr_list_tbl">
        <?php
        $attributes = $box->getAttributes();
        foreach($attributes as $name => $value):
        $name = htmlspecialchars($name);
        $value = htmlspecialchars($value);
        ?>
        <tr onclick="editAttr('<?=addslashes($name)?>', '<?=addslashes($value)?>')" onmouseover="highlightAttribute('<?=addslashes($name)?>', true)" onmouseout="highlightAttribute('<?=$name?>', false)" id="<?=$name?>_tr">
          <td style="width: 30%;" >
            <a href="javascript: deleteAttribute(<?=$box->getId()?>, '<?=addslashes($name)?>', function(){openAttrEditor(<?=$box->getId()?>)});" style="color: red; text-decoration: none;"><span style="font-family: sans-serif;">x</span></a> <?=$name?>
          </td>
          <td style="width: 70%;">
            = <?=$value?>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>
      <form>
      <table id="attr_editor_frm">
      <tr>
        <td><label for="name" style="margin-right: 20px">Name</label></td>
          <td>
            <input type="text" id="attr_name" name="name" value='' />
          </td>
      </tr>
      <tr>
        <td><label for="value" style="margin-right: 20px">Value</label></td>
          <td>
            <input type="text" id="attr_value" name="value" value='' />
          </td>
      </tr>
      <tr>
          <td>
            &nbsp;
          </td>
          <td>
            <input type="submit" id="update_attribute" name="update_attribute" value='Save attribute' onClick='updateAttribute(<?=$box->getId()?>, $("attr_name").getValue(), $("attr_value").getValue(), function(){openAttrEditor(<?=$box->getId()?>)}); return false;' />
          </td>
      </tr>
      </table>
      </form>
      </div>
    <?php
    }
    ?>
    Delete box with id:
    <form method="post">
      <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
      <input type="text" id="delete_box" name="delete_box" value='' />
      <input type="submit" id="submit" name="submit" value='Delete' />
    </form>
  </div>
</body>
</html>
