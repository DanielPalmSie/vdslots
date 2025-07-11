<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 18/02/2016
 * Time: 15:27
 */

namespace App\Repositories;

use App\Models\Race;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Database\Capsule\Manager as DB;

class RacesRepository
{
    /** @var Application $app */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public static function getRaces($search='', $start=0, $length=50)
    {
        $races = Race::orderBy('id', 'DESC');
        $races_count = Race::count();

        if ($search != '') {
            //$games->where('game_name', 'LIKE', '%'.$name.'%');
        }

        $filtered_count = $races->count();

        return [
            'data' => $races->skip($start)->take($length)->get(),
            'recordsTotal' => $races_count,
            'recordsFiltered' => $filtered_count
        ];
    }

}