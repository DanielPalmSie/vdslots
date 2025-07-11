<?php
// This function redirects PHP errors to 

function phiveErrorHandler($errno, $errstr, $errfile, $errline)
{
	// Return true will still suppress PHP errors.
	if (!phive()->getSetting("logging"))
		return true;
	
	// We'll do the backtrace here so that too many uninteresting
	//  steps aren't included.
	if ($errno & phive()->getLog()->getDetailedTypes())
	{
		$bt_array = debug_backtrace();
		$backtrace = phive()->getLog()->backtraceToString($bt_array);
	}
	else $backtrace = null;
	
    return phive()->getLog()->phpError($errno, $errstr, $errfile, $errline, $backtrace);
}