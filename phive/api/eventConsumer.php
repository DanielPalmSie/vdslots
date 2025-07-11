<?php
require_once __DIR__ . '/../phive.php';
require_once __DIR__ . '/../modules/Events/EventConsumer.php';

$consumerNames = $argv[1];

if (empty($consumerNames)) {
    dd2("Consumer name needs to be provided.");
}

$consumerNames = array_filter(explode(',', $consumerNames));

// To avoid infinite recursion in some cases.
$GLOBALS['is_queued'] = true;

try {
    /** @var EventConsumer $consumer */
    $consumer = new EventConsumer($consumerNames);
    $consumer->init();
} catch (\Throwable $e) {
    phive('Logger')->getLogger('queue_messages')->debug("ERROR exception eventConsumer.php ...", [$e->getMessage()]);
}


