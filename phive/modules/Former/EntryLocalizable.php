<?
require_once __DIR__ . '/EntryText.php';

class EntryLocalizable extends EntryText
{	
	public function output()
	{
		parent::output();
		if (substr($this->getValue(), 0, 1) == "#" && phive()->moduleExists('Localizer')){
			$localizer = phive('Localizer');
			// Switch over to translator mode
			$old_mode = $localizer->getTranslatorMode();
			$localizer->setTranslatorMode(true);
			echo phive('Localizer')->getPotentialString($this->getValue());
			$localizer->setTranslatorMode($old_mode);
		}
	}
}
?>