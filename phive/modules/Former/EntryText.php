<?
require_once __DIR__ . '/Entry.php';

class EntryText extends Entry
{	
	public function output()
	{
		$width = $this->getSetting('width');
		$height = $this->getSetting('height');
		$comment = $this->getSetting('comment');
		if ($this->getSetting('constant'))
		{
?>
	<input type="hidden" name="<?=$this->getTagName()?>" value='<?=htmlspecialchars($this->getValue(), ENT_QUOTES)?>' /><?=$this->getValue()?>
<?
		}
		else
		if ($this->getSetting('tinymce'))
		{
			if (!phive()->moduleExists('InputHandler'))
				trigger_error("Can't use tinymce feature with InputHandler module installed", E_USER_ERROR);
			else
				phive('InputHandler')->printTextArea("large", $this->getTagName(),"textarea_id",$this->getValue(),$width,$height);
		}
		else
		if (!$height)
		{
?>
	<input type="text" name="<?=$this->getTagName()?>" style="width: <?=$width?>" value='<?=htmlspecialchars($this->getValue(), ENT_QUOTES)?>' />
<?
		}
		else
		{
?>
			<textarea name="<?=$this->getTagName()?>" style="width: <?=$width?>; height: <?=$height?>" style="<?=$style?>"><?=htmlspecialchars($this->getValue(), ENT_QUOTES)?></textarea>
<?
		}
		if ($comment)
			echo $comment;
	}
}
?>