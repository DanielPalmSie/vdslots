<?php

namespace App\Classes\FormBuilder\Elements;

class Button extends Element {
		
	protected $_m_sDefaultTemplate = 'Snippets/button.html';

	public function create() {	
		return $this->_getElement('<button type="' . $this->_m_sType . '" name="' . $this->_m_sName . '" value="' . $this->_m_sValue . '"' . $this->_getHtmlAttributes() . '>' . $this->_m_sText . '</button>');
	}
}