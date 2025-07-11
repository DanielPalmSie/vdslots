<?php

namespace IdScan;

use IdScan\Exceptions\CreateIdScanDocumentException;
use IdScan\Interfaces\DocumentRequest;

class IdScanDocument
{
    private const TAG = 'idcard-pic';

    /*
     * @var LoggerInterface
     */
    private $logger;
    private \Dmapi $documentsApi;

    public function __construct($logger, \Dmapi $dmapi)
    {
        $this->logger = $logger;
        $this->documentsApi = $dmapi;
    }

    /**
     * @throws CreateIdScanDocumentException
     */
    public function document($uid)
    {
        $documents = $this->documentsApi->getUserDocumentsV2($uid);

        $docn = (int)array_search(self::TAG, array_column($documents, 'tag'));
        $document = $documents[$docn];

        if (empty($document)) {
            $this->logger->critical("Missing idcard-pic document for $uid");
            throw new CreateIdScanDocumentException();
        }

        $this->logger->debug("idcard-pic document for user $uid", $document);

        return $document;
    }

    /**
     * Retrieve documents from IDScan and save to admin2 user profile
     * @param array $user_data
     * @param IdScanImage $image
     * @return bool
     * @throws CreateIdScanDocumentException
     */
    public function saveDocuments(DocumentRequest $request, IdScanImage $image): bool
    {
        $uid = $request->getUid();
        $this->logger->debug("Save documents was called for $uid");

        $document = $this->document($uid);
        $isExpiryDateValid = $request->isValidExpiryDate();
        $isImageSaved = (bool) $this->saveImage($document['id'], $request, $image);
        if ($isExpiryDateValid) {
            return $isImageSaved && $this->saveExpiryDate($document['id'], $request);
        }
        return $isImageSaved;
    }

    /**
     * @param $uid
     * @param IdScanImage $image
     * @param $document_id
     * @param $country
     * @return array|string[][]
     * @throws CreateIdScanDocumentException
     */
    public function saveImage($document_id, DocumentRequest $request, IdScanImage $image): array
    {
        $uid = $request->getUid();
        $countryCode = $request->getCountryCode();

        $files = [[
            'original_name' => $uid . '_idscan.' . $image->extension(),
            'uploaded_name' => $uid . '_idscan.' . $image->extension(),
            'mime_type' => $image->mime(),
            'tag' => self::TAG,
            'encoded_data' => $image->encoded()
        ]];

        $this->logger->debug("Saving image to a document", [$document_id, $uid, $image->extension(), $image->mime(), $image->size()]);
        $response = $this->documentsApi->addMultipleFilesToDocument($document_id, $files, self::TAG, $countryCode, $uid);

        if (!$response || !empty($response['errors'])) {
            $this->logger->critical("DMAPI error.", $response);
            // Document service could be temporarily unavailable, throw exception to retry later
            throw new CreateIdScanDocumentException('Error saving image to dmapi');
        }
        $documentStatus =  $request->isValidExpiryDate() ? 'approved' : 'processing';

        $this->documentsApi->updateDocumentStatus($uid, $document_id, $documentStatus, $uid);
        return $response;
    }

    public function saveExpiryDate($document_id, DocumentRequest $request)
    {
        $uid = $request->getUid();
        $expiryDate = $request->getExpiryDate();
        $this->logger->debug("Saving expiry date to a document", [$document_id, $uid, $expiryDate]);
        $response = $this->documentsApi->updateDocumentExpiryDate($uid, $document_id, $expiryDate);
        if (!$response || !empty($response['errors'])) {
            $this->logger->critical("DMAPI error.", $response);
            throw new CreateIdScanDocumentException('Error saving expiry date to dmapi');
        }
        return $response;
    }
}
