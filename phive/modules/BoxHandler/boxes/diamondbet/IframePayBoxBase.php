<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';

class IframePayBoxBase extends DiamondBox{
	
	public function init(){
		$this->handlePost(array('script_path'));		
	}
	
	function printHTML(){ ?>
		<div class="payframe">
			<iframe src="<?php echo $this->script_path ?>?action=<?php echo $_GET['action'] ?>&lang=<?php echo phive('Localizer')->getCurNonSubLang() ?>"></iframe>
		</div>
		
	<?php }
	
	function printExtra(){?>
		<p>
			Script path:
			<input type="text" name="script_path" value="<?php echo $this->script_path ?>"/>
		</p>
		
	<?php }
	
}
