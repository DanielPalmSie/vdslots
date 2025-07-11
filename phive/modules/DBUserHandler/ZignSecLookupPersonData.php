<?php

class ZignSecLookupPersonData {

    private array $responseData;
    private string $firstname;
    private string $lastname;
    private string $address;
    private string $zipcode;
    private string $city;
    private string $birthDay;
    private string $birthMonth;
    private string $birthYear;
    private string $sex;
    private ?string $nationality;

    private bool $isActiveNid;
    private bool $wasFound;

    private function __construct(
        array $responseData,
        string $firstname,
        string $lastname,
        string $address,
        string $zipcode,
        string $city,
        string $birthDay,
        string $birthMonth,
        string $birthYear,
        string $sex,
        ?string $nationality,
        bool $isActiveNid,
        bool $wasFound
    ) {
        $this->responseData = $responseData;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->address = $address;
        $this->zipcode = $zipcode;
        $this->city = $city;
        $this->birthDay = $birthDay;
        $this->birthMonth = $birthMonth;
        $this->birthYear = $birthYear;
        $this->sex = $sex;
        $this->nationality = $nationality;
        $this->isActiveNid = $isActiveNid;
        $this->wasFound = $wasFound;
    }

    public static function fromV2(array $responseData, ?string $nationality = null): ZignSecLookupPersonData
    {
        $data = $responseData['Person'];

        $address = $data['Address'];
        if (!empty($data['Address2'])) {
            $address .= " " . $data['Address2'];
        }

        $isActiveNid = empty($data['PersonStatus']) || $data['PersonStatus'] === 'Active';
        $wasFound = $responseData['WasFound'] ?? false;

        return new ZignSecLookupPersonData(
            $responseData,
            $data['FirstName'] ?? '',
            $data['LastName'] ?? '',
            $address ?? '',
            $data['PostalCode'] ?? '',
            $data['City'] ?? '',
            $data['BirthDayOfMonth'] ?? '',
            $data['BirthMonth'] ?? '',
            $data['BirthYear'] ?? '',
            $data['Gender'] ?? '',
            $nationality,
            $isActiveNid,
            $wasFound
        );
    }

    public static function fromV5(array $responseData, ?string $nationality = null): ZignSecLookupPersonData
    {
        $data = $responseData['result']['data']['result'];
        $additional = $data['additional'];
        $basic = $data['basic'];
        $isActiveNid = $data['ssnStatus'] === 'Active';
        $responseCode = $data['responseCode'];
        $wasFound = strtolower($responseCode) === 'ok';

        $sex = $additional['gender'] === 'male' ? 'Male' : 'Female';

        return new ZignSecLookupPersonData(
            $responseData,
            $basic['firstName'] ?? '',
            $basic['lastName'] ?? '',
            $basic['street'] ?? '',
            $basic['zipCode'] ?? '',
            $basic['city'] ?? '',
            $additional['birthDayOfMonth'] ?? '',
            $additional['birthMonth'] ?? '',
            $additional['birthYear'] ?? '',
            $sex,
            $nationality,
            $isActiveNid,
            $wasFound
        );
    }

    public function getDob(): string
    {
        $dob = date("Y-m-d", mktime(0, 0, 0, $this->birthMonth, $this->birthDay, $this->birthYear));

        return $dob ?: '';
    }

    public function getResponseData(): array
    {
        return $this->responseData;
    }

    public function isActiveNid(): bool
    {
        return $this->isActiveNid;
    }

    public function wasFound(): bool
    {
        return $this->wasFound;
    }

    public function toArray(): array
    {
        $data = [
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'address' => $this->address,
            'zipcode' => $this->zipcode,
            'city' => $this->city,
            'birthdate' => $this->birthDay,
            'birthmonth' => $this->birthMonth,
            'birthyear' => $this->birthYear ,
            'dob' => $this->getDob(),
            'sex' => $this->sex
        ];

        if ($this->nationality) {
            $data['nationality'] = $this->nationality;
        }

        return $data;
    }
}
