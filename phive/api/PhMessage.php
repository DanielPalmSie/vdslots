<?
define("PHM_OK",				1);	
define("PHM_WARNING", 			2);
define("PHM_ERROR", 			4);
define("PHM_NOT_CONFIGURED", 	8);

// This definies if errors are fatal or not.
define("PHM_SIMPLE_OK", PHM_OK | PHM_WARNING);
define("PHM_SIMPLE_FATAL", PHM_ERROR | PHM_NOT_CONFIGURED);

class PhMessage
{
	private $message;
	private $type;
	
	/**
	 * Creates a message object of specified type with message string.
	 *
	 * @param Int 		$_type		Message type
	 * @param String 	$_message	Message string
	 */
	public function __construct($_type, $_message = null)
	{
		$this->type		= $_type;
		$this->message	= $_message;
	}

	/**
	 * Retrieve the message
	 *
	 * @return String	Message, null if none set
	 */
	public function getMessage()
	{
		if ($this->getType() === PHM_NOT_CONFIGURED &&
			!$this->message)
			return "This device is not configured.";
		
		return $this->message;
	}
	
	/**
	 * Returns whether message contains text
	 */
	public function hasText()
	{
		return !($this->getMessage() == '' && $this->getMessage() == null);
	}
	
	/**
	 * Message type
	 *
	 * @return Int		Messagetype
	 */
	public function getType() 
	{
		return $this->type;
	}
}

?>