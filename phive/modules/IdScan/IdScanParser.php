<?php

use IdScan\Exceptions\ImageUrlNotFoundException;

class IdScanParser
{
    private array $data;
    private string $hashedUid;
    private string $brand;
    private string $countryCode;
    private array $journeyImages;
    private bool $passed;
    private string $expiryDate;
    private string $expiryDateStatus;


    public function __construct()
    {
    }

    public function parse(array $data): bool {
        $this->data = $data;

        if (!$this->setHashedUid()){
            return false;
        } else {
            $this->setBrand();
            $this->setCountryCode();
            $this->setJourneyImages();
            $this->setPassed();
            $this->setExpiryDate();
            $this->setExpiryDateStatus();
        }

        return true;
    }


    /**
     * @return string|null
     */
    private function setHashedUid(): ?string
    {
        $data = $this->data;
        $ar = $data['AdditionalData'];


        for ($i = 0; $i <= count($ar); $i++) {
            $tar = $ar[$i];
            if($tar['Name'] == 'uid'){
                $this->hashedUid = $tar['Value'];
                return  $this->hashedUid;
            }
        }

        return null;
    }

    private function setCountryCode(): void
    {
        $data = $this->data;
        $ar = $data['ProcessedDocuments'][0];

        $this->countryCode = $ar['IssuingCountryCode'];
    }

    /**
     * Sets brand from webhook
     * @return void
     */
    private function setBrand(): void {
        $data = $this->data;
        $ar = $data['AdditionalData'];

        for ($i = 0; $i <= count($ar); $i++) {
            $tar = $ar[$i];
            if($tar['Name'] == 'brand'){
                $this->brand = $tar['Value'];
            }
        }
    }

    /**
     * @param array $journeyImages
     */
    private function setJourneyImages(): void
    {
        $this->journeyImages = $this->data['JourneyImages'];
    }


    /**
     * @param bool $passed
     */
    public function setPassed(): void
    {
        $this->passed = true;
        $data = $this->data;
        $ar = $data['HighLevelResultDetails'];

        foreach ($ar as $k => $v){
            if (strpos($v, 'FAILED') !== false){
                $this->passed = false;
                return;
            }
        }
    }

    private function setExpiryDate(): void
    {
        $data = $this->data;
        if (isset($data['ProcessedDocuments'][0]['ExtractedFields'])) {
            foreach ($data['ProcessedDocuments'][0]['ExtractedFields'] as $field) {
                if ($field['Name'] == 'ExpiryDate') {
                    phive('DBUserHandler')->logAction(cu(), 'Extracted ID expiry date '.$field['Value'] , 'IDScan');
                    $this->expiryDate = $field['Value'];
                    break;
                }
            }
            phive('DBUserHandler')->logAction(cu(), 'ID Expiry Date was not found' , 'IDScan');

        }
    }

    /**
     * @return string
     */
    public function getCountryCode(): string
    {
        return $this->countryCode;
    }



    /**
     * @return string
     */
    public function getBrand(): string
    {
        return $this->brand;
    }


    /**
     * @return string
     */
    public function getHashedUid(): string
    {
        return $this->hashedUid;
    }

    /**
     * @param string $stepName
     * @param string $imageRole
     * @return string|null
     * @throws ImageUrlNotFoundException
     */
    public function getJourneyImageUrl(string $stepName, string $imageRole): ?string
    {
        $ar = $this->journeyImages;

        for ($i = 0; $i <= count($ar); $i++) {
            $tar = $ar[$i];
            if ($tar['StepName'] == $stepName && $tar['ImageRole'] == $imageRole){
                return $tar['ImageUrl'];
            }
        }
        throw new ImageUrlNotFoundException();
    }


    public function passed(): bool {
        return $this->passed;
    }

    public function getExpiryDate(): string
    {
        return $this->expiryDate;
    }

    public function setExpiryDateStatus()
    {
        $data = $this->data;
        $highLevelResultDetails = $data['HighLevelResultDetails'];
        foreach ($highLevelResultDetails as $detail) {
            if (strpos($detail, 'DOCUMENTEXPIRY') !== false) {
                $expiryDateStatus = explode(':', $detail)[1];
                $this->expiryDateStatus = $expiryDateStatus;
                break;
            }
        }
    }

    public function getExpiryDateStatus(): string
    {
        return $this->expiryDateStatus;
    }

}
