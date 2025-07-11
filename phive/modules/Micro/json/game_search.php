<?php
require_once __DIR__ . '/../../../phive.php';
$sql = phive('SQL');
$mg = phive('MicroGames');
switch($_REQUEST['action']){
  case 'get-game':
      $game = $mg->getByGameId($sql->escape($_REQUEST['game_id'], $sql->escape($_REQUEST['device'])));

      $url = $mg->onPlay($game, ['game_id' => $_REQUEST['game_id'], 'type' => $_REQUEST['device'], 'lang' => $_REQUEST['lang']]);

      $res = ['url' => $url, 'game' => $game];
    break;
  default:
    // Decode xhtml quote added in Phive::htmlQuotes for proper text matching
    $user_input = html_entity_decode($_REQUEST['search_str'], ENT_QUOTES|ENT_XHTML);
    $escaped_user_input = $sql->escape($user_input, false);

    $sstr = "game_name LIKE '%{$escaped_user_input}%' AND active = 1 ";

    $res = $mg->getAllGames(
      $sstr,
      "id, game_name, game_id, game_url, tag, network, width, height, ribbon_pic",
      "flash",
      true);
    break;
}

echo json_encode($res);
