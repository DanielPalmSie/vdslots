<?php

// TODO henrik remove

require_once __DIR__ . '/PhMessage.php';
// Log class that will handle both logging from the Phive core
//  and the Phive modules. It is designed so that all message
//  entries are PhMessage objects

class PhLog
{
  // Filename with path included
  private $filename;
  
  // Is logging activated? (bool)
  private $activated = true;
  
  // Log messages sent
  private $messages = 0;
  
  // Log errors
  private $errors = array();
  
  // Last date outputted
  private $lastdate = null;
  
  // PHP Errors as strings
  private $errortype = array (
    E_WARNING            => '<p class="log_warning">Warning</p>',
    E_NOTICE             => '<p class="log_notice">Notice</p>',
    E_USER_ERROR         => '<p class="log_error">User Error</p>',
    E_USER_WARNING       => '<p class="log_warning">User Warning</p>',
    E_USER_NOTICE        => '<p class="log_notice">User Notice</p>',
    E_STRICT             => '<p class="log_notice">Runtime Notice</p>',
    E_RECOVERABLE_ERROR  => '<p class="log_error">Error</p>'
  );
  
  // Detailed errors
  private $detailed_types;
  
  public function __construct()
  {
    // Set error handling		
    $date = date('Y-m-d');
    $this->filename = __DIR__ . "/../log/log-$date.htm";
    $this->detailed_types =
    E_WARNING | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR;
  }
  
  static function prLog($msg){
    echo '<div style="position: absolute; top: 0; left: 0; background: #ccc; padding: 10px; border: 1px solid #000; z-index: 2000; color: #900; font-size: 14px; font-weight: bold;">'.$msg.'</div>';
  }
  
  // Check if file is writable
  public function testLog()
  {
    // Check if the directory is writable.
    return is_writable(dirname($this->filename));
  }
  
  protected function getHeader()
  {
    $filename = __DIR__ . '/../html/log_header.php';
    $handle = fopen($filename, "r");
    $contents = fread($handle, filesize($filename));
    fclose($handle);
    return $contents;
  }
  
  // Message to HTML, returned as a string
  protected function messageToHTML($errno, $errstr, $errfile, $errline, $backtrace=null)
  {
    $detailed = false;
    $str = "";
    if (($count = $this->messages++) == 0)
      $str .= "<hr />";
    
    $name = uniqid() . "_" . $count;
    
    // Check if there is additional info (the error string
    //  contains a "---")
    if (count($array = explode("---", $errstr)) > 1)
    {
      $errstr = $array[0];
      $additional_info = $array[1];
      $detailed = true;
    }
    
    // Detailed error
    $detailed = $detailed || $errno & $this->detailed_types;
    $errno_str = $this->errortype[$errno];		
    $date = $date_str = date("Y-m-d H:i:s");
    
    if ($date == $this->lastdate)
      $date_str = "";
    
    if ($detailed)
      $more_str = '<a id="button_'.$name.'" style="cursor: pointer" onclick="toggle_visibility(\''.$name.'\')">+</a>';
    else
      $more_str = "&nbsp;";
    
    $fileline = basename($errfile) . ':' . $errline;
    
    $str .= <<<EOT
<table><tr>
<td class="log_col_type">
	$errno_str
</td><td class="log_col_more">
	$more_str
</td><td class="log_col_text">
	$errstr <span class="gray">($fileline)</span>
</td><td class="log_col_time">
	$date_str
</td></tr></table>
EOT;
    if ($detailed)
    {
      $str .= '<div style="display: none" id="detailed_'.$name.'">';
      if ($additional_info != '')
      {
	$str .= <<<EOT
<table class="log_additional"><tr>
<td class="log_col_details">
	<pre>$additional_info</pre>
</td></tr></table>
EOT;
      }

      // Highlight the file and the line from where the trigger was called
      $backtrace = str_replace(
	$errfile.':'.$errline, 
	'<b>'.$errfile.'</b>:<b>'.$errline.'</b>', 
	$backtrace);
      
      $str .= <<<EOT
<table class="log_details"><tr>
<td class="log_col_details">
	<pre>$backtrace</pre>
</td></tr></table>

EOT;
      $str .= '</div>';
    }
    
    $this->lastdate = $date;

    return $str;
  }
  
  // Intercept PHP errors
  public function phpError($errno, $errstr, $errfile, $errline, $backtrace=null)
  {		
    // Write to file
    $writeheader = false;
    if (!file_exists($this->filename))
    {
      // Create file and write header
      $writeheader = true;
    }
    else
      if (!is_writable($this->filename))
	return;
    
    // Unfortunately we can't log these errors		
    if (!$handle = fopen($this->filename, 'a'))
      return;
    
    // Write header if applicable
    if ($writeheader)
    {
      fwrite($handle, $this->getHeader());
    }
    
    fwrite($handle, $this->messageToHTML($errno, $errstr, $errfile, $errline, $backtrace));
    fclose($handle);
    
    // Save in memory too, for output
    $this->errors[] = array(
      'type'=>$errno,
      'message'=>$errstr,
      'file'=>$errfile,
      'line'=>$errline);

    // Don't execute PHP internal error handler
    return true;
  }
  
  // Backtrace array to HTML output (in string).
  static public function backtraceToString($backtrace)
  {
    $str = "";
    $limit = 10;
    foreach ($backtrace as $row => $data)
    {
      if ($limit-- == 0)
	break;

      if ($row != 0)
	$str .= "\n";
      
      $str .= ($row+1) . '#  ';
      $str .= $data['function'];
      
      // The possiblity to expand the arguments,
      //  they are first hidden.
      if (!empty($data['args']))
      {
	$data_strs = array();
	$monkey = 0;
	foreach ($data['args'] as $arg)
	{
	  if ($arg===null)
	    $arg_str = 'null';
	  else
	    if (is_string($arg))
	      $arg_str = '"'.$arg.'"';
	  else
	    if (is_object($arg))
	      $arg_str = get_class($arg);
	  else
	    if (is_numeric($arg))
	      $arg_str = $arg;
	  else
	    if (is_array($arg))
	      $arg_str = "Array";
	  else
	    if (is_bool($arg))
	      $arg_str = ($arg?"true":"false");
	  else
	    $arg_str = "(type unknown)";
	  
	  array_push($data_strs, $arg_str);
	  $monkey++;
	}
	
	$list = str_replace("\\'", "'", implode(', ', $data_strs));
	$list = str_replace("'", "\\'", $list);
	$list = htmlentities($list, ENT_QUOTES);
	$list = str_replace("\n", "", $list);
	$list = str_replace("\r", "", $list);
	
	$str .= 
	'(<a onclick="this.innerHTML=\'' . 
	$list .
	'\';">...</a>)';
      }
      else
	$str .= '()';
      
      
      if ($data['file'])
	$str .= ' called at [' . $data['file'] . ':' . $data['line'] . ']';
    }
    return $str;
  }
  
  public function getDetailedTypes()
  {
    return $this->detailed_types;
  }
  
  public function explainType($type)
  {
    return $this->errortype[$type];
  }
  
  // Returns the name of the current log file
  public function getFilename()
  {
    return $this->filename;
  }
  
  public function getErrors()
  {
    return $this->errors;
  }
  
  public function clock($key){
    
    $cur_time = microtime(true);
    
    if(empty($this->cur_time))
      $this->cur_time = $cur_time;
    
    $diff = $cur_time - $this->cur_time;
    
    if(empty($key))
      $this->clocks[] = $diff;	
    else
      $this->clocks[$key] = $diff;
    
    $this->cur_time = $cur_time;
  }
  
  public function prClocks(){
    print_r($this->clocks);
  }
}

