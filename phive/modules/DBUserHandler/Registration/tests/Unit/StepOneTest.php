<?php

/**
 *
 * usage: In phive directory, run:
 *        ./vendor/bin/pest --testsuite Registration
 */


it('array response', function () {
    // call the function we want to test
    $result = phive('DBUserHandler')->getRegistrationData('MT');
    expect($result)->toBeArray();
});


// test that we don't output anything (to avoid broken HTML responses from loadJS)
beforeEach(function () {
    ob_start();
});

afterEach(function () {
    // get the echo output
    $output = ob_get_clean();

    // assert that the output is what we expect
    //expect($output)->toBe('');
});
