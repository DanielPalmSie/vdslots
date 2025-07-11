<?php 
    require_once __DIR__ . '/../../../phive/phive.php';

    $th = phive('Tournament');
    $tournament = $th->byId(688);
    $entries = $th->entries($tournament);
    foreach($entries as $e) {
        $th->startEvents($tournament, $e);
    }
    exit;

?>
