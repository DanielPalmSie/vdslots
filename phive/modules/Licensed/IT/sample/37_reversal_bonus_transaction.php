<?php
include __DIR__ . '/00__variables.php';
require_once __DIR__ . '/../../../Test/TestPhive.php';

$user = cu('devtestit2');

if (empty($user) || cuCountry($user) != 'IT') {
    die("Wrong user");
}
$uid = uid($user);


$bonus_test = TestPhive::getModule('Bonuses');
$bonus_test->setup($user);
$bonus_test->giveWelcome($user);
$bonus_test->reset($user);
$bonus_test->addUserBonus(true, 2122, true);
$bonus_test->playFrb($user);
sleep(2);
$bonus_entry = phive("SQL")->sh($uid)->loadAssoc("SELECT * FROM bonus_entries WHERE status = 'active' AND user_id = $uid ORDER BY id desc LIMIT 1");

if (empty($bonus_entry)) {
    die("No bonus uid-" . $uid);
}


print_r(lic('cancelBonus', [$user, $bonus_entry], $user));