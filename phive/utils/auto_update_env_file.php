<?php

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Exit if not in cli mode
 * @see isCli()
 */
if (!empty($_SERVER['REMOTE_ADDR']) || php_sapi_name() !== 'cli') {
    exit;
}

$phive_location = __DIR__ . '/../';
function exitWithMessage($message)
{
    echo 'WARNING: ' . $message . "\n";
    exit(0);
}

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

$env_file = $phive_location . '.env';
if (!file_exists($env_file)) {
    exitWithMessage('File .env does not exist.');
}

if (empty($_ENV['DOMAIN'])) {
    exitWithMessage('Domain not configured in .env.');
}

$config_new_dir = $_ENV['CONFIG_NEW_DIR'];
if (empty($config_new_dir)) {
    $config_new_dir = 'config-new';
}
$domain_file = $phive_location . $config_new_dir . '/env-files/' . $_ENV['DOMAIN'] . '.env';
if (!file_exists($domain_file)) {
    file_put_contents($env_file, "DOMAIN={$_ENV['DOMAIN']}
CONFIG_DIR=config
");
    exitWithMessage('Domain file is missing in env-files.');
}

$env_data = file_get_contents($env_file);
if ($env_data === false) {
    exitWithMessage("Failed to read from $env_file.");
}

$domain_data = file_get_contents($domain_file);
if ($domain_data === false) {
    exitWithMessage("Failed to read from $domain_file.");
}

if ($env_data !== $domain_data) {
    file_put_contents($env_file, $domain_data);
    exitWithMessage("Replaced $env_file file with data from $domain_file.");
}

exitWithMessage("File .env is up to date.");
