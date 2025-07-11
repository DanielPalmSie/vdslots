<?php
namespace App\Classes\FormBuilder\Elements;

class stdClass {}

abstract class Element implements ElementInterface {
	
	protected $_m_sType = '';
	protected $_m_oValidate = null;
	protected $_m_sDefaultTemplate = '';
	protected $_m_sDefaultCommentTemplate = 'Snippets/comment.html';
	protected $_m_sUseTemplate = '';
	protected $_m_sName = '';
	protected $_m_sValue = '';
	protected $_m_sLabel = '';
	protected $_m_sElement = '';
	protected $_m_sText = '';
	protected $_m_bLabelWrap = false;
	protected $_m_bLabelWrapAfter = false;
	protected $_m_oRules = null;
	protected $_m_oLabelAttributes = null;
	protected $_m_oAttributes = null;
	protected $_m_aOptions = array();	
	protected $_m_mComment = '';
	
	public function __construct(ValidateInterface $p_oValidate = null) {
		$this->_m_oValidate = $p_oValidate;
	}

	abstract public function create();

	public function type($p_sType = \App\Classes\FormBuilder\Elements\ElementInterface::TYPE_TEXT) {
		$this->_m_sType = $p_sType;
		return $this;
	}
	
	/**
	 * Inject class dependencies
	 *
	 * @param object $p_oDependency Instance of the dependent class
	 * @return bool false if dependency couldn't be set
	 */
	public function injectDependency($p_oDependency){
		switch ($p_oDependency){
			case $p_oDependency instanceof ValidateInterface:
				$this->_m_oValidate = $p_oDependency;
				break;

			default:
				return false;
		}
		return $this;
	}
	
	public function name($p_sName) {
		$this->_m_sName = $p_sName;
		return $this;
	}

	/**
	 * Set the input value
	 * 
	 * @param string $p_sValue
	 */
	public function value($p_sValue = '') {
		$this->_m_sValue = $p_sValue;
		return $this;
	}

	/**
	 * Options used for select, checkbox, radio
	 * @param stdClass $p_oOptions
	 */
	public function options(array $p_aOptions = array()){
		$this->_m_aOptions = $p_aOptions;
		return $this;		
	}
	
	public function label($p_sLabel = '', $p_oLabelOptions = null) {
		$this->_m_sLabel = $p_sLabel;
		if (!empty($p_oLabelOptions)) {
			if (isset($p_oLabelOptions->wrap)) {
				$this->_m_bLabelWrap = $p_oLabelOptions->wrap;
				unset($p_oLabelOptions->wrap);
			}
			if (isset($p_oLabelOptions->after)) {
				$this->_m_bLabelWrapAfter = $p_oLabelOptions->after;
				unset($p_oLabelOptions->after);
			}
			if (isset($p_oLabelOptions->attr)) {
				$this->_m_oLabelAttributes = $p_oLabelOptions->attr;
				unset($p_oLabelOptions->attr);
			}
		}
		return $this;
	}

	/**
	 * Set the attributes for the input field
	 * 
	 * @param array $p_aAttr
	 */
	public function attr($p_oAttr = null) {
		$this->_m_oAttributes = $p_oAttr;
		return $this;
	}

	/**
	 * Set the template to use for the input field
	 * 
	 * @param string $p_sTemplate
	 */
	public function template($p_sTemplate) {
		$this->_m_sUseTemplate = $p_sTemplate;
		return $this;
	}
	
	/**
	 * Set the text used for buttons
	 *
	 * @param string $p_sText
	 */
	public function text($p_sText) {
		$this->_m_sText = $p_sText;
		return $this;
	}
	
	/**
	 * Set the text used for comments
	 *
	 * @param mixed $p_mComment
	 */
	public function comment($p_mComment) {	
		$this->_m_mComment = $p_mComment;
		return $this;
	}
	
	/**
	 * Set the rules for the input field
	 * 
	 * @param array $p_aRules
	 */
	public function rules($p_oRules = null) {
		$this->_m_oRules = $p_oRules;
		return $this;
	}

	public function setElementName($p_sElement){
		$this->_m_sElement = $p_sElement;
	}
	
	public function isValid() {
		if (!empty($this->_m_oRules)) {
			foreach ($this->_m_oRules as $method => $mOptions) {
				if (method_exists($this->_m_oValidate, $method)) {
					$this->_m_oValidate->$method($this->_m_sValue);
				}
			}
		}
		return empty($this->getErrors());
	}

	public function getErrors() {
		return $this->_m_oValidate->getErrors();
	}
		
	protected function _getHtmlAttributes(){
	
		$sAttr = $this->_getHtmlAttributesByRules();

		if (!empty($this->_m_oAttributes)) {
			
			unset($this->_m_oAttributes->type, $this->_m_oAttributes->keytype, $this->_m_oAttributes->name);
			
			if (isset($this->_m_oAttributes->disabled)){
				if($this->_m_oAttributes->disabled === true && in_array($this->_m_sElement, array('input', 'select', 'textarea', 'button', 'keygen'))) {
					$sAttr .= ' disabled';
				}
				unset($this->_m_oAttributes->disabled);
			}
			
			if (isset($this->_m_oAttributes->readonly)){
				if($this->_m_oAttributes->readonly === true && in_array($this->_m_sElement, array('input', 'select', 'textarea'))) {
					$sAttr .= ' readonly';
				}
				unset($this->_m_oAttributes->readonly);
			}
			
			if (isset($this->_m_oAttributes->autofocus)){
				if($this->_m_oAttributes->autofocus === true && in_array($this->_m_sElement, array('input', 'select', 'textarea', 'button', 'keygen'))) {
					$sAttr .= ' autofocus';
				}
				unset($this->_m_oAttributes->autofocus);
			}
			
			if (isset($this->_m_oAttributes->challenge)){
				if($this->_m_oAttributes->challenge === true && in_array($this->_m_sElement, array('keygen'))) {
					$sAttr .= ' challenge';
				}
				unset($this->_m_oAttributes->challenge);
			}
			
			if (isset($this->_m_oAttributes->checked)){
				if($this->_m_oAttributes->checked === true && in_array($this->_m_sType, array('checkbox', 'radio'))) {
					$sAttr .= ' checked';
				}
				unset($this->_m_oAttributes->checked);
			}
			
			if (!isset($this->_m_oAttributes->id) && $this->_m_sElement !== 'datalist') {
				$sAttr .= ' id="' . $this->_m_sName . '"';
			}
				
			if (isset($this->_m_oAttributes->placeholder)){
				if(in_array($this->_m_sElement, array('input', 'textarea'))) {
					$sAttr .= ' placeholder="' . $this->_m_oAttributes->placeholder . '"';
				}
				unset($this->_m_oAttributes->placeholder);
			}
			
			if (isset($this->_m_oAttributes->autocomplete) || $this->_m_sType === 'password'){
				if(($this->_m_oAttributes->autocomplete === false && in_array($this->_m_sElement, array('input'))) || $this->_m_sType === 'password') {			
					$sAttr .= ' autocomplete="off"';
				}
				unset($this->_m_oAttributes->autocomplete);
			}					
		}

		if (!empty($this->_m_oAttributes)) {
			foreach ($this->_m_oAttributes as $key => $value) {
				$sAttr .= ' ' . $key . '="' . $value . '"';
			}
		}
				
		return $sAttr;
	}
	
	protected function _getElement($p_sFormField) {
		if (!empty($this->_m_sLabel)) {
			$sLabel = '<label'; 
			$sLabel .= ((!$this->_m_bLabelWrap) ? ' for="' . (isset($this->_m_oAttributes->id) ? $this->_m_oAttributes->id : $this->_m_sName) . '"' : '');
			if (!empty($this->_m_oLabelAttributes)) {
				foreach ($this->_m_oLabelAttributes as $key => $value) {
					$sLabel .= ' ' . $key . '="' . $value . '"';
				}
			}
			$sLabel .= '>{{before}}{{formfield}}{{after}}</label>';
			$aSearch = array (
				'{{before}}',
				'{{formfield}}',
				'{{after}}' 
			);
			$aReplace = array (
				((!$this->_m_bLabelWrapAfter) ? $this->_m_sLabel : ''),
				(($this->_m_bLabelWrap) ? $p_sFormField : ''),
				(($this->_m_bLabelWrapAfter) ? $this->_m_sLabel : '') 
			);
			$sLabel = str_replace($aSearch, $aReplace, $sLabel);
		}
		
		if(is_object($this->_m_mComment) && is_file(__DIR__ . '/../' . $this->_m_mComment->template) && !empty($this->_m_mComment->text)){
			$sComment = str_replace('{{formfieldComment}}', $this->_m_mComment->text, file_get_contents(__DIR__ . '/../' . $this->_m_mComment->template));
		} elseif(is_string($this->_m_mComment)) {
			$sComment = (is_file(__DIR__ . '/../' . $this->_m_sDefaultCommentTemplate) ? str_replace('{{formfieldComment}}', $this->_m_mComment, file_get_contents(__DIR__ . '/../' . $this->_m_sDefaultCommentTemplate)) : $this->_m_mComment);
		}
		
		$sComment = (empty($this->_m_mComment) ? '' : $sComment);
		$sTemplate = (is_file($this->_m_sUseTemplate) ? $this->_m_sUseTemplate : $this->_m_sDefaultTemplate);
		$aSearch = array (
			'{{labelBefore}}',
			'{{labelAfter}}',
			'{{formfield}}',
			'{{formfieldComment}}'
		);
		$aReplace = array (
			((!$this->_m_bLabelWrapAfter) ? $sLabel : ''),
			(($this->_m_bLabelWrapAfter) ? $sLabel : ''),
			(($this->_m_bLabelWrap) ? '' : $p_sFormField),
			$sComment		
		);

		return str_replace($aSearch, $aReplace, file_get_contents(__DIR__ . '/../' . $sTemplate));
	}
	
	private function _getHtmlPattern($p_sKey){
		
		$sRequired = (empty($this->_m_oRules->required) ? '.{0}|' : '');
		$sMinMaxLength = '{' . $this->_m_oRules->minlength . ',' . (empty($this->_m_oRules->maxlength) ? '' : $this->_m_oRules->maxlength) . '}';
		
		$aPatterns = array(
			'minlength' => $sRequired . '.' . $sMinMaxLength,
			'alnum' => $sRequired . '^[A-Za-z0-9]' . $sMinMaxLength . '$',
			// other patterns can be added here
		);
	
		if(isset($aPatterns[$p_sKey])){
			return $aPatterns[$p_sKey];
		}
		return $p_sKey;
	}
	
	private function _getHtmlAttributesByRules(){
	
		$sAttr = '';

		if (!empty($this->_m_oRules)) {
	
			if (!isset($this->_m_oAttributes->min) && isset($this->_m_oRules->min) && in_array($this->_m_sElement, array('input'))) {
				$sAttr .= ' min="' . $this->_m_oRules->min . '"';
				unset($this->_m_oAttributes->min);
			}
			
			if (!isset($this->_m_oAttributes->max) && isset($this->_m_oRules->max) && in_array($this->_m_sElement, array('input'))) {
				$sAttr .= ' max="' . $this->_m_oRules->max . '"';
				unset($this->_m_oAttributes->max);
			}
						
			if (!isset($this->_m_oAttributes->maxlength) && isset($this->_m_oRules->maxlength) && in_array($this->_m_sElement, array('input','textarea'))) {
				$sAttr .= ' maxlength="' . $this->_m_oRules->maxlength . '"';
				unset($this->_m_oAttributes->maxlength);
			}

			if (!isset($this->_m_oAttributes->required) && isset($this->_m_oRules->required) && $this->_m_oRules->required === true && in_array($this->_m_sElement, array('input', 'select', 'textarea'))) {
				$sAttr .= ' required';
				unset($this->_m_oAttributes->required);
			}
			
			
			if (!isset($this->_m_oAttributes->pattern) && isset($this->_m_oRules->pattern) && in_array($this->_m_sElement, array('input'))) {
				$sAttr .= ' pattern="' . $this->_getHtmlPattern($this->_m_oRules->pattern) . '"';
				unset($this->_m_oAttributes->pattern);
			}			
		}
	
		return $sAttr;
	}	
}