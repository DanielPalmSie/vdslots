<?php
header('Content-Type: text/xml;charset=utf-8');
ob_start();
require_once __DIR__ . '/../../phive/api.php';

echo phive('Netent')->processRequest(file_get_contents('php://input'), 'DK');

