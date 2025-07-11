<?php
require_once __DIR__ . '/../../phive/api.php';

$body = json_decode(file_get_contents('php://input'), true);

$gpr = phive('Gpr');
$gpr->init();

echo json_encode($gpr->exec($body));
