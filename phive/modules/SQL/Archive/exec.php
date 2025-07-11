<?php

require_once __DIR__ . '/../../../phive.php';
require_once __DIR__ . '/BackgroundProcessor.php';

if (!isCli()) {
    exit;
}

// setting this to 1 seems to have no effect
// the process keeps on running even after 1 second
//ini_set('max_execution_time', 60 * 60 * 12);

$GLOBALS['is_archive'] = true;

BackgroundProcessor::exec($_SERVER['PROCESS_TRACKER_ID'], $_SERVER['argv']);
