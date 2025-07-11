<?
require_once __DIR__ . '/../../api/PhModule.php';
require_once __DIR__ . '/Entry.php';

require_once __DIR__ . '/FormRenderer.php';
include_once __DIR__ . '/FormRendererRows.php';

require_once __DIR__ . '/Form.php';

// Could be automated (possibly through Phive setup generation)
require_once __DIR__ . '/EntrySubmit.php'; // required!

include_once __DIR__ . '/EntryText.php';
include_once __DIR__ . '/EntryLocalizable.php';
include_once __DIR__ . '/EntryInput.php';
include_once __DIR__ . '/EntryTextarea.php';
include_once __DIR__ . '/EntryList.php';
include_once __DIR__ . '/EntryHidden.php';
include_once __DIR__ . '/EntryBoolean.php';
include_once __DIR__ . '/EntryLink.php';

include_once __DIR__ . '/FormerCommon.php';

// Simple form class principally for admin interfaces.
class Former extends PhModule
{
  private $forms;
  
  private $submitted;
  private $submitted_form;
  
  private $error_message;
  
  public function __construct()
  {
    $this->forms = array();
  }
  
  public function reset()
  {
    $this->__construct();
  }
  
  function fc(){
    return new FormerCommon();
  }
  
  public function addForms($forms){
    $forms = is_array($forms) ? $forms : func_get_args(); 
    foreach ($forms as $form){
      if ($form instanceof Form){
	$form->setName($form->getName());
	$form->setPhive(phive());
	$this->forms[$form->getName()] = $form;
      }
      else
	trigger_error("All parameters of addForms() must be of type Form.", E_WARNING);
    }
  }
  
  public function handleResponse()
  {
    $array = null;
    foreach ($_POST as $key=>$post)
    {
      $array = explode('|', $key);
      if ($array[0] === 'EntrySubmit')
	break;
    }
    
    if ($array !== null)
    {
      $this->submitted = $array[2];
      $this->submitted_form = $array[1];

      if (!$this->submitted || !$this->submitted_form)
	return null; // It probably isn't a Former form, which is okay, they should be able to coexist.
      
      if (!($this->forms[$this->submitted_form] instanceof Form))
	;//trigger_error("\"$this->submitted_form\" is not a Form class object (probably null)", E_USER_ERROR);
      else
	return $this->forms[$this->submitted_form]->handleResponse();
    }
    return null;
  }
  
  public function output($falias=null)
  {
    if ($falias===null)
    {
      foreach ($this->forms as $form)
      {
	// recursive
	$this->output($form->getName());
      }
      return;
    }
    
    if (!isset($this->forms[$falias]))
    {
      trigger_error("Former::output(), the form alias ($falias) is not found", E_USER_ERROR);
      return null;
    }
    else
      if (!($this->forms[$falias] instanceof Form))
    {
      trigger_error("Added form is not type Form, but type " . get_class($this->forms[$falias]), E_USER_ERROR);
      return null;
    }
    else
      return $this->forms[$falias]->output();		
  }
  
  public function execute()
  {	
   $this->handleResponse();
   $this->output();
   }
  
  public function submitted()
  {
    return $this->submitted;
  }
  
  public function submittedForm()
  {
    return $this->submitted_form;
  }
  
  // Tunneling function to the active form
  public function getValue($alias)
  {
    return $this->forms[$this->submitted_form]->getValue($alias);
  }
  
  public function getArray($includesubmits = false)
  {
    return $this->forms[$this->submitted_form]->getArray($includesubmits);
  }
}
