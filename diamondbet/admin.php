<?php

$file1 = realpath(__DIR__ . '/../phive/admin.php');
$admin2_loc = phive()->getSetting('admin2_loc');
$file2 = realpath($admin2_loc . '/public/index.php');

if (is_readable($file1) && is_readable($file2)) {
    require_once $file1;
    require_once $file2;
} else {
    die("An error occurred while loading the necessary files. Please contact the administrator.");
}
