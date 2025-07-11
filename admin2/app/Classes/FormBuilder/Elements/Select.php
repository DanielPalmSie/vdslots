<?php
namespace App\Classes\FormBuilder\Elements;
class Select extends Element {
	protected $_m_sDefaultTemplate = 'Snippets/select.html';

	public function create() {
		return $this->_getElement('<select name="' . $this->_m_sName . '"' . $this->_getHtmlAttributes() . '>' . $this->_getOptions() . '</select>');
	}
		
	private function _getOptions(){
		$aOptions = array();
		foreach($this->_m_aOptions as $key => $oOption){
			$sOption  = '<option value="' . (isset($oOption->value) ? $oOption->value : '') . '"';
			$sOption .= (($this->_m_sValue == $oOption->value) ? ' selected="selected"' : '');
			
			if (!empty($oOption->attr)) {
				foreach ($oOption->attr as $key => $value) {
					$sOption .= ' ' . $key . '="' . $value . '"';
				}
			}
			
			$aOptions[] = PHP_EOL . $sOption . '>' . (isset($oOption->text) ? $oOption->text : '') . '</option>';			
		}
		return implode('', $aOptions);
	}
}