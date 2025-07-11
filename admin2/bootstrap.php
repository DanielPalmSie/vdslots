<?php

use App\Controllers\PoolX\BetsController;
use App\Providers\AppServiceProvider;
use App\Providers\ConfigServiceProvider;
use App\Services\PoolX\BetService as PoolXBetService;
use Dotenv\Dotenv;
use App\Controllers\Altenar\BetsController as AltenarBetsController;
use App\Services\Altenar\BetService as AltenarBetService;
use App\Controllers\AwardUploadController;
use App\Controllers\Bingo\BetsController as BingoBetsController;
use App\Services\Bingo\BetService as BingoBetService;

define('BASE_DIR', __DIR__);

if (in_array('do_db=archive', $argv)) {
    $_SERVER['only_archive_databases'] = true;

    $argv = $_SERVER['argv'] = array_filter($argv, static function($arg) {
        return $arg !== 'do_db=archive';
    });
}

try {
    if (class_exists(Dotenv::class)) {
        $dotEnv = Dotenv::createUnsafeImmutable(__DIR__);
        $dotEnv->load();
    } else {
        throw new Exception('Dotenv not installed. Please run composer install.');
    }
} catch (Exception $e) {
    error_log('WARNING: ' . 'Dotenv failed to load: ' . $e->getMessage());
}

if (!function_exists('isOnDisabledNode')) {
    function isOnDisabledNode($node): bool
    {
        $disabled_node = $_ENV['APP_DB_DISABLED_NODE'];
        if (is_null($disabled_node) || $disabled_node === '') {
            return false;
        }
        return (int)$disabled_node === ($node % 10);
    }
}

$views = getenv('VIEW_PATH');
$cache = getenv('VIEW_CACHE_PATH');
$root_url = getenv('BO_BASE_URL');

$app = new Silex\Application();

if (in_array(getenv('APP_ENV'), ['dev', 'test', 'prod', 'uat'])) {
    $app['env'] = getenv('APP_ENV');
} else {
    $app['env'] = 'prod';
    $app['err'] = 'Environment setup not supported';
}
$app['debug'] = getenv('APP_DEBUG') == 'true';
$app['slow-queries'] = getenv('APP_SLOW_QUERIES') == 'true';
$app['blade'] = new \App\Extensions\Blade\Blade($views, $cache);

$app->register(new Silex\Provider\RoutingServiceProvider());
$app->register(new \App\Providers\SessionServiceProvider());
$app['flash'] = $app['session']->getFlashBag();

$app->register(new ConfigServiceProvider(__DIR__."/config/{$app['env']}.php"));
$app->register(new ConfigServiceProvider(__DIR__."/config/menu.php"));
$app->register(new ConfigServiceProvider(__DIR__."/config/grs.php"));
$app->register(new ConfigServiceProvider(__DIR__."/config/rg-evaluation.php"));
$app->register(new ConfigServiceProvider(__DIR__."/config/triggers.php"));
$app->register(new ConfigServiceProvider(__DIR__."/config/notifications.php"));
if (file_exists(__DIR__."/config/local.php")) {
    $app->register(new ConfigServiceProvider(__DIR__."/config/local.php"));
}

if (file_exists(__DIR__."/config/aml.php")) {
    $app->register(new ConfigServiceProvider(__DIR__."/config/aml.php"));
}
$app['api.key'] = $app['env'] === 'prod' ? getenv('BO_API_KEY') : $app['vs.api']['key'];

if ($_SERVER['only_archive_databases']) {
    $master = [
        'host' => getenv('DB_ARCHIVE_HOST'),
        'database' => getenv('DB_ARCHIVE_DATABASE'),
        'username' => getenv('DB_ARCHIVE_USERNAME'),
        'password' => getenv('DB_ARCHIVE_PASSWORD'),
        'is_node' => false
    ];
    $shards = (isset($app['vs.db']) && isset($app['vs.db']['shards_archive']) ? $app['vs.db']['shards_archive'] : []);
} else {
    $master = [
        'host' => getenv('DB_HOST'),
        'database' => getenv('DB_DATABASE'),
        'username' => getenv('DB_USERNAME'),
        'password' => getenv('DB_PASSWORD'),
        'is_node' => false
    ];
    $shards = (isset($app['vs.db']) && isset($app['vs.db']['shards']) ? $app['vs.db']['shards'] : []);
}

// Master connections must be always a string and not a number, as is_numeric must return true to set up as node
$app->register(new \App\Providers\DatabaseServiceProvider(), [
    'capsule.connections' => array_merge([
        'default' => $master,
        'videoslots_archived' => [
            'host' => getenv('DB_ARCHIVE_HOST'),
            'database' => getenv('DB_ARCHIVE_DATABASE'),
            'username' => getenv('DB_ARCHIVE_USERNAME'),
            'password' => getenv('DB_ARCHIVE_PASSWORD'),
            'is_node' => false,
            'is_archive' => true
        ],
        'replica' => getenv('DB_REPLICA_HOST') !== false ? [
            'host' => getenv('DB_REPLICA_HOST'),
            'database' => getenv('DB_REPLICA_DATABASE'),
            'username' => getenv('DB_REPLICA_USERNAME'),
            'password' => getenv('DB_REPLICA_PASSWORD'),
            'is_node' => false
        ] : false,
        'nodes' => $shards,
        'replica_nodes' => isset($app['vs.db']) && isset($app['vs.db']['shards_replica']) ? $app['vs.db']['shards_replica'] : [],
        'archive_nodes' => isset($app['vs.db']) && isset($app['vs.db']['shards_archive']) ? $app['vs.db']['shards_archive'] : []
    ],[]),
    'capsule.masters' => ['default', 'videoslots_archived', 'replica'],
    'capsule.vs.db' => isset($app['vs.db']) ? $app['vs.db'] : [],
    'capsule.enable.log.all' => $app['debug'] || $app['slow-queries'] ? true : false
]);

$app['capsule'];

$app->register(new \Silex\Provider\SwiftmailerServiceProvider(), array(
    'swiftmailer.options' => [
        'transport' => getenv('MAIL_TRANSPORT'),
        'host' => getenv('MAIL_HOST'),
        'port' => getenv('MAIL_PORT'),
        'encryption' => getenv('MAIL_ENCRYPTION'),
        'username' => getenv('MAIL_USERNAME'),
        'password' => getenv('MAIL_PASSWORD')
    ]
));

/*$app->register(new Predis\Silex\ClientServiceProvider('redis'), [ Redis support disabled for now
    'redis.parameters' => 'tcp://127.0.0.1:6379',
    'redis.options'    => [
        'prefix'  => 'silex:',
        'profile' => '3.0',
    ],
]);
*/

if ($app['debug']) {
    if (class_exists('DebugBar\DebugBar')) {
        $app->register(new \App\Providers\DebugBarServiceProvider(), [
            'debugbar.path' => '/phive/admin/debugbar/',
            'debugbar.assets.dir' => '/var/www/admin2/phive_admin/debugbar/'
        ]);
    }
    if (class_exists('Sorien\Provider\PimpleDumpProvider')) {
        $app->register(new \Sorien\Provider\PimpleDumpProvider(), [
            'pimpledump.output_dir' => __DIR__
        ]);
    }
}

$storage_path = getenv('STORAGE_PATH_LOGS') ?: getenv('STORAGE_PATH');
$storage_path_logs = rtrim($storage_path, '/\\') . "/admin2.{$app['env']}.log";

$app->register(new App\Providers\MonologServiceProvider(), [
    'monolog.name' => 'VSAdmin2',
    'monolog.level' => Silex\Provider\MonologServiceProvider::translateLevel(getenv('APP_LOG_LEVEL') ?: 'WARNING'),
]);
$app->extend('monolog', function ($monolog, $app) use ($storage_path_logs) {
    $monolog->pushHandler(
        new \Monolog\Handler\RotatingFileHandler($storage_path_logs, getenv('LOG_ROTATION_FILES') ?: 0,
            Silex\Provider\MonologServiceProvider::translateLevel(getenv('APP_LOG_LEVEL') ?: 'WARNING'),
            true,
            0666)
    );
    return $monolog;
});

$app->register(new App\Providers\ServiceControllerServiceProvider());
$app->register(new App\Providers\EventServiceProvider());

$app['risk_profile_rating.repository'] = function() use ($app) {
    return new \App\Repositories\RiskProfileRatingRepository($app);
};

$app["risk_profile_rating.controller"] = function() use ($app) {
    return new \App\Controllers\Api\RiskProfileRatingController(
        $app['risk_profile_rating.repository']
    );
};

$app['sportsbook.repository'] = function () use ($app) {
    return new \App\Repositories\SportsbookRepository($app);
};

$app['sportsbook_clean_event_service'] = function () use ($app) {
    return new \App\Services\Sportsbook\CleanEventService($app, getenv('USER_SERVICE_SPORT_URL'));
};

$app['sportsbook_bet_settlement_service'] = function () use ($app) {
    return new \App\Services\Sportsbook\BetSettlementReportService($app, getenv('USER_SERVICE_SPORT_URL'));
};

$app['sportsbook_manual_bet_settlement_service'] = function () use ($app) {
    return new \App\Services\Sportsbook\ManualBetSettlementService($app, getenv('USER_SERVICE_SPORT_URL'));
};

$app['payments_method_submethod_filter_service'] = function () use ($app) {
    return new \App\Services\Payments\MethodAndSubMethodFiltersService($app);
};

$app['poolx.bet_service'] = function () use ($app) {
    return new PoolXBetService($app);
};

$app['poolx.bets_controller'] = function () use ($app) {
    return new BetsController($app, $app['poolx.bet_service']);
};

$app['altenar.bet_service'] = function () use ($app) {
    return new AltenarBetService($app);
};

$app['altenar.bets_controller'] = function () use ($app) {
    return new AltenarBetsController($app, $app['altenar.bet_service']);
};

$app['bingo.bet_service'] = function () use ($app) {
    return new BingoBetService($app);
};

$app['bingo.bets_controller'] = function () use ($app) {
    return new BingoBetsController($app, $app['bingo.bet_service']);
};

$app->register(new AppServiceProvider());

$app->mount(
    '/admin2/gamification',
    new AwardUploadController()
);
