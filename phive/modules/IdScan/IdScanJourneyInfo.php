<?php

namespace IdScan;

use GuzzleHttp\Exception\GuzzleException;
use IdScan;
use IdScan\Exceptions\ImageNotFoundException;
use IdScan\Exceptions\ImageUrlNotFoundException;
use IdScan\Exceptions\JourneyNotFoundException;
use IdScan\Exceptions\NoAccessTokenException;
use IdScanParser;

class IdScanJourneyInfo
{
    private IdScan $idScan;
    private string $journeyId;
    private IdScanParser $idScanParser;

    /**
     * @throws GuzzleException
     * @throws NoAccessTokenException
     * @throws JourneyNotFoundException
     */
    public function __construct($journeyId)
    {
        $this->idScan = new IdScan();
        $this->journeyId = $journeyId;
        $this->idScanParser = new IdScanParser();

        if (!$this->getJourney()) {
            throw new JourneyNotFoundException();
        }
    }

    /**
     * @throws NoAccessTokenException|GuzzleException
     */
    public function getJourney(): bool
    {
        $token_data = $this->idScan->generateToken('investigation');

        if (!isset($token_data['access_token'])) {
            throw new NoAccessTokenException();
        }

        $this->idScan->setToken($token_data['access_token']);

        $journey_data = $this->idScan->getJourney($this->journeyId);
        return $this->idScanParser->parse($journey_data);
    }

    public function brand(): string
    {
        return $this->idScanParser->getBrand();
    }

    public function passed(): bool
    {
        return $this->idScanParser->passed();
    }

    /**
     * @throws Exceptions\MissingSettingException
     * @throws Exceptions\NotReadableImageException
     * @throws ImageNotFoundException
     * @throws GuzzleException
     * @throws ImageUrlNotFoundException
     */
    public function image(): IdScanImage
    {
        return new IdScanImage($this->imageBase64());
    }

    public function hashedUid(): string
    {
        return $this->idScanParser->getHashedUid();
    }

    /**
     * @throws ImageUrlNotFoundException
     * @throws GuzzleException|ImageNotFoundException
     */
    public function imageBase64()
    {
        $image_url = $this->idScanParser->getJourneyImageUrl('ID Document', 'WhiteImage');

        return $this->idScan->getJourneyImage($image_url)['base64'];
    }
}