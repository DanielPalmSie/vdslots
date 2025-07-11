<?php
require_once __DIR__ . '/../../../api.php';

/** @var Distributed $dist */
$dist = phive('Distributed');

$dist->validateIP();

$req = file_get_contents("php://input");

$dist->dumpLog('dist_target_req', $req);

$req = json_decode($req, true);

$dist->validateAuth($req);

$res = $dist->exec($req);

$dist->dumpLog('dist_target_res', $res);

if ($res === false) {
    $res = 'error';
}

echo json_encode($res);
