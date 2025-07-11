<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 4/21/17
 * Time: 11:49 AM
 */
namespace App\Extensions\DebugBar;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

class DatabaseCollector extends DataCollector implements Renderable
{
    public function collect()
    {
        return [['key1' ,'val1', 'key2', 'val2'], ['key1', 'val1', 'key2', 'val2']];
    }

    public function getName()
    {
        return 'queries';
    }

    public function getWidgets()
    {
        return [
            "queries" => [
                "icon" => "inbox",
                "widget" => "PhpDebugBar.Widgets.ListWidget",
                //"widget" => "PhpDebugBar.Widgets.VariablesListWidget",
                "map" => "queries",
                "default" => "[]"
            ]
        ];
    }
}
