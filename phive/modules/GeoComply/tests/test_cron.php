<?php
require_once __DIR__ . '/../../../phive.php';

// Prepare the test as if the user was playing
$gc = phive('GeoComply');
$user = cu('ontest');

if (empty($user)) {
    die('Please use an existing Ontario user');
}

$user_id = $user->data['id'];
phMsetShard('is_playing', 1, $user_id, 60);


if (!$gc->hasVerifiedIp($user)) {
// On frontend make sure that the user has a valid package (login with the user and pass geolocation check)
    die('Please first login with the user and pass verification');
}

if (!empty(lic('geoComplyCron', [], $user_id))) {
    die("Error 1: user should not be logged out yet");
}

phMdelShard('is_playing', $user_id);

if (!empty(lic('geoComplyCron', [], $user_id))) {
    if (!$gc->hasVerifiedIp($user)) {
        die('Please login again, the geolocation has expired before concluding the test');
    }
    die("Error 2: user should not be logged out if it's not playing a game");
}
phMsetShard('is_playing', 1, $user_id, 60);
phMdel($user_id . '.geocomply');

if (empty(lic('geoComplyCron', [], $user_id))) {
    die("Error 3: user should be logged out");
}
echo "OK - test passed";
exit;

