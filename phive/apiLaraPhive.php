<?php

error_reporting(4181);
$GLOBALS['no-session'] = true;
$GLOBALS['lara-api'] = true;

require_once __DIR__ . '/vendor/autoload.php';
include_once __DIR__ . '/api/Phive.base.php';
include_once __DIR__ . '/api/BrandedConfig.php';
require_once __DIR__ . "/initLaraPhive.php";

phive()->addModule(new BrandedConfig(), 'BrandedConfig');
phive('BrandedConfig')->bootstrap(__DIR__);

require_once phive('BrandedConfig')->getModulesFile();

phive()->install();
