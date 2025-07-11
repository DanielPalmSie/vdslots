<?
require_once __DIR__ . '/Entry.php';

class EntryList extends Entry
{	
	public function output()
	{
		if (!is_array($options = $this->getSetting('options')))
		{
			trigger_error("EntryList must have a setting named 'options' of type array.", E_USER_ERROR);
			return;
		}
?>
	<select name="<?=$this->getTagName()?>">
<?
		foreach ($options as $key=>$option)
		{
	 		$sel = ($this->getValue()==$key)?"selected":"";
?>
		<option value="<?=$key?>" <?=$sel?>><?=$option?></option>
<?
		}
?>
	</select>
<?
	}
}
?>