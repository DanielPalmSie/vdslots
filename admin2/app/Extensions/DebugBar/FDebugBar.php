<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 4/21/17
 * Time: 12:07 PM
 */
namespace App\Extensions\DebugBar;

use DebugBar\DataCollector\ExceptionsCollector;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DataCollector\RequestDataCollector;
use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\DebugBar;

/**
 * Debug bar subclass which adds all included collectors
 */
class FDebugBar extends DebugBar
{
    public function __construct()
    {
        $this->addCollector(new PhpInfoCollector());
        $this->addCollector(new MessagesCollector());
        $this->addCollector(new RequestDataCollector());
        //$this->addCollector(new TimeDataCollector());
        $this->addCollector(new MemoryCollector());
        $this->addCollector(new ExceptionsCollector());

        $this->addCollector(new DatabaseCollector());
    }
}