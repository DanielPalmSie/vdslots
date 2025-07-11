<?
require_once __DIR__ . '/Entry.php';

class EntrySubmit extends Entry
{
	public function __construct($alias, $arg)
	{
		if (is_array($arg))
			parent::__construct($alias, $arg);
		else
			parent::__construct($alias, array("default"=>$arg));
	}
	
	public function output()
	{
		$onclick_inner = $onclick = '';
		
		if ($js = $this->getSetting('js'))
		{
			$onclick_inner = $js;
		}
		// Check if this button has a specific action
		if ($action = $this->getSetting('action'))
		{
			$form = 'document.'.$this->getForm()->getName();
			if ($onclick_inner)
				$onclick_inner .= ';';
			 $onclick_inner .= "$form.action='$action'"; 
		}
		
		if ($onclick_inner)
			$onclick = 'onClick="' . $onclick_inner . '"';
?>
	<input type="submit" name="<?=$this->getTagName()?>" value='<?=htmlspecialchars($this->getValue(), ENT_QUOTES)?>' <?=$onclick?> />
<?
	}
}

?>