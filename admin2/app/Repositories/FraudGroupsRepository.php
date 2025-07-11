<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 18/02/2016
 * Time: 15:27
 */

namespace App\Repositories;

use App\Models\FraudGroup;
use App\Models\FraudRule;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Database\Capsule\Manager as DB;

class FraudGroupsRepository
{
    /** @var Application $app */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public static function getGroups()
    {
        return FraudGroup::orderBy('id', 'DESC')->get();
    }

    public static function getRules()
    {
        return FraudRule::orderBy('id', 'DESC')->get();
    }

}