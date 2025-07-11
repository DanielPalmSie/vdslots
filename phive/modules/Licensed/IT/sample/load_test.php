<?php
require_once __DIR__ . '/../../../../phive.php';
/*
|--------------------------------------------------------------------------
| Required script in sample folder
|--------------------------------------------------------------------------
|
| This file is required to be included in any new file, elaboration below:
| Include checking that if the files are not in test mode in the folder /sample inside IT
| they just die and can't be executed as they will be public otherwise
| isLocal() - Checks if current domain has .loc at the end.
| isTest() - Returns true if the current project's domain has 'test' in it, example: test2.videoslots.com.
*/
if (phive()->isLocal() || phive()->isTest()) {
    $test_env = true;
}
$test_env ?? die();