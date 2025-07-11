<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../api/crud/crud.php';
$crud = Crud::table('micro_games', true);
$crud->renderInterface(
    'id',
    [],
    true,
    [
      'client_id' => 10001,
      'width' => 800,
      'height' => 600,
      'meta_descr' => '#game.meta.description',
      'jackpot_contrib' => 0,
      'op_fee' => 0.15,
      'languages' => 'en',
      'popularity' => 0,
      'played_times' => 0,
      'stretch_bkg'	=> 1
    ],
    [],
    true,
    [],
    ['ext_game_name']
);

