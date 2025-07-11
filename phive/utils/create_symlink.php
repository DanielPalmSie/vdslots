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

$admin_dir = $_ENV['ADMIN_DIR'];

if(!$admin_dir){
    exitWithMessage('Admin2 path is not in .env file');
}

$admin_phive_dir = $admin_dir.'phive_admin';
$phive_admin = $phive_location.'admin';

if (file_exists($admin_dir)) {
    if (!file_exists($phive_admin)) {
        if (symlink($admin_phive_dir, $phive_admin)) {
            exitWithMessage("Symlink to admin2 created successfully.");
        } else {
            exitWithMessage("Failed to create admin2 symlink.");
        }
    } else {
        exitWithMessage("Symlink to admin2 already exists.");
    }
} else {
    exitWithMessage("The admin2 folder does not exist.");
}
