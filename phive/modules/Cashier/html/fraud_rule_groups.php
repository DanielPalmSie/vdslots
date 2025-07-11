<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../api/crud/crud.php';
$crud = Crud::table('fraud_groups', true);
$crud->renderInterface('id');
