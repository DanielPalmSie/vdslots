<?php
require_once __DIR__ . '/../../../phive.php';
$rc = json_decode( file_get_contents( "php://input" ), true );
if ( $rc[ 'key' ] != phive( 'Config' )->getSetting( 'rc_key' ) )
    die( 'nok' );
// Call it like this: {key: blabla, method: valAsArray, args: [1,2,3]}
echo json_encode(call_user_func_array([phive('Config'), $rc['method']], $rc['args']));
