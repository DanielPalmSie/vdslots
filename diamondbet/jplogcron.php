<?php
ini_set('max_execution_time', '240');
require_once __DIR__ . '/../phive/phive.php';

if(isCli()){
    phive('MicroGames')->parseJps('netent');
}

