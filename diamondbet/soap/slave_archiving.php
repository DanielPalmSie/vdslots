<?php
require_once __DIR__ . '/../../phive/phive.php';

$sql     = phive('SQL');

foreach(range(0, count(phive('SQL')->getShards()) - 1) as $i){
    $anode = new SQL($sql->getSetting('shard_archives')[$i]);
    $snode = new SQL($sql->getSetting('slave_shards')[$i]);
    foreach(['wins', 'bets', 'bets_mp', 'wins_mp'] as $tbl){
        echo "Node $i, table $tbl\n";
        $max_id = $anode->getValue("SELECT id FROM $tbl ORDER BY id DESC LIMIT 1");
        $snode->applyToRows("SELECT * FROM $tbl WHERE id > $max_id", function($r) use ($anode, $tbl){
            $anode->insertArray($tbl, $r);
            //exit;
        });
    }
}
