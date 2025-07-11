<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../api/crud/crud.php';

Crud::table('bonus_codes')->renderInterface('id', array('affe_id' => array('table' => 'users', 'idfield' => 'id', 'dfield' => 'username')));

