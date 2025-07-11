<?
require_once __DIR__ . '/Entry.php';

class EntryInput extends Entry
{	
	public function output()
	{
?>
	<input type="text" name="<?=$this->getTagName()?>" value='<?=htmlspecialchars($this->getValue(), ENT_QUOTES)?>' />
<?
	}
}
?>