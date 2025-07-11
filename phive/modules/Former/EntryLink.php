<?
require_once __DIR__ . '/Entry.php';

// This entry type allows you to input either a manual
//  link or a page reference (from the Pager module).
// This is wise to use especially if the Pager module
//  is localized and different pages can have different
//  URLS in different languages.

class EntryLink extends Entry
{	
	public function getExtraValues()
	{
		return array("page_id");
	}
	
	public function output()
	{
		if (is_array($options = $this->getSetting('pages')))
		{
			$page_id = $this->getValue('page_id');
?>
	<select name="<?=$this->getTagName()?>_page_id">
<?
			foreach ($options as $key=>$option)
			{
	 			$sel = ($page_id==$key)?"selected":"";
?>
		<option value="<?=$key?>" <?=$sel?>><?=$option?></option>
<?
			}
?>
	</select><br />
<?
		}
?>
	<input type="text" name="<?=$this->getTagName()?>" value='<?=htmlspecialchars($this->getValue(), ENT_QUOTES)?>' />
<?
	}
}
?>