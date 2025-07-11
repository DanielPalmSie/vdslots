<?php
/**
 * File temporarily restored to avoid error log on the server.
 */

require_once __DIR__ . '/../../phive.php';

phive()->sessionStart();

if (!isLogged()) {
    error_log("404 - Tried to use play.php logged out");

    phive('Redirect')->to("/404/", '', false, '404 Not Found');
}

$game 	= phive('MicroGames')->getByGameId($_GET['game_id'], 0);

$lang = empty($_GET['lang']) || $_GET['lang'] == 'en' ? '' : '/'.$_GET['lang'];
$url = phive()->getSiteUrl().$lang.'/play/'.$game['game_url'];
if ($_GET['show_demo'] ?? false) {
    $url = $url . '?show_demo=true';
}
error_log('OLD page for loading games, not in use anymore, redirect the player to the correct one  with a 301.');

phive('Redirect')->to($url, '');
exit;

