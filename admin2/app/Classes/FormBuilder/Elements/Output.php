<?php
namespace App\Classes\FormBuilder\Elements;
class Output extends Element {
		
	protected $_m_sDefaultTemplate = 'Snippets/output.html';

	public function create() {	
		return '';//$this->_getElement('<input type="' . $this->_m_sType . '" name="' . $this->_m_sName . '" value="' . $this->_m_sValue . '"' . $this->_getHtmlAttributes() . ' />');
	}
}