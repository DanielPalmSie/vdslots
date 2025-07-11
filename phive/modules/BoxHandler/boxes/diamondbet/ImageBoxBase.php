<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
class ImageBoxBase extends DiamondBox{
	
	public function init(){
		if(isset($_POST['save_settings']) && $_POST['box_id'] == $this->getId()){
			$this->setAttribute("width", $_POST['width']);
			$this->setAttribute("height", $_POST['height']);
		}
		
		$this->width 	= $this->getAttribute("width");
		$this->height	= $this->getAttribute("height");
	}
	
	public function printHTML(){?>
		<?php if(!empty($this->width)): ?>
			<div style="width:<?php echo $this->width ?>px; height:<?php echo $this->height ?>px;">
			    <?php img('img.'.phive('Localizer')->getCurNonSubLang().'.box'.$this->getId(), $this->width, $this->height) ?>
			</div>
		<?php endif ?>
	<?php }
	
	function printExtra(){?>
		<p>
			Width:
			<input type="text" name="width" value="<?php echo $this->width ?>"/>
		</p>
		<p>
			Height:
			<input type="text" name="height" value="<?php echo $this->height ?>"/>
		</p>
		
	<?php }
}
?>