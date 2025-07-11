<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 02/03/16
 * Time: 09:35
 */

namespace App\Helpers;
use App\Models\Trigger;

class TriggersHelper
{
    /**
     * Get Trigger list based on type AML - FRD - RG
     * @param type $trigger_type
     * @return mixed
     */
    public static function getTriggersList($trigger_type = '')
    {
        return Trigger::on('default')->where('name', 'like', '%' . $trigger_type. '%')->get()->sortBy('name', SORT_NATURAL);
    }
}