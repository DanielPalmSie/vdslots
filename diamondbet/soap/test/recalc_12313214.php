<?php
require_once __DIR__ . '/../../../phive/phive.php';

if (!isCli()) {
    die("Error: This script must be run in a CLI environment.\n");
}

$tournament = phive('Tournament');

echo "Starting calcPrizesCron()...\n";

try {
    $tournament->calcPrizesCron();
    echo "calcPrizesCron() finished without exceptions.\n";
} catch (Exception $e) {
    echo "Error in calcPrizesCron(): " . $e->getMessage() . "\n";
}

echo "Done!\n";
