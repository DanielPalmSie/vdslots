<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 4/20/16
 * Time: 6:33 PM
 */

use App\Extensions\Database\FManager as Capsule;

/**
 * @var \Silex\Application $app
 */
//This is needed for configuration credentials
require __DIR__.'/../bootstrap.php';


$app['phpmig.adapter'] = function (\Pimple\Container $app) {
    return new \App\Extensions\Database\MigrationAdapter($app['capsule'], 'migrations_backoffice');
};

$app['phpmig.migrations_path'] = function() {
    return __DIR__.'/../app/Database/Migrations';
};

//I can run this directly, because Capsule is set as globally
//with $capsule->setAsGlobal(); line at /bootstrap.php
$app['schema'] = function () {
    return Capsule::schema();
};

$app->register(new \App\Extensions\Database\ZeroDowntimeMigration\ServiceProvider());

return $app;