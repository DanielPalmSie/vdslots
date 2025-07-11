<?php

namespace App\Classes\FormBuilder;

class FormBuilder {
	private $_m_oValidate = null;
	private $_m_oCollection = null;
	private $_m_aCollections = array ();
	private $_m_sCollection = 'default';
	private $_m_oElement = null;
	private $_m_sDefaultCollectionTemplate = 'Snippets/collection.html';	
	private $_m_sDefaultFormTemplate = 'Snippets/form.html';
	
	final public function __construct(validateInterface $p_oValidate = null, collectionInterface $p_oCollection = null) {
		$this->_m_oValidate = $p_oValidate;
		$this->_m_oCollection = $p_oCollection;
	}

	public function createInput(array $p_aOptions = array()) {
		$this->_create('input', $p_aOptions);
	}

	public function createSelect(array $p_aOptions = array()) {
		$this->_create('select', $p_aOptions);
	}

	public function createTextarea(array $p_aOptions = array()) {
		$this->_create('textarea', $p_aOptions);
	}

	public function createCheckbox(array $p_aOptions = array()) {
		$this->_createCheckItem('checkbox', $p_aOptions);
	}

	public function createRadio(array $p_aOptions = array()) {
		$this->_createCheckItem('radio', $p_aOptions);
	}
	
	public function createDatalist(array $p_aOptions = array()) {
		$this->_create('datalist', $p_aOptions);
	}
	
	public function createKeygen(array $p_aOptions = array()) {
		$this->_create('keygen', $p_aOptions);
	}
	
	public function createButton(array $p_aOptions = array()) {
		$this->_create('button', $p_aOptions);
	}

	/**
	 * @todo not implemented yet
	 * @param array $p_aOptions
	 */
	public function createOutput(array $p_aOptions = array()) {
		$this->_create('output', $p_aOptions);
	}
	
	public function addToCollection() {}

	/**
	 * Get all form fields
	 */
	public function getCollection($p_sByName = null) {
		// @todo order the form fields in this collection by param order
		if ($p_sByName !== null) {
			foreach ($this->_m_aCollections as $iOrder => $aCollection) {
				if (isset($aCollection[$p_sByName])) {
					return $aCollection[$p_sByName];
				}
			}
			return false;
		}
		return $this->_m_aCollections;
	}

	/**
	 * Get all form fields in the same collection
	 */
	public function getElement($p_sByName) {
		// @todo order the form fields in this collection by param order
	}

	public function createForm($p_aOptions) {
		$oForm = $this->_arrayToObject($p_aOptions);
		$html = $sAttr = '';
		$sTemplate = $this->_m_sDefaultCollectionTemplate;
		$sFormTemplate = $this->_m_sDefaultFormTemplate;
		
		if(isset($oForm->template) && is_file($oForm->template)){
			$sFormTemplate = $oForm->template;
			unset($oForm->template);
		}		
		
		foreach ($this->_m_aCollections as $iOrder => $aCollections) {
			foreach ($aCollections as $sCollection => $aElements) {
				$sElements = '';
				foreach ($aElements as $this->_m_oElement) {
					$sElements .= $this->_m_oElement->create() . PHP_EOL;
				}
				
				// check if custom collection template has to be applied
				if(isset($this->_m_aCollectionTemplates[$sCollection]) && file_exists($this->_m_aCollectionTemplates[$sCollection])){
					$sTemplate = $this->_m_aCollectionTemplates[$sCollection];
				}
				
				$html .= str_replace(array('{{collection}}','{{className}}'), array($sElements,$sCollection), file_get_contents(__DIR__ . '/' . $sTemplate));
				$html .= "<input type=\"hidden\" name=\"token\" value=\"".$_SESSION['token']."\">";
				//die($html);

			}
		}
		
		if(!isset($oForm->notag) || isset($oForm->notag) && $oForm->notag === false){
			if (!empty($oForm->attr)) {
				foreach ($oForm->attr as $key => $value) {
					if($key === 'enctype'){
						if(isset($oForm->attr->method) && $oForm->attr->method !== \App\Classes\FormBuilder\Elements\ElementInterface::FORM_POST){
							continue;
						}
					}
					$sAttr .= ' ' . $key . '="' . $value . '"';
				}
			}		
			$html = "<form$sAttr>" . $html . '</form>';
		}

		return str_replace(array('{{form}}'), $html, file_get_contents(__DIR__ . '/' . $sFormTemplate));
	}

	public function valid() {
		$aErrors = array ();
		if ($this->_m_oValidate instanceof validateInterface) {
			foreach ($this->_m_aElements as $collection => $element) {
				if (!$element->valid()) {
					$aErrors[] = $element->getErrors();
				}
			}
		}
		return (empty($aErrors) ? true : $aErrors);
	}

	private function _createCheckItem($p_sFormField, array $p_aOptions = array()){
		
		$aType = array('type' => (($p_sFormField === 'checkbox') ? \App\Classes\FormBuilder\Elements\ElementInterface::TYPE_CHECKBOX : \App\Classes\FormBuilder\Elements\ElementInterface::TYPE_RADIO));
		
		if(isset($p_aOptions['name'])){			
			// it's just one checkbox/radio
			$this->_create('input', array_merge($p_aOptions, $aType));		
		} elseif(isset($p_aOptions[0])){			
			// we have an array of checkboxes/radios
			foreach($p_aOptions as $aOptions){			
				$this->_create('input', array_merge($aOptions, $aType));
			}
			
		} else {
			throw new \Exception("The array for $p_sFormField is wrongly defined.");
		}		
	}
	
	private function _create($p_sFormField, array $p_aOptions = array()) {
	
		// transform the form field options into an object
		$oOptions = $this->_arrayToObject($p_aOptions);

		$p_sClassName = '\App\Classes\FormBuilder\Elements\\' . ucfirst($p_sFormField);
		
		if(class_exists($p_sClassName)){
			$this->_m_oElement = new $p_sClassName();			
		} else {
			throw new \Exception('Class ' . $p_sClassName . ' does not exist!');
		}
		$this->_m_oElement->setElementName($p_sFormField);
		
		// inject non required validation dependency
		$this->_m_oElement->injectDependency($this->_m_oValidate);
	
		// set the form field name and if missing throw an error
		$this->_setFormFieldName((!isset($oOptions->name) ? '' : $oOptions->name));
	
		// set the type attribute of the element. eg: email, text, password etc
		if (isset($oOptions->type)) {
			$this->_m_oElement->type($oOptions->type);
		} elseif($p_sFormField === 'input'){
			$this->_m_oElement->type();
		}
	
		// set the rules for the element
		if (isset($oOptions->rules)) {
			$this->_m_oElement->rules($oOptions->rules);
			unset($oOptions->rules);
		}
	
		// overrule the default template used for this element
		if (isset($oOptions->template) && is_string($oOptions->template)) {
			$this->_m_oElement->template($oOptions->template);
			unset($oOptions->template);
		}
	
		// set the value for this element
		if (isset($oOptions->value)) {
			$this->_m_oElement->value($oOptions->value);
			unset($oOptions->value);
		}
		
		// set the value for this element
		if (isset($oOptions->text)) {
			$this->_m_oElement->text($oOptions->text);
			unset($oOptions->text);
		}
		
		// set the comment for this element
		if (isset($oOptions->comment)) {
			$this->_m_oElement->comment($oOptions->comment);
			unset($oOptions->comment);
		}
		
		// set the options for this element
		if (isset($oOptions->options)) {
			
			$this->_m_oElement->options($oOptions->options);
			unset($oOptions->options);
		}
		
		// assign the element to a collection group
		if (isset($oOptions->collection) && is_object($oOptions->collection) && isset($oOptions->collection->name) && isset($oOptions->collection->order)) {
			$sCollection = preg_replace("/[^A-Za-z0-9]/", '', $oOptions->collection->name);
			$iOrder = (int) $oOptions->collection->order;			
			$this->_m_aCollectionTemplates[$sCollection] = (isset($oOptions->collection->template) ? $oOptions->collection->template : $this->_m_sDefaultCollectionTemplate);
			unset($oOptions->collection);
		} else {
			$iOrder = 0;
			$sCollection = $this->_m_sCollection;
		}
	
		// set other element attributes
		if (isset($oOptions->attr) && is_object($oOptions->attr)) {
			$this->_m_oElement->attr($oOptions->attr);
		}
	
		// set the label for this element
		if (isset($oOptions->label)) {
			if (is_string($oOptions->label)) {
				$this->_m_oElement->label($oOptions->label);
			} else if (is_object($oOptions->label)) {
				$sLabelText = $oOptions->label->text;
				unset($oOptions->label->text);
				$this->_m_oElement->label($sLabelText, (!empty($oOptions->label) ? $oOptions->label : array ()));
			}
			unset($oOptions->label);
		}
		$this->_m_aCollections[$iOrder][$sCollection][] = $this->_m_oElement;
	}
	
	private function _setFormFieldName($p_sName = ''){
		if (empty($p_sName)) {
			throw new \Exception('A form field name is empty!'); 
		}		
		// set the name attribute of the element
		$this->_m_oElement->name($p_sName);		
	}
		
	private function _arrayToObject(array $p_aOptions = array()){
		return json_decode(json_encode($p_aOptions), false);
	}
}
