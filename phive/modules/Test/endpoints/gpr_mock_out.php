<?php
require_once __DIR__ . '/../../../phive.php';
require_once __DIR__ . '/../TestPhive.php';

// http://www.videoslots.loc/phive/modules/Test/endpoints/gpr_mock_out.php

if(empty($_POST['original_url'])){
    $body = json_decode(file_get_contents('php://input'), true);
    $from_brand = $body['from_brand_request'];
} else {
    // Form post, ex: Pragmatic
    // phive('Logger')->getLogger('casino')->debug('GPR MOCK POST DATA: ', $_POST);
    $body = $_POST;
    $from_brand = json_decode($_POST['from_brand_request'], true);
    unset($body['from_brand_request']);
}

phive('Logger')->getLogger('casino')->debug('GPR MOCK OUT: ', ['body' => $body, 'server' => $_SERVER]);

$gp_map = [
    'stakelogic' => 'StakelogicV2'
];

$class = $gp_map[ $from_brand['gp'] ];

if(empty($class)){
    $class = ucfirst($from_brand['gp']).'Gpr';
}

$gp = TestPhive::getModule( $class );
$gpr = phive('Gpr');
$gp->injectGpr($gpr);
$res = $gp->mockReply($body, $from_brand);
echo is_array($res) ? json_encode($res) : $res;


