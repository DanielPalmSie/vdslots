<?php
namespace App\Classes\FormBuilder\Elements;

class Datalist extends Element {
	protected $_m_sDefaultTemplate = 'Snippets/datalist.html';

	public function create() {
		
		if(isset($this->_m_oAttributes->id)){
			$sId = $this->_m_oAttributes->id;
			unset($this->_m_oAttributes->id);
		} else {
			$sId = $this->_m_sName;
		}
		
		return $this->_getElement('
			<input type="' . ElementInterface::TYPE_TEXT . '" name="' . $this->_m_sName . '" id="list_' . $sId . '" value="' . $this->_m_sValue . '"' . $this->_getHtmlAttributes() . ' list="' . $sId . '" />
			<datalist id="' . $sId . '">' . $this->_getOptions() . '</datalist>'
		);
	}
		
	private function _getOptions(){
		$aOptions = array();
		foreach($this->_m_aOptions as $value){
			$aOptions[] = PHP_EOL . '<option value="' . $value . '" />';		
		}
		return implode('', $aOptions);
	}
}