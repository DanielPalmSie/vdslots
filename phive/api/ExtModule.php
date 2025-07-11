<?php
require_once __DIR__ . '/PhModule.php';
class ExtModule extends PhModule{

  function __construct(){
    $this->jquery = false;
  }

  function setCurSite($show){
    $this->showPickSite = $show;
    $this->curSiteId 	= $_SESSION['working_site'];

    if(!empty($_POST['pick_site'])){
      $_SESSION['working_site'] 	= $_POST['pick_site'];
      $this->curSiteId 			= $_SESSION['working_site'];
    }
  }

  function table(){
    return $this->getSetting("TABLE");
  }

  function getForeign(){ return array(); }

  function foreignAttr($col_name){
    if(!empty($this->foreign[ $col_name ]))
      echo "foreign=\"$col_name\"";
  }

  function getForeignAttr($key){
    return $this->foreign[ $_POST['fkey'] ][$key];
  }

  function filterEls($els){
    return $els;
  }

  function getForeignList(){
    $table 	= $this->getForeignAttr('table');
    $label 	= $this->getForeignAttr('label');
    if(is_array($label)){
      echo json_encode($this->getLabel());
    }else{
      $sql 	= "SELECT * FROM ".$this->getForeignAttr('table')." WHERE $label LIKE '%".phive('SQL')->escape($_POST['value'], false)."%'";
      $rarr 	= array();
      $els 	= $this->filterEls(phive('SQL')->loadArray($sql));

      foreach($els as $el)
	$rarr[] = array('el_id' => $el[ $this->getForeignAttr('fid') ], 'el_label' => $el[ $label ]);
      echo json_encode($rarr);
    }
  }

  function displayLabel($el){
    return '';
  }

  function getLabelById($id_name, $id){
    $conf = $this->foreign[$id_name];
    if(!empty($conf)){
      $sql = "SELECT {$conf['label']} FROM {$conf['table']} WHERE {$conf['fid']} = $id";
      return phive('SQL')->getValue($sql);
    }
    return '';
  }

  function getLabel(){
    $config = $this->getForeignAttr('label');
    $cur_table = $this->getForeignAttr('table');
    $sql 	= "SELECT $cur_table.*, {$config['ftable']}.{$config['label']} FROM $cur_table, {$config['ftable']}
		   WHERE $cur_table.{$config['fid']} = {$config['ftable']}.{$config['fid']}
		       AND {$config['label']} LIKE '%".phive('SQL')->escape($_POST['value'], false)."%'";

    $rarr 	= array();

    foreach(phive('SQL')->loadArray($sql) as $el){
      $label = $this->displayLabel($el) == '' ? $el[ $config['label'] ] : $this->displayLabel($el);
      $rarr[] = array('el_id' => $el[ $this->getForeignAttr('fid') ], 'el_label' => $label);
    }
    return $rarr;
  }

  function loadJquery(){
    if($this->jquery == false){
      echo '<script src="/phive/js/jquery.min.js"></script>';
      echo '<script src="/phive/js/jquery.json.js"></script>';
    }
    $this->jquery = true;
  }

  function insert(){
    if(!empty($this->curSiteId))
      $_POST['site_id'] = $this->curSiteId;
    phive('SQL')->insertArray($this->table(), $_POST);
    echo "ok";
  }

  function delete(){
      phive('SQL')->query("DELETE FROM ".$this->table()." WHERE id = ".(int)$_POST['row_id']);
  }

  function showCol($col_name){
    if($col_name == "id")
      return false;
    if(!empty($this->curSiteId) && $col_name == 'site_id')
      return false;
    return true;
  }

  function renderJform($load_jquery = true, $show = false){
    $this->setCurSite($show);
    if(!empty($_POST['id'])){
      phive('SQL')->query("UPDATE ".$this->table()." SET ".phive('SQL')->escape($_POST['field'],false)." = '".phive('SQL')->escape($_POST['val'],false)."' WHERE id = ".intval($_POST['id']));
      echo "ok";
      exit;
    }

    if(!empty($_POST['post_action'])){
      $func = $_POST['post_action'];
      unset($_POST['post_action']);
      $this->$func();
    }

    $struct = phive('SQL')->loadObjects("SHOW COLUMNS IN ".$this->table());
    $where = empty($this->curSiteId) ? '' : " WHERE site_id = ".$this->curSiteId;
    $rows = phive('SQL')->loadArray("SELECT * FROM ".$this->table().$where, 'ASSOC', 'id');
    if($load_jquery)
      $this->loadJquery();
?>

<script type="text/javascript">
  $(document).ready(function(){

    $("#jform_table").find("input").keydown(function(event){
      if(event.keyCode == 13){
        var field_id = $(this).attr("id").split("-");
        $.post(window.location.href, {field: field_id[0], id: field_id[1], val: $(this).val()});
      }
    });

    $("#add_form").find("input").keydown(function(event){
      var cur =  $(this);

      if(cur.attr('foreign').length > 0){

        if($("#el_list").length == 0){
          cur.after('<select id="el_list"></select>');
          $("#el_list").blur(function(){
            var cur_id = $(this).find("option:selected").val();
            cur.val( cur_id );
            $(this).remove();
          });
        }

        if(cur.val().length >= 2){
          $.post(window.location.href, {value: cur.val(), func: 'getForeignList', fkey: cur.attr('foreign')}, function(res){
            res = eval( '(' + res + ')' );
            var str = '';
            $.each(res, function(){
              str += '<option value="'+this.el_id+'">'
                + this.el_label
                + '</option>';
            });
            $("#el_list").html(str);
          });
        }
      }
    });

    $("div[id^='delete-']").click(function(){
      var id = $(this).attr("id").split("-").pop();
      $.post(window.location.href, {row_id: id}, function(res){
        $("#row-"+id).hide("slow");
      });
    });
  });
</script>

<a href="/admin">Back</a>
<br><br>
<?php if($this->showPickSite == true): ?>
  <br>
  <form action="<?php echo 'http'.phive()->getSetting('http_type').'://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] ?>" method="POST">
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
    <?php phive('Site')->sitesSelect(false, false, array(), 'name="pick_site"', true, $this->curSiteId) ?>
    <input type="submit" id="delete_submit" value="Submit">
  </form>
  <br>
<?php endif ?>
Add:
<form id="add_form" action="<?php echo 'http'.phive()->getSetting('http_type').'://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] ?>" method="POST">
  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
  <table>
    <tr>
      <?php foreach($struct as $row): ?>
	<?php if( $this->showCol($row->Field) ): ?>
	  <th><?php echo ucfirst($row->Field) ?></th>
	<?php endif ?>
      <?php endforeach ?>
    </tr>
    <tr>
      <?php foreach($struct as $row): ?>
	<?php if( $this->showCol($row->Field) ): ?>
	  <td><input class="add_input" <?php $this->foreignAttr( $row->Field ) ?> type="text" name="<?php echo $row->Field ?>"/></td>
	<?php endif ?>
      <?php endforeach ?>
      <td> <input type="submit" id="add_submit" value="Save"> </td>
    </tr>
  </table>
  <input type="hidden" name="post_action" value="insert"/>
</form>
Edit:
<table id="jform_table">
  <?php foreach($rows as $id => $row): ?>
    <tr id="row-<?php echo $id ?>" >
      <?php foreach($row as $key => $val): ?>
	<?php if( $this->showCol($key) ): ?>
	  <td> <?php echo $this->getLabelById($key, $val) ?> <input id="<?php echo "$key-$id" ?>" type="text" value="<?php echo $val ?>" /> </td>
	<?php endif ?>
      <?php endforeach ?>
      <td>
	<form action="<?php echo 'http'.phive()->getSetting('http_type').'://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] ?>" method="POST">
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
	  <input type="submit" id="delete_submit" value="Delete">
	  <input type="hidden" name="post_action" value="delete"/>
	  <input type="hidden" name="row_id" value="<?php echo $id ?>"/>
	</form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
		<?php
	}
}
?>
