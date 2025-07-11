<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../api/crud/crud.php';
$crud = Crud::table('allowed_ips', true);
$crud->renderInterface();
