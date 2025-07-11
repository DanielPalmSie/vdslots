<?php

use IdScan\Exceptions\CreateIdScanDocumentException;
use IdScan\Interfaces\DocumentRequest;
use IdScan\IdScanDocument;
use IdScan\IdScanDocumentRequest;
use IdScan\IdScanImage;
use Tests\Unit\Mock\StdoutLogger;

uses(Tests\Unit\PestPhiveBase::class);

$request = new IdScanDocumentRequest();
$request->setUid(rand(100000, 999999));
$request->setCountryCode('DE');
$request->setJourneyID('123');
$request->setJourneyImage(__DIR__.'/assets/image.jpg');


it('asserts that request is correct', function (DocumentRequest $request){
    $result = $request->verify();
    expect($result)->toBeTrue();

})->with(
        ['request' => $request]
);

it('saves document with mocked dmapi', function ($request, $file_path) {
    $logger = new StdoutLogger();
    $documentsApi = $this->createMock(\Dmapi::class);
    $documentsApi->method('getDocumentByTag')->willReturn([]);
    $documentsApi->method('createEmptyDocument')->willReturn(['id' => 1]);
    $documentsApi->method('addMultipleFilesToDocument')->willReturn(['success' => true]);

    try {
        $image = new IdScanImage($file_path);
        $idScanDocument = new IdScanDocument($logger, $documentsApi);

        $result = $idScanDocument->saveDocuments($request, $image);
        expect($result)->toBeTrue();
    } catch (\Exception $e) {
        $this->fail($e->getMessage());
    }
})->with([
        'Saving document with big image' =>
                [
                        'request' => $request,
                        'file_path' => __DIR__.'/assets/image.jpg',
                ],
]);


it('saves document with a DocumentRequest instance', function (DocumentRequest $request) {
    try {
        $image = new IdScanImage($request->getJourneyImage());
        $idscanDocument = new IdScanDocument(new StdoutLogger(), phive('Dmapi'));

        $result = $idscanDocument->saveDocuments($request, $image);

        expect($result)->toBeTrue();
    } catch (\Exception $e) {
        $this->fail($e->getMessage());
    }

})->with(
        ['request' => $request]
);

it('throws CreateIdScanDocumentException exception on creating document', function ($request) {

    $documentsApi = $this->createMock(\Dmapi::class);
    $documentsApi->method('getDocumentByTag')->willReturn([]);
    $documentsApi->method('createEmptyDocument')->willReturn(null); // error on creating document
    $documentsApi->method('addMultipleFilesToDocument')->willReturn(['success' => true]);

    $idscanDocument = new IdScanDocument(new StdoutLogger(), $documentsApi);
    $image = new IdScanImage(__DIR__.'/assets/image.jpg');

    $idscanDocument->saveDocuments($request, $image);

})->with(
        ['request' => $request]
)->throws(CreateIdScanDocumentException::class, 'Error creating document');

it('throws CreateIdScanDocumentException exception on saving document', function ($request) {
    $documentsApi = $this->createMock(\Dmapi::class);
    $documentsApi->method('getDocumentByTag')->willReturn([]);
    $documentsApi->method('createEmptyDocument')->willReturn(['id' => 1]);
    $documentsApi->method('addMultipleFilesToDocument')->willReturn(null); // error on saving document

    $idscanDocument = new IdScanDocument(new StdoutLogger(), $documentsApi);
    $uid = rand(100000, 999999);
    $image = new IdScanImage(__DIR__.'/assets/image.jpg');

    $idscanDocument->saveDocuments($request, $image);

})->with(
        ['request' => $request]
)->throws(CreateIdScanDocumentException::class, 'Error saving image to dmapi');

it('throws CreateIdScanDocumentException exception on saving document 2', function ($request) {
    $documentsApi = $this->createMock(\Dmapi::class);
    $documentsApi->method('getDocumentByTag')->willReturn([]);
    $documentsApi->method('createEmptyDocument')->willReturn(['id' => 1]);
    $documentsApi->method('addMultipleFilesToDocument')->willReturn(['errors' => true]); // error on saving document

    $idscanDocument = new IdScanDocument(new StdoutLogger(), $documentsApi);
    $image = new IdScanImage(__DIR__.'/assets/image.jpg');

    $idscanDocument->saveDocuments($request, $image);

})->with(
        ['request' => $request]
)->throws(CreateIdScanDocumentException::class, 'Error saving image to dmapi');

