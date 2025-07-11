<?php
require_once __DIR__ . '/../vendor/autoload.php';
include_once __DIR__ . '/../api/Phive.base.php';
require_once __DIR__ . '/../api/BrandedConfig.php';


$phive_location = __DIR__ . '/../';


/**
 * Loading Dotenv here to ensure that .env is updated even when phive is broken
 */
try {
    if (class_exists(\Dotenv\Dotenv::class)) {
        if (! method_exists(\Dotenv\Dotenv::class, 'createUnsafeImmutable')) {
            $dotEnv = new \Dotenv\Dotenv($phive_location);
            $dotEnv->load();
        } else {
            $dotEnv = \Dotenv\Dotenv::createUnsafeImmutable($phive_location);
            $dotEnv->load();
        }
    } else {
        throw new Exception('Dotenv not installed. Please run composer install.');
    }
} catch (Exception $e) {
    error_log('WARNING: ' . 'Dotenv failed to load: ' . $e->getMessage());
}


function exitWithMessage($message, $type = 'WARNING: ')
{
    echo "$type" . $message . "\n";
    exit(0);
}



/**
 * Exit if not in cli mode
 * @see isCli()
 */
if (!empty($_SERVER['REMOTE_ADDR']) || php_sapi_name() !== 'cli') {
    exitWithMessage('Not in cli mode');
}


if (!$_ENV['CONFIG_CACHING']) {
    exitWithMessage('Caching is not enabled in config file');
}


$configDirectory = $_ENV['CONFIG_DIR'];
$cachingDirectory = $_ENV['CONFIG_CACHE_DIR'];
$cachingDirectoryPath = "$phive_location$configDirectory/$cachingDirectory";

if (!$cachingDirectory){
    exitWithMessage('Caching directory is not specified in config');
}


phive()->addModule(new BrandedConfig(), 'BrandedConfig');
phive('BrandedConfig')->bootstrap($phive_location);


//Getting config data in a same way as it was designed by architects
$config = phive('BrandedConfig')->getNewConfigData();

if (!is_array($config) || count($config) === 0){
    exitWithMessage("Can't read config data");
}


if (!file_exists($cachingDirectoryPath)){
    if (!mkdir($cachingDirectoryPath)){
        exitWithMessage("Can't create cache directory");
    }
}


//removing all previously generated cache files
$files = scandir($cachingDirectoryPath); // get all file names

foreach($files as $file){ // iterate files
    if(is_file($cachingDirectoryPath."/$file")) {
        unlink($cachingDirectoryPath."/$file"); // delete file
    }
}



$cacheTime = $config['CACHE_TIME'] = time();
$cacheFilePath = $cachingDirectoryPath."/$cacheTime-config.json";
$fileCreated = file_put_contents($cacheFilePath, json_encode($config));

if (!$fileCreated){
    exitWithMessage("Can't create static config file");
} else {
    exitWithMessage("Cache config file is updated.", null);
}

