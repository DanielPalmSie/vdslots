<?
require_once __DIR__ . '/Entry.php';

class EntryBoolean extends Entry
{	
	public function output()
	{
?>
	<input type="hidden" name="<?=$this->getTagName()?>" value="0" />
	<label><input type="checkbox" name="<?=$this->getTagName()?>" value='1' <?=($this->getValue())?"checked ":""?>/> <?=$this->getSetting('caption')?></label>
<?
	}
}
?>