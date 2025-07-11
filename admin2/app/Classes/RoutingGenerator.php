<?php
/**
 * Created by PhpStorm.
 *
 * Simple routing generator, so once we migrate to another solution we can work still with this solution without
 * a huge refactoring.
 *
 * TODO finish this or discard
 * Ideas to improve this can be found here https://github.com/chili-labs/Silex-Routing
 *
 * User: ricardo
 * Date: 27/10/16
 * Time: 17:19 AM
 */

namespace App\Classes;

use Silex\Application;
use Silex\ControllerCollection;

class RoutingGenerator
{
    /** @var  Application $app */
    private $app;

    /** @var string $controller */
    private $controller;

    /** @var array $routes */
    private $routes = [];

    /**
     * RoutingGenerator constructor.
     *
     * @param Application $app
     * @param string $controller Controller full class name
     */
    public function __construct(Application $app, $controller)
    {
        $this->app = $app;
        $this->controller = get_class($controller);
    }

    /**
     * Adds a new route to the controller collection
     *
     * @param string $pattern
     * @param string $action
     * @param string $bind
     * @param array $permissions
     * @param string $method
     */
    public function add($pattern, $action, $bind, array $permissions, $method = 'GET|POST')
    {
        $this->routes[] = [
            'pattern' => $pattern,
            'action' => strpos($action, '::') === false ? "{$this->controller}::{$action}" : "App\\Controllers\\{$action}",
            'bind' => $bind,
            'permissions' => $permissions,
            'method' => $method
        ];
    }

    /**
     * @return ControllerCollection
     */
    public function load()
    {
        /** @var ControllerCollection $factory */
        $factory = $this->app['controllers_factory'];

        foreach ($this->routes as $route) {
            $factory->match($route['pattern'], $route['action'])
                ->bind($route['bind'])
                ->before(function () use ($route) {
                    foreach ($route['permissions'] as $permission) {
                        if (!p($permission)) {
                            $this->app->abort(403);
                        }
                    }
                })
                ->method($route['method']);
        }

        return $factory;
    }

    /* Route example
 		$factory->match('/user-form/', 'App\Controllers\UserController::userForm')
           ->bind('user')
           ->before(function () use ($app) {
                if (!p('users.section')) {
                   $app->abort(403);
               }
           });
        */


}