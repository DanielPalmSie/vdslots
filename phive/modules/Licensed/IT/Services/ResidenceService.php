<?php
namespace IT\Services;

/**
 * Class ResidenceService
 * @package IT\Services
 */
class ResidenceService
{
    /**
     * @var CountriesService
     */
    private $countries_service;

    /**
     * @var ProvincesService
     */
    private $provinces_service;

    /**
     * @return CountriesService
     */
    protected function getCountriesService(): CountriesService
    {
        if (empty($this->countries_service)) {
            $this->countries_service = new CountriesService();
        }

        return $this->countries_service;
    }

    /**
     * @return ProvincesService
     */
    protected function getProvinceService(): ProvincesService
    {
        if (empty($this->provinces_service)) {
            $this->provinces_service = new ProvincesService();
        }

        return $this->provinces_service;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getMunicipalityCodes(): array
    {
        $provinces_service = $this->getProvinceService();
        return $provinces_service->getProvinceBySelectedKeyList('cadastral_code_municipality');
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getMunicipalityByProvinceList(): array
    {
        $provinces_service = $this->getProvinceService();
        return $provinces_service->getMunicipalityByProvinceList();
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getProvinceList(): array
    {
        $provinces_service = $this->getProvinceService();
        return $provinces_service->getProvinceList();
    }


    /**
     * @param $registry_code
     * @return array
     * @throws \Exception
     */
    public function getMunicipalityDetail(string $registry_code): array
    {
        $data = $this->getMunicipalityCodes();

        if (! array_key_exists($registry_code, $data)) {
            return [
                'error' => true,
                'message' => 'Code does not exist',
            ];
        }

        return $data[$registry_code];
    }

    /**
     * @param bool $is_english
     * @return array
     * @throws \Exception
     */
    public function getCountries(bool $is_english = true): array
    {
        $countriesService = $this->getCountriesService();
        return $countriesService->getCountries($is_english);
    }
}