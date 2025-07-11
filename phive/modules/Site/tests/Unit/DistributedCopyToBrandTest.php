<?php
namespace Modules\Site\tests\Unit;
use PHPUnit\Framework\Assert;

uses()->group('isolated');

$response = phive('Distributed')->getUserDataFromBrand('devtestmt', null, true);
$data = $response['result'];

print_r($data);

it('users is in DB and is active', function ()  use ($data) {
    // Check if $data['user'] is an array
    expect($data)->toHaveKey('user')->and($data['user'])->toBeArray();

    // Check if $data['user']['active'] is set to 1
    expect($data['user'])->toHaveKey('active')->and($data['user']['active'])->toBe('1');
});



it('checks if user has admin permissions', function () use ($data) {
    // Check if $data has 'permission' key
    expect($data)->toHaveKey('permissions');

    // Check if $data['permission'] is an array
    expect($data['permissions'])->toBeArray();

    // Check if $data['permission'] is not empty
    Assert::assertNotEmpty($data['permissions']);
});


