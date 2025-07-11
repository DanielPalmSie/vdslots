<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../api/crud/crud.php';

?>
Display tags by game:
<form method="post" action="">
<input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
<?php dbSelect('show_id', phive('MicroGames')->selAllGamesShowDevice('id', "", 1), $_POST['show_id']) ?>
<input type="submit" value="Submit" />
</form>
<br>
<br>
<?php

$where = '';

$sql = "
    SELECT
        gt.alias,
        CASE
            WHEN COUNT(gtc.game_id) > 0 THEN true
            ELSE false
        END as has_connected_games
    FROM game_tags gt
    LEFT JOIN game_tag_con gtc ON gt.id = gtc.tag_id
    WHERE gt.alias IN ('gameofweek.cgames', 'livecasinospotlight.cgames')
    GROUP BY gt.alias
";

$tags = phive('SQL')->loadArray($sql, 'ASSOC', 'alias');

if ((int)$tags['gameofweek.cgames']['has_connected_games'] !== 0) {
    $where .= "AND alias != 'gameofweek.cgames' ";
}
if ((int)$tags['livecasinospotlight.cgames']['has_connected_games'] !== 0) {
    $where .= "AND alias != 'livecasinospotlight.cgames' ";
}

Crud::table('game_tag_con', true)->setMulti(array('tag_id'))->setWhere("game_id = '{$_POST['show_id']}'")->renderInterface('id', array(
    'tag_id' => array('table' => 'game_tags', 'idfield' => 'id', 'dfield' => 'alias', 'where' => $where),
    'game_id' => array('table' => 'micro_games', 'idfield' => 'id', 'dfield' => 'game_name', 'dfields' => array('game_name', 'device_type', 'network', 'active'))));
