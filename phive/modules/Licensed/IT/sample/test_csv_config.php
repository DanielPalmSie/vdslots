<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';

$config = phive('Config');

$countries_service = new \IT\Services\CountriesService();

$countries_service->getAllCountries();
$countries_service->getCountries();

