<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
class ExpandableBoxBase extends DiamondBox
{
	public function init(){
		if(isset($_POST['save_settings']) && $_POST['box_id'] == $this->getId())
		{
			$this->setAttribute("show_headline", $_POST['show_headline']);
			$this->setAttribute("has_button", $_POST['has_button']);	
			$this->setAttribute("button_link", $_POST['button_link']);	
				
		}
		$this->show_headline = ($this->attributeIsSet("show_headline"))?$this->getAttribute("show_headline"):1;
		$this->has_button = ($this->attributeIsSet("has_button"))?$this->getAttribute("has_button"):0;
		$this->button_link = ($this->attributeIsSet("button_link"))?$this->getAttribute("button_link"):"";
		
	}
	public function getHeadline(){
		if($this->show_headline)
			return t("expandablebox.".$this->getId().".header");
		return null;
	}
	public function printHTML(){?>
		<div class="db_content">
			<?php echo t("expandablebox.".$this->getId().".content.html"); 
			if($this->has_button)
				dbButton(t("expandablebox.".$this->getId().".buttontext"),$this->button_link);
			?>
			
		</div>
	<?php }
	public function printCustomSettings(){
		?>
		<form method="post" action="?editboxes#box_<?= $this->getId()?>">
			<input type="hidden" name="box_id" value="<?=$this->getId()?>"/>
		    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">

		
			<p>
				<label for="show_headline">Show headline: </label>
				<select name="show_headline" id="show_headline">
					<option value="1" <?php if($this->show_headline) echo 'selected="selected"'; ?>>Yes</option>
					<option value="0" <?php if(!$this->show_headline) echo 'selected="selected"'; ?>>No</option>
				</select>
			</p>
			<p>
				<label for="has_button">Show button: </label>
				<select name="has_button" id="show_headline">
					<option value="1" <?php if($this->has_button) echo 'selected="selected"'; ?>>Yes</option>
					<option value="0" <?php if(!$this->has_button) echo 'selected="selected"'; ?>>No</option>
				</select>
			</p>
			<p>
				<label for="button_link">Button link: </label>
				<input type="text" name="button_link" value="<?=$this->button_link?>" id="button_link"/>
			</p>
			<input type="submit" name="save_settings" value="Save and close" id="save_settings"/>
		</form>
		<?php
	}
}

?>