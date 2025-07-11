<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Services;

use IT\Services\CountriesService;
use Tests\Unit\Phive\Modules\Licensed\IT\Support;

/**
 * Class CountriesServiceTest
 */
class CountriesServiceTest extends Support
{
    /**
     * @var CountriesService
     */
    protected $stub;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->flushAll();
        $this->stub = \Mockery::mock(CountriesService::class)->makePartial();
    }

    public function testFormatCountryReturn()
    {
        $country_data = $this->getCountryFile();
        $format_country_return = self::getAccessibleMethod(
            CountriesService::class,
            'formatCountryReturn'
        );

        $return = $format_country_return->invokeArgs($this->stub, [$country_data]);
        $expected_return = [
            'iso' => 'iso',
            'name' => 'NAME',
            'printable_name' => 'name',
            'iso3' => 'iso3',
            'numcode' => 'numcode'
        ];

        $this->assertEquals($expected_return, $return);
    }

    public function testGetCountries()
    {
        $country_data_base = $this->getCountryDataBase();
        $countries_data_base = [$country_data_base];

        $country_file = $this->getCountryFile();
        $countries_file = [$country_file];

        $this->stub = \Mockery::mock(CountriesService::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $this->stub->shouldReceive(
            [
                'getCountries' => $countries_data_base,
                'load' => $countries_file
            ]
        );

        $result = $this->stub->getCountries();
        $country_data_base_expected = $this->removeUselessFields($country_data_base);
        $expected_countries = [$country_data_base_expected];

        $this->assertEquals($expected_countries, $result);
    }

    public function testGetCeasedCountries()
    {
        $country_file = $this->getCountryFile();
        $countries_file = [$country_file];

        $this->stub = \Mockery::mock(CountriesService::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $this->stub->shouldReceive(['load' => $countries_file]);

        $result = $this->stub->getCeasedCountries();
        $this->assertEquals($countries_file, $result);
    }

    /**
     * @param array $country
     * @return array
     */
    private function removeUselessFields(array $country): array
    {
        unset($country['calling_code']);
        unset($country['reg_age']);
        unset($country['sms_price']);
        unset($country['sms_currency']);
        unset($country['tax']);
        unset($country['vat']);
        unset($country['ded_pc']);

        return $country;
    }

    /**
     * @return array
     */
    private function getCountryDataBase(): array
    {
        return [
            [
                'iso' => 'iso',
                'name' => 'NAME_ENGLISH',
                'printable_name' => 'name_english',
                'iso3' => 'iso3',
                'territory_state' => 'numcode',
                'calling_code' => 'calling_code',
                'reg_age' => 'reg_age',
                'sms_price' => 'sms_price',
                'sms_currency' => 'sms_currency',
                'tax' => 'tax',
                'vat' => 'vat',
                'ded_pc' => 'ded_pc',
            ]
        ];
    }

    /**
     * @return array
     */
    private function getCountryFile(): array
    {
        return [
            'iso' => 'iso',
            'name' => 'name',
            'iso3' => 'iso3',
            'territory_state' => 'numcode'
        ];
    }
}