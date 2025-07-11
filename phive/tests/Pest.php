<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

// uses(Tests\TestCase::class)->in('Feature');


/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

use PHPUnit\Framework\ExpectationFailedException;

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

expect()->extend('toBeTheLicense', function () {
    $result = phive('Licensed')->getLicCountryProvince();

    if (!phive('Licensed')->isActive($this->value)) {
        throw new ExpectationFailedException(
            "The license is not active. Got: {$this->value}, want: $result"
        );
    }

    expect($this->value)->toBe($result);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/
/**
 * Helper to use in ->skip() to check if the license is the one we want to test
 * Example:
 *       it('Testing from CAON', function () {
 *         // some logic that will only work if env is configured for CA-ON
 *      })->skip(!isLic('CA-ON'), 'Test is only for CA-ON');
 *
 * @param $lic
 * @return bool
 */
function isLic($lic)
{
    return phive('Licensed')->getLicCountryProvince() == $lic;
}

function something()
{
    // ..
}


/**
 * SETUP LICENSE FOR TESTS
 *
 * This loads a custom license file from the tests directory, based on the group name
 * The group name must start with 'lic-' and the license file must be named 'lic-<groupname>.php'
 * The license file must be in the config/lic-defaults directory
 */

// get the group (or groups) parameter from $_SERVER['argv'], it's an argument passed on CLI on the form --group=group1,group2
// it can be at any position in the array, so we need to loop through it
foreach ($_SERVER['argv'] as $arg) {
    if (strpos($arg, '--group=') === 0) {
        $groups = substr($arg, 8);

        // find a group starting with lic-, there can be multiple groups, so we need to loop through them
        $groups = explode(',', $groups);
        foreach ($groups as $group) {
            if (strpos($group, 'lic-') === 0) {
                $groups = $group;
                break;
            }
        }

        // if we found a group starting with lic-, then set the environment to that group
        if (strpos($groups, 'lic-') === 0) {
            putenv("TEST_APP_LICENSE=$groups");
        }
        break;
    }
}

// Base class for all tests (will import phive.php)
uses(Tests\Unit\PestPhiveBase::class);
