<?
require_once __DIR__ . '/Entry.php';

class EntryHidden extends Entry
{
	public function __construct($alias, $value)
	{
		parent::__construct($alias, array("default"=>$value));
	}
	
	public function isRendered()
	{
		return false;
	}
	
	public function output()
	{
?>
	<input type="hidden" name="<?=$this->getTagName()?>" value='<?=htmlspecialchars($this->getValue(), ENT_QUOTES)?>' />
<?
	}
}

?>