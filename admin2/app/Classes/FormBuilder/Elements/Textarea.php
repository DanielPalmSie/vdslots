<?php
namespace App\Classes\FormBuilder\Elements;

class Textarea extends Element {
	protected $_m_sDefaultTemplate = 'Snippets/textarea.html';

	public function create() {	
		return $this->_getElement('<textarea name="' . $this->_m_sName . '"' . $this->_getHtmlAttributes() . '>' . $this->_m_sValue . '</textarea>');
	}
}