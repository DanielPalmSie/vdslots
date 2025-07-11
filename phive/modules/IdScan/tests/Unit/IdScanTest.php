<?php

/**
 *
 * usage: In phive directory, run:
 *        ./vendor/bin/pest --filter=IdScanTest
 */

uses()->group('lic-CAON');


it('asserts that IdScan.php file exists', function () {
    $file = __DIR__ . '/../../IdScan.php';
    expect($file)->toBeFile(); // same as $this->assertFileExists($file);
    // @see https://pestphp.com/docs/expectations
});

it('asserts that PHIVE is available', function () {
    // making sure that we are using the correct Test Base Class with necessary phive import
    expect(phive())->toBeObject();
});

it('asserts that missing credentials passed as parameter logs a critical warning', function () {
    // note mockery for pest requires php >= 8.0, so we are using phpunit mock objects, but would be nice in the future
    // to use mockery @see https://pestphp.com/docs/plugins/mock

    // @see https://phpunit.readthedocs.io/en/9.5/test-doubles.html?highlight=mocks#stubs
    $logger = $this->createMock(Logger::class);
    $logger->method('critical')->willReturn(true);

    $logger->expects($this->once())->method('critical');

    $idScan = new IdScan(null, $logger);
    $credentials = ($idScan->getSetting('auth'));

    // remove one of the credentials
    unset($credentials['USERNAME']);

    $idScan->init($credentials);
});

it('asserts that missing credentials on configuration will log a critical warning', function () {

    $logger = $this->createMock(Logger::class);
    $logger->method('critical')->willReturn(true);

    $logger->expects($this->once())->method('critical');

    $idScan = new IdScan(null, $logger);
    $credentials = ($idScan->getSetting('auth'));

    // remove one of the credentials
    unset($credentials['USERNAME']);

    $idScan->setSetting('auth', $credentials);

    $idScan->init();
});

it('asserts that calls to the init method without params work', function () {

    $logger = $this->createMock(Logger::class);
    $logger->method('critical')->willReturn(true);

    $logger->expects($this->never())->method('critical');

    $idScan = new IdScan(null, $logger);
    $credentials = ($idScan->getSetting('auth'));

    $idScan->setSetting('auth', $credentials);

    $idScan->init();
});


it('it asserts that IDScan data can be stored to a Redis and then retrieved', function () {
    $datafile = file_get_contents(__DIR__.'/assets/stepdata.txt', 'r');
    $data = json_decode($datafile);

    //journeyID = "fafc96b4-d3e4-4eab-bf36-4f3f8d5d09a7";
    $hashed_uuid = '21dabc85-1011-f6a9-9b06-000062b1ca7a';

    phMsetArr($hashed_uuid, $data);

    $idScan = new IdScan();
    $savedData = $idScan->getSavedUserData($hashed_uuid);

    expect($savedData)->toBeArray();
});
