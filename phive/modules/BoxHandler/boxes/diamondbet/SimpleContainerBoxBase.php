<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
class SimpleContainerBoxBase extends DiamondBox{
	public function init(){
		$this->handlePost(array('boxid'));
		/*
		if(!empty($this->boxid))
			$this->box_target = phive('BoxHandler')->getRawBoxById($this->boxid);
			*/
	}
	
	public function printHTML(){

		phive('BoxHandler')->boxHtml($this->boxid);	
		
		/*
		$cur_box_id 	= (int)$this->boxid;
		if($cur_box_id != 0){
			$cur_box_class 	= $this->box_target['box_class'];
			$cur_file = __DIR__.'/../../../../../diamondbet/boxes/'.$cur_box_class.'.php';
			if(is_file($cur_file)){
				require_once $cur_file;
				$cur_box = new $cur_box_class($cur_box_id);
				$cur_box->init();	
			}
		}
		if($cur_box_id != 0) 
			$cur_box->printHTML();
		*/
	}
	
	public function printExtra(){
		?>
			<table>
				<tr>
					<td>
						Parent Box Id: <input name="boxid" value="<?php echo $this->boxid; ?>">
					</td>
				</tr>
			</table>
		<?php
	}
}
?>