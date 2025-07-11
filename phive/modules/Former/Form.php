<?
// Representing one html form, handled by Former
class Form
{
	// FormRenderer
	private $renderer;
	
	// Array of Entry objects
	//  Array constructed in two layers, first a map of all submit_aliases,
	//  then the array containing the entries.
	private $entries;

	// Number of errors that occured
	private $num_errors;
	
	// The submit buttons and their names (not their captions)
	private $submit_buttons;
	
	// submit name (this enables us to switch between several forms on the same page)
	private $form_alias;
	
	// phive pointer
	private $pPhive = null;

	function __construct($falias=null, $renderer=null)
	{
		$this->form_alias = $falias;
		$this->entries = array();
		$this->renderer = ($renderer===null)?(new FormRenderer()):$renderer;
		$this->submitted = false;
		$this->num_errors = 0;
		$this->submit_buttons = array();
	} 

	// Install
	public function addEntries(){
		foreach (func_get_args() as $entry){
			if ($entry instanceof Entry){
				// Set form
				$entry->setForm($this);
				
				if ($entry instanceof EntrySubmit)
					$this->submit_buttons[] = $entry->getAlias();

				$this->entries[$entry->getAlias()] = $entry;
			}
		}
	}

	public function output()
	{
		if (!is_array($this->entries) || 
			empty($this->entries))
			return;
			
		$this->renderer->output($this);
	}

	// reset
	public function reset($falias=null)
	{
		self::__construct($falias);
	}

	// This function handles the given POST or GET data
	// Returns null if not submitted, false if 
	public function handleResponse()
	{
		$this->num_errors = 0;

		foreach ($this->getEntries() as $entry)
		{
			$value_names = $entry->getAllTagNames();
			foreach ($value_names as $value_name)
			{
				$post = $this->getPost($value_name);
				
				if ($post!==null)
				{
					$exploded = explode('|', $value_name);
					$value = $post;
			
					// Store the value
					$entry->setValue($exploded[2], $value);
			
					if ($entry->getSetting('mandatory') &&
						!$entry->isOK($msg))
					{
						$entry->setError($msg);
						++$this->num_errors;
					}
					else
					// Additional validation
					if (($func=$entry->getSetting('validation')) !== null){
						$post 	= $this->getPostStripped();
						$class 	= $entry->getSetting('class');
						if($class !== null){
							$obj = new $class;
							$msg = call_user_func_array(array($obj, $func), array($post));
						}else
							$msg = $func($post);
							
						if (!($msg instanceof PhMessage))
							trigger_error("Function 'validation' used in a form entry and does not return a PhMessage", E_USER_WARNING);
						else
						if ($msg->getType() & PHM_SIMPLE_FATAL)
						{
							$entry->setError($msg->getMessage());
							++$this->num_errors;
						}
					}
				}
				
			}
		}
		return ($this->getNumErrors() == 0);
	}
	
	public function getPostStripped()
	{
		$post = array();
		foreach ($_POST as $key=>$value)
		{
			$array = explode('|', $key);
			$post[$array[2]] = $value;
		}
		return $post;
	}
	
	// This function handles the response and then outputs
	//  the form
	public function execute()
	{
		$ret = $this->handleResponse();
		$this->output();
		
		return $ret;
	}
	
	// Get entries
	public function getEntries()
	{
		return $this->entries;
	}
	
	// Get a specific entry
	public function getEntry($alias)
	{
		return $this->entries[$alias];
	}
	
	// Get value (without having to retrieve entries)
	public function getValue($alias)
	{
		if ($this->entries[$alias] instanceof Entry)
			return $this->entries[$alias]->getValue();
		else
			return null;
	}
	
	// Get and set current form
	public function setName($falias)
	{
		$this->form_alias = $falias;
	}
	
	public function getName()
	{
		if ($this->form_alias === null)
			return 'default_form';
		else
			return $this->form_alias;
	}
	
	// Get values as array
	public function getArray($includesubmits = false)
	{
		$ret = array();
		foreach ($this->entries as $entry)
		{
			if (!(!$includesubmits &&
				get_class($entry) == "EntrySubmit"))
			{
				$array = $entry->getAllValues();
				if (is_array($array))
					$ret = array_merge($ret, $array);
			}
		}
		return $ret;
	}
	
	// Get number of errors
	public function getNumErrors()
	{
		return $this->num_errors;
	}
	
	// Get OK (i.e. submitted and no errors)
	public function isOK()
	{
		return ($this->submitted && $this->num_errors == 0);
	}
	
	// has been submitted
	public function submitted()
	{
		return $this->submitted;
	}
	
	// Get post
	public function getPost($str)
	{
		if (isset($_POST[$str]))
			return $_POST[$str];
		else
			return null;
	}
	
	// Checks if this form has this submit button
	public function hasSubmit($str)
	{
		return in_array($str, $this->submit_buttons);
	}
	
	// Get / Set phive
	public function setPhive($phive)
	{
		$this->pPhive = $phive;
	}
	
	public function getPhive()
	{
		return phive();
	}
}

?>
