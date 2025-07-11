<?php
require_once __DIR__ . '/../vendor/autoload.php';
include_once __DIR__ . '/../api/Phive.base.php';
require_once __DIR__ . '/../api/BrandedConfig.php';


$phiveLocation = __DIR__ . '/../';


/**
 * Loading Dotenv here to ensure that .env is updated even when phive is broken
 */
try {
    if (class_exists(\Dotenv\Dotenv::class)) {
        if (!method_exists(\Dotenv\Dotenv::class, 'createUnsafeImmutable')) {
            $dotEnv = new \Dotenv\Dotenv($phiveLocation);
            $dotEnv->load();
        } else {
            $dotEnv = \Dotenv\Dotenv::createUnsafeImmutable($phiveLocation);
            $dotEnv->load();
        }
    } else {
        throw new Exception('Dotenv not installed. Please run composer install.');
    }
} catch (Exception $e) {
    error_log('WARNING: ' . 'Dotenv failed to load: ' . $e->getMessage());
}

function createMessage($message, $type = 'WARNING:' ) {
    return "$type" . $message . "\n";
}

function exitWithMessage($message, $type = 'WARNING: ')
{
    echo createMessage($message, $type);
    exit(0);
}

function getAllDomains($configDirectory)
{
    $env_files = glob("$configDirectory/env-files/*.env");

    $domains = array_map(function ($file_path) {
        return pathinfo($file_path, PATHINFO_FILENAME);
    }, $env_files);

    return $domains;
}

// Exit if env is not multibrand
if (!$_ENV['MULTIBRAND']) {
    exitWithMessage('ENV is not multibrand, skip multibrand cache');
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
$cachingDirectoryPath = "$phiveLocation$configDirectory/$cachingDirectory";


if (!$cachingDirectory) {
    exitWithMessage('Caching directory is not specified in config');
}

// Create base cache dir if missing
if (!file_exists($cachingDirectoryPath)){
    if (!mkdir($cachingDirectoryPath)){
        exitWithMessage("Can't create cache directory");
    }
}

phive()->addModule(new BrandedConfig(), 'BrandedConfig');

$errors = [];

//Generate cache for each domain, removing old files
foreach (getAllDomains($configDirectory) as $domain) {
    $domainEnvPath = "$phiveLocation$configDirectory/env-files/$domain.env";
    $useCache = phive('BrandedConfig')->parseEnvFile($domainEnvPath)['CONFIG_CACHING'];

    // SKIP domains not using cache, like stage, test, etc
    if(!$useCache) {
        continue;
    }

    $_SERVER['HTTP_X_CONSUMER_CUSTOM_ID'] = $domain;
    $domainCachingDirectoryPath = "$cachingDirectoryPath/$domain";
    phive('BrandedConfig')->bootstrap($phiveLocation, true);

    //Getting config data in a same way as it was designed by architects
    $config = phive('BrandedConfig')->getNewConfigData();

    if (!is_array($config) || count($config) === 0){
        $errors[$domain][] = createMessage("Can't read config data");
        continue;
    }

    // Create cache directory if doesnt exist
    if (!file_exists($domainCachingDirectoryPath)){
        if (!mkdir($domainCachingDirectoryPath)){
            $errors[$domain][] = createMessage("Can't create cache directory");
            continue;
        }
    }

    // Removing all previously generated cache files
    $files = scandir($domainCachingDirectoryPath); 

    // iterate files
    foreach($files as $file){ 
        if(is_file("$domainCachingDirectoryPath/$file")) {
            // delete file 
            unlink("$domainCachingDirectoryPath/$file"); 
        }
    }

    $cacheTime = $config['CACHE_TIME'] = time();
    $cacheFilePath = "$domainCachingDirectoryPath/$cacheTime-config.json";

    //Create cache file
    $fileCreated = file_put_contents($cacheFilePath, json_encode($config));

    if (!$fileCreated){
        $errors[$domain][] = createMessage("Can't create static config file");
        continue;
    }

    echo "$domain config file is updated \n";
}

if (count($errors)) {
    foreach ($errors as $domain => $domainErrors) {
        echo "$domain errors: \n";
        foreach ($domainErrors as $error) {
            echo $error;
        }
    }
    $keys = implode(", ", array_keys($errors));
    exitWithMessage("Errors in $keys", null);
} else {
    exitWithMessage("No errors", null);
}