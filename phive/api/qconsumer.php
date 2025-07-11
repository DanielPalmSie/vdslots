<?php
require_once __DIR__ . '/../phive.php';

$queueNames = $argv[1];

if (empty($queueNames)) {
    dd2("Consumer name needs to be provided.");
}

$queueNames = array_filter(explode(',', $queueNames));

// To avoid infinite recursion in some cases.
$GLOBALS['is_queued'] = true;

/** @var Consumer $consumer */
$consumer = phive('Site/Consumer');

$consumer->start($queueNames);
