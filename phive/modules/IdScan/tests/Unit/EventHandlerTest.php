<?php

uses(Tests\Unit\PestPhiveBase::class);

use IdScan\Exceptions\CreateIdScanDocumentException;
use IdScan\IdScanDocument;
use IdScan\IdScanImage;
use Tests\Unit\Mock\StdoutLogger;

it('handles image creation', function () {
    $idScanModule = phive('IdScan');

    $documentsApi = $this->createMock(\Dmapi::class);
    $documentsApi->method('getDocumentByTag')->willReturn([]);
    $documentsApi->method('createEmptyDocument')->willReturn(['id' => 1]);
    $documentsApi->method('addMultipleFilesToDocument')->willReturn(['errors' => true]); // error on saving document

    $request = new \IdScan\IdScanDocumentRequest();
    $request->setUid(rand(100000, 999999));
    $request->setJourneyID('1234-5678');
    $request->setCountryCode('DE');
    $request->setJourneyImage(__DIR__ . '/assets/image.jpg');
    $request->setExpiryDate('2025-05-18');
    $request->setExpiryDateStatus('PASSED');

    if($request->verify()){
        $idscanDocument = new IdScanDocument(new StdoutLogger(), $documentsApi);
        $image = new IdScanImage($request->getJourneyImage());

        $idscanDocument->saveDocuments($request, $image);
    }

})->throws(CreateIdScanDocumentException::class, 'Error saving image to dmapi');
