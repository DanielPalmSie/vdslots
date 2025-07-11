<?
abstract class Entry
{
	private $alias;
	
	// Form name, should be set automatically by the form class
	private $form;
	
	// Already entered values if form is not entirely valid
	//  (most entries have only one value, but the possibility of several still exists)
	private $values;
	
	// Array with settings
	// Settings that are used by the base class
	// - default
	// - mandatory
	// - name (description)
	// - note (sub description, written smaller)
	private $settings;
	
	// To be filled by Form if the value is not valid.
	private $error;
	
	// $id is a string
	// $settings is an array of type map.
	public function __construct($alias, $settings=null)
	{
		$this->alias = $alias;
		$this->settings = $settings;
	}
	
	// Overload this function to include extra values
	public function getExtraValues()
	{
		return null;
	}

 	// That $entry is of type Entry is checked
	//  before this function is called.
	abstract public function output();

	// Overwrite to make hidden, not rendered
	public function isRendered()
	{
		return true;
	}
	
	// setValue() needs to be called before this.
	// Entries with several variables need to overload this function
	//  and specify if not all variables are suppose to be mandatory
	public function isOK(&$error_msg)
	{
		// Set the error too
		$error_msg = "Field is mandatory";
		return (trim($this->getValue()) != '');
	}

	// Gets and sets
	final public function setForm($form) { $this->form = $form; }
	final public function getForm() { return $this->form; }
	final public function setValue($value_name=null, $value)
	{
		if ($value_name === null)
			$value_name = $this->alias;
		$this->values[$value_name] = $value;
	}
	
	final public function getValue($value_name=null) 
	{
		if ($value_name === null)
			$vn = $this->alias;
			
		$default_str = 'default' . (($value_name!==null)?('_' . $value_name):'');
		
		if (isset($this->values[$vn])) 
			return $this->values[$vn];
		else
		if ($this->getSetting($default_str) !== null)
			return $this->getSetting($default_str);
		else
			return null;
	}
	
	final public function getAllValues()
	{
		return $this->values;
	}
	
	public function getTagName($alias=null)
	{
		return get_class($this) . '|' . 
			$this->getForm()->getName() . '|' . 
			($alias===null?$this->alias:$alias);
	}
	
	public function getAllTagNames()
	{
		$ret = array($this->getTagName());
		$extra = $this->getExtraValues();
		if (is_array($extra))
		{
			foreach ($extra as $extra_alias)
			{
				$ret[] = $this->getTagName($this->alias . '_' . $extra_alias);
			}
		}
		return $ret;
	}

	public function getAlias() 
	{ 
		return $this->alias;
	}
	
	final public function setError($error) { return $this->error = $error; }
	final public function getError() { return $this->error; }

	final public function getSetting($setting)
	{
		if (!is_array($this->settings))
			return null;
		else
		if (!isset($this->settings[$setting]))
			return null;
		else
			return $this->settings[$setting];
	}
	
	// Get phive
	final public function getPhive() 
	{ 
		if ($this->form)
			return $this->form->getPhive(); 
		else
			return null;
	}
}
?>