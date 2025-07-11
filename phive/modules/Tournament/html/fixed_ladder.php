<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../api/crud/crud.php';
Crud::table('tournament_award_ladder')->renderInterface();
