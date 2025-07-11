<?php

use App\Extensions\Database\FManager as Capsule;
use App\Extensions\Database\Seeder\SeederAdapter;

//This is needed for configuration credentials
require __DIR__.'/../bootstrap.php';


$app['phpmig.adapter'] = function (\Pimple\Container $app) {
    return new SeederAdapter($app['capsule'], getenv('DATABASE_SEEDERS_TABLE') ?: 'seeders_backoffice');
};


$app['phpmig.migrations_path'] = function() {
    return __DIR__.'/../app/Database/Seeders';
};

$app['phpmig.migrations_template_path'] = function() {
    return __DIR__ . '/../app/Extensions/Database/Seeder/SeederTemplate.php';
};

//I can run this directly, because Capsule is set as globally
//with $capsule->setAsGlobal(); line at /bootstrap.php
$app['schema'] = function () {
    return Capsule::schema();
};

return $app;