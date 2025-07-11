<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.16.
 * Time: 16:36
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class BankCountry extends FModel
{
    public $incrementing = false;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $primaryKey = 'iso';

}
