<?php
/**
 *
 * usage: In phive directory, run:
 *        ./vendor/bin/pest --testsuite Distributed --ignore-group=isolated // to run all tests except the ones with the group 'isolated'
 */

use Laraphive\Contracts\EventPublisher\EventPublisherInterface;

uses()->group('isolated');

it('checks distributed is configured properly', function () {
    $username = 'devtestgb';
    $enabled_remote_brand = phive('DBUserHandler')->getSetting('check_remote_brand', false);

    expect($enabled_remote_brand)->toBeTrue();


    $restricted_countries = phive('Config')->valAsArray('countries', 'transfer-blocked');

    expect(in_array(cuCountry($username), $restricted_countries))->toBeFalse();

    $result = toRemote(getRemote(), 'userInBrand', [$username, false, false, $restricted_countries, true]);

    expect($result)->toBeArray()->toHaveKey('success', 1);
});

it('simulates queue messages', function () {
    expect(phive('Distributed')->getSetting('queue-simulated'))->toBeTrue();
    phiveApp(EventPublisherInterface::class)
                ->fire('authentication', 'AuthenticationSimulated', [], 0);
    // if this doesn't throw an exception, it means the test passed
    expect(true)->toBeTrue();
});
