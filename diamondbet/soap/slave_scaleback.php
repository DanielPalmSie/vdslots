<?php
require_once __DIR__ . '/../../phive/phive.php';

if (!isCli()) {
    exit;
}

$args = $_SERVER['argv'];

array_shift($args);

$GLOBALS['is_archive'] = true;

phive('SQL/Archive')->execute($args);
