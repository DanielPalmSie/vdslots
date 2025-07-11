<?php
include __DIR__ . '/../../phive/admin.php';
require_once __DIR__ . '/../../phive/modules/SQL/html/sync_slaves.php';
$row 		= syncRow();
if(!empty($row) && $_POST['table'] == 'micro_games'){
  //phive('Command')->scpUpload("{$row['game_id']}_BG.jpg", $_POST['slave']);
  //phive('Command')->scpUpload("{$row['game_id']}_big.jpg", $_POST['slave']);
}
echo json_encode($row);
