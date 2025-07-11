<?php
namespace App\Classes\FormBuilder\Elements;
class Keygen extends Element {
		
	protected $_m_sDefaultTemplate = 'Snippets/keygen.html';

	public function create() {	
		return $this->_getElement('<keygen type="' . (!empty($this->_m_sType) ? $this->_m_sType : ElementInterface::KEYTYPE_RSA) . '" name="' . $this->_m_sName . '"' . $this->_getHtmlAttributes() . ' />');
	}
}