<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 11/25/16
 * Time: 12:18 PM
 * phive('SQL')->insertArray('trans_log', array('user_id' => $uid, 'tag' => $tag, 'dump_txt' => var_export($var, true)));
 */
namespace App\Models;

use App\Extensions\Database\FModel;

class TransLog extends FModel
{
    public $timestamps = false;

    protected $table = 'trans_log';

    public $guarded = ['id'];

}
