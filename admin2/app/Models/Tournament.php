<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.16.
 * Time: 16:36
 */

namespace App\Models;

use App\Extensions\Database\FModel;
use App\Extensions\Database\FManager as DB;

class Tournament extends FModel
{
    public $timestamps = false;

    protected $table = 'tournaments';

    protected $primaryKey = 'id';

    protected $fillable = [];

    public static function getColumnsData()
    {
        $instance = new static;
        $column_data = DB::select('SHOW COLUMNS FROM ' . $instance->getTable());
        $adapted_column_data = [];

        foreach ($column_data as $value) {
            $type_simplified = "text";

            $pos_int = stripos($value->Type, 'int');
            if ($pos_int !== false) {
                $type_simplified = 'number';
            }

            $pos_date = stripos($value->Type, 'date');
            if ($pos_date !== false) {
                $type_simplified = 'date';
            }

            $adapted_column_data[$value->Field] = ['type' => $value->Type, 'type_simple' => $type_simplified, 'NULL' => $value->Null == "NO", 'default' => $value->Default];
        }

        return $adapted_column_data;
    }

}
