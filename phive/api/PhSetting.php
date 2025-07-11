<?php

// TODO henrik remove this whole class.

// This represents a setting that a module requests, the
//  actual settings are then stored in simple arrays in PHP.
// So this class is not the actual setting, but the prototype.

// Types of data
define("PHTYPE_INTEGER", 	1);
define("PHTYPE_NUMBER", 	2);
define("PHTYPE_STRING", 	4);
define("PHTYPE_BOOL",		8);

class PhSetting
{
	// The data type of the setting
	private $type;
	
	// The name and the hard-coded default (optional).
	private $name;
	private $default;
	
	// Description
	private $description;
	
	public function __construct($_type, $_name, $_default=null, $_desc=null)
	{
		$this->type = $_type;
		$this->name = $_name;
		$this->default = $_default;
		$this->description = $_desc;
	}
	
	// Generates a html tag for inputting the setting
	// If a value is supplied, it will fill the input
	//  box, otherwise the default will be used.
	public function getHtmlTag($value=null)
	{
		switch ($type=$this->getType())
		{
			
			case PHTYPE_BOOL:
				$sel = ($value == true || ($value === null && $this->getDefault() == true));
			
				echo '<select name="' . $this->getTagName() . '">';			
				echo '<option value="true" ';
				if ($sel)
					echo "selected";
				echo ">True</option>";

				echo '<option value="false" ';
				if (!$sel)
					echo "selected";
				echo ">False</option>";
				
				echo "</select>";
				
				break;
			default:
				echo '<input size="30" name="' . $this->getTagName() . '"';
				if ($value)
					echo ' value="' . htmlspecialchars($value, ENT_QUOTES) . '"';
				else
				if ($this->getDefault())
					echo ' value="' . htmlspecialchars($this->getDefault(), ENT_QUOTES) . '"';
				
				echo ' />';
				break;
		}
	}
	
	// The tag names are formated like "<type>--<name>"
	//  so that the page intercepting the information
	//  knows what typ it is.
	public function getTagName()
	{
		return $this->getType() . '--' . $this->getName();
	}
	
	// Get functions
	public function getType() { return $this->type; }
	public function getName() { return $this->name; }
	public function getDefault() { return $this->default; }
	public function getDescription() { return $this->description; }
	
	// *** Static functions ***
	// The following are static functions because they are
	//  not meant to manipulate a PhSetting but an actual
	//  setting, read more about the difference in the
	//  top of this file.
	
	// Changes the value to a PHP-recognizable syntax.
	static public function encodePHPValue($type, $value)
	{
		switch ($type)
		{
			case PHTYPE_BOOL:
				return $value; // already string
			case PHTYPE_INTEGER:
			case PHTYPE_NUMBER:
				return $value;
			
			case PHTYPE_STRING:	
				// Like add slashes but without ' => \'
				$value = str_replace("\\", "\\\\", $value);
				$value = str_replace("\"", "\\"."\"", $value);		

				// fall through
			default:
				return '"' . $value . '"';
		}
	}
	
	static public function validate($type, $value)
	{
		switch($type)
		{
			case PHTYPE_INTEGER:
				if (is_numeric($value) && 
				 	(is_int($value) || 
					(is_string($value) && preg_match('/^[+-]?[0-9]+$/', $value))))
					return new PhMessage(PHM_OK);
				else
					return new PhMessage(PHM_ERROR, "Value is not a valid integer.");
			
			case PHTYPE_NUMBER:
				if (is_numeric($value))
					return new PhMessage(PHM_OK);
				else
					return new PhMessage(PHM_ERROR, "Value is not a valid number.");

			case PHTYPE_BOOL:
				if (strtolower($value) == 'true' || strtolower($value) == 'false')
					return new PhMessage(PHM_OK);
				else
					return new PhMessage(PHM_ERROR, "Value is not a valid boolean.");
					
			default:
				return new PhMessage(PHM_OK);
		}
	}
	
	static public function strtobool($string) {
		switch (strtolower($string)) {
			case 'true':
			case 't':
			case '1':
			case 1:
				return true;
			case 'false':
			case 'f':
			case '0':
			case 0:
				return false;
			default:
				return null;
		}	
	}
	
	static public function booltostr($bool) {
		if ($bool === true) {
			return 'true';
		} elseif ($bool === false) {
			return 'false';
		}
		return null;
	}
}
