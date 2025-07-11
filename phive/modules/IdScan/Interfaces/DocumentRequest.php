<?php
namespace IdScan\Interfaces;

interface DocumentRequest
{
    public function getUid();

    public function getCountryCode();

    public function getJourneyID();

    public function getJourneyImage();

    public function verify();

    public function getExpiryDate();

    public function getExpiryDateStatus();

    public function isValidExpiryDate();
}
