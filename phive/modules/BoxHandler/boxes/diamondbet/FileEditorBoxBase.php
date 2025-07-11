<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
class FileEditorBoxBase extends DiamondBox
{
	public function init(){
		if(isset($_POST['save_settings']) && $_POST['box_id'] == $this->getId())
			$this->setAttribute("file_path", $_POST['file_path']);
		
		$this->file_path = $this->getAttribute("file_path");
		
		$this->filer = phive('Filer');
	}
	
	public function printHTML(){
		if($this->filer->saveFile($this->file_path))
			echo "File was saved. <br><br>";
		$this->filer->editForm($this->file_path);
	}
	
	public function printExtra(){
	?>
		<p>
			<label for="button_link">Path to file: </label>
			<input type="text" name="file_path" value="<?=$this->file_path?>" id="file_path"/>
		</p>
	<?php
	}
	
}
