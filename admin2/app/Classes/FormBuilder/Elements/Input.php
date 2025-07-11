<?php
namespace App\Classes\FormBuilder\Elements;
class Input extends Element {
		
	protected $_m_sDefaultTemplate = 'Snippets/input.html';

	public function create() {	
		return $this->_getElement('<input type="' . $this->_m_sType . '" name="' . $this->_m_sName . '" value="' . $this->_m_sValue . '"' . $this->_getHtmlAttributes() . ' />');
	}
}