<?php
namespace IdScan;

use IdScan\Interfaces\DocumentRequest;

class IdScanDocumentRequest implements DocumentRequest {
    private string $uid;

    private string $countryCode = 'MT';
    private string $journeyID;

    private string $journeyImage;
    private string $expiryDate;
    private string $expiryDateStatus;

    public function __construct()
    {
    }

    /**
     * @return string
     */
    public function getUid(): string
    {
        return $this->uid;
    }

    /**
     * @param string $uid
     */
    public function setUid(string $uid): void
    {
        $this->uid = $uid;
    }

    /**
     * @return string
     */
    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    /**
     * @param string $countryCode
     */
    public function setCountryCode(string $countryCode): void
    {
        $this->countryCode = $countryCode;
    }

    /**
     * @return string
     */
    public function getJourneyID(): string
    {
        return $this->journeyID;
    }

    /**
     * @param string $journeyID
     */
    public function setJourneyID(string $journeyID): void
    {
        $this->journeyID = $journeyID;
    }

    /**
     * @return string
     */
    public function getJourneyImage(): string
    {
        return $this->journeyImage;
    }

    /**
     * @param string $journeyImage
     */
    public function setJourneyImage(string $journeyImage): void
    {
        $this->journeyImage = $journeyImage;
    }

    public function verify()
    {
        if(empty($this->uid) ||
            empty($this->journeyID) ||
            empty($this->countryCode) ||
            empty($this->journeyImage)
        ) {
            return false;
        }

        return true;
    }

    public function getExpiryDate(): string
    {
        return $this->expiryDate;
    }


    /**
     * @param $expiryDate
     */
    public function setExpiryDate($expiryDate): void
    {
        $this->expiryDate = $expiryDate;
    }

    public function setExpiryDateStatus($expiryDateStatus): void
    {
        $this->expiryDateStatus = $expiryDateStatus;
    }
    public function getExpiryDateStatus(): string
    {
        return $this->expiryDateStatus;
    }

    public function isValidExpiryDate(): bool
    {
        if ($this->expiryDateStatus != 'PASSED'){
            return false;
        }
        return true;
    }
}
