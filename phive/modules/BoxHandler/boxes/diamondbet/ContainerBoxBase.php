<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
require_once __DIR__.'/../../../../../diamondbet/html/display.php';
class ContainerBoxBase extends DiamondBox{
  public function init(){
    parent::init();
      $this->handlePost(array('row_count', 'col_count', 'box_ids', 'box_class', 'inline_css'));
      if ($this->box_ids && phive('SQL')->query(
              "SELECT box_class, box_id FROM boxes WHERE box_id IN(" . str_replace(':', ',', $this->box_ids) . ")"
          )) {
          $this->sub_boxes = phive('SQL')->fetchArray('ASSOC', 'box_id');
      }

    $this->box_class = empty($this->box_class) ? 'boxes-container' : $this->box_class;
    $this->width 		= 100 / $this->col_count;
  }

  public function printHTML(){
    if(!empty($this->sub_boxes)){
      $box_arr 	= explode(':', $this->box_ids);
      if(!empty($this->width))
	$this->width = "width: {$this->width}%;";
?>
<div class="<?php echo $this->box_class ?>" style="<?php echo $this->inline_css ?>">
  <table class="container-cont">
    <?php for($i = 0; $i < $this->row_count; $i++): ?>
      <tr>
	<?php for($j = 0; $j < $this->col_count; $j++):
		       $cur_box_id 	= (int)trim($box_arr[ $j + ($i * $this->col_count) ]);
	if($cur_box_id != 0){
	  $cur_box_class 	= $this->sub_boxes[ $cur_box_id ]['box_class'];
	  $cur_file = __DIR__.'/../../../../../diamondbet/boxes/'.$cur_box_class.'.php';
	  if(is_file($cur_file)){
	    require_once $cur_file;
	    $cur_box = new $cur_box_class($cur_box_id);
	    $cur_box->baseInit();
	    $cur_box->init();
	  }
	}

	if($j == 0)
	  $css_class = 'first-container';
	else if($j == $this->col_count - 1)
	$css_class = 'last-container';
	else
	  $css_class = 'container';
	?>
	  <td id="td<?php echo $cur_box_id ?>" style="vertical-align: top; <?php echo $this->width; ?>" class="<?php echo $css_class ?>">
	    <?php
	    if($cur_box_id != 0 && is_object($cur_box)){
	      if(phive('Permission')->willEditBoxes())
		$cur_box->printModeratorHTML(true);
	      else
		$cur_box->printHTML();
	    }
	    ?>
	    <td>
	<?php endfor ?>
      </tr>
    <?php endfor; ?>
  </table>
</div>
<?php
}
}

public function printExtra(){
?>
<table>
  <tr>
    <td>
      Number of Rows: <input style="width:50px;" name="row_count" value="<?php echo $this->row_count; ?>">
    </td>
  </tr>
  <tr>
    <td>
      Number of Columns: <input style="width:50px;" name="col_count" value="<?php echo $this->col_count; ?>">
    </td>
  </tr>
  <tr>
    <td>
      Sub boxes, top left to bottom right (id1:id2...):<br />
      <input style="width:450px;" name="box_ids" value="<?php echo $this->box_ids; ?>">
    </td>
  </tr>
  <tr>
    <td>
      Box class: <input style="width:50px;" name="box_class" value="<?php echo $this->box_class; ?>">
    </td>
  </tr>
  <tr>
    <td>
      Inline CSS:
      <br />
      <textarea name="inline_css" cols="50" rows="5"><?php echo $this->inline_css; ?></textarea>
    </td>
  </tr>
</table>
<?php
}
}
