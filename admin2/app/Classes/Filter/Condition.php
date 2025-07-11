<?php
/**
 * Created by PhpStorm.
 * User: iondum
 * Date: 18/10/2017
 * Time: 11:42
 */

namespace App\Classes\Filter;

use App\Extensions\Database\FManager as DB;

class Condition
{
    private $key;
    private $comparator;
    private $value;
    private $addons;

    function __construct($arr)
    {
        $this->key = $arr[0];
        $this->comparator = $arr[1];
        $this->value = $arr[2];


        if (is_array($this->value))
        {
            $this->value = self::getComplexValue($this->value);
        }

        if(count($arr) > 3) {
            $this->addons = [];
        }
        for ($i = 3; $i < count($arr); $i++) {
            $this->addons[] = $arr[$i];
        }
    }

    public static function getComplexValue($value) {
        switch ($value[0])
        {
            case 'date':
                $value = $value[1];
                break;
            case 'today_plus':
                $value = "CURDATE() + INTERVAL {$value[1]} DAY";
                break;
            case 'today_minus':
                $value = "CURDATE() - INTERVAL {$value[1]} DAY";
                break;
            case 'none':
                $value = "none";
                break;
        }
        return $value;
    }

    public function has($key)
    {
        return $this->{$key} !== null;
    }

    /**
     * @param null $key
     * @return array|string
     */
    public function get($key=null)
    {
        if ($key)
        {
            return $this->{$key};
        }
        return [
            $this->key,
            $this->comparator,
            $this->value,
            $this->addons
        ];
    }
}