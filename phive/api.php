<?php

$GLOBALS['no-session'] = true;

require_once __DIR__ . '/vendor/autoload.php';
include_once __DIR__ . '/api/Phive.base.php';
include_once __DIR__ . '/api/BrandedConfig.php';

phive()->addModule(new BrandedConfig(), 'BrandedConfig');
phive('BrandedConfig')->bootstrap(__DIR__);

include_once phive('BrandedConfig')->getModulesFile();

phive()->install();

