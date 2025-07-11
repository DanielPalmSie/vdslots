<?php

/**
 *
 * usage: In phive directory, run:
 *        ./vendor/bin/pest --testsuite --ignore-group=isolated // to run all tests except the ones with the group 'isolated'
 *        ./vendor/bin/pest --testsuite Registration --group=lic-CAON  // to run only the tests with the group 'lic-CAON'
 */

// Base class for all tests (will import phive.php)


uses()->group( 'isolated', 'lic-CAON');

it('Testing from CAON', function () {
    // We should not run this test if we are not in CA-ON
    expect('CA-ON')->toBeTheLicense();

})->skip(!isLic('CA-ON'), 'Test is only for CA-ON');
