<?
require_once __DIR__ . '/Entry.php';

class EntryTextarea extends Entry
{
	public function output()
	{
		// Check if FEP_LARGE is checked
		if ($this->params & FEP_LARGE)
			$colsrows = ' cols="60" rows="20"';
		else
			$colsrows = ' cols="42" rows="12"';
?>
			<textarea name="<?=$this->getTagName()?>" <?=$colsrows?> style="<?=$style?>"><?=htmlspecialchars($this->getValue(), ENT_QUOTES)?></textarea>
<?
	}
}

?>