<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Services;

use IT\Pacg\Services\OpenAccountNaturalPersonEntity;
use IT\Pacg\Types\BirthDataType;
use IT\Pacg\Types\LimitListType;
use IT\Pacg\Types\NaturalPersonType;
use IT\Pacg\Types\ResidenceType;

/**
 * Class OpenAccountNaturalPersonEntityTest
 */
class OpenAccountNaturalPersonEntityTest extends AbstractServiceTest
{
    /**
     * @var OpenAccountNaturalPersonEntity
     */
    protected $stub;

    /**
     * OpenAccountNaturalPersonEntityTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(OpenAccountNaturalPersonEntity::class)->makePartial();
    }

    public function testSuccessSettingOpenAccountNaturalPersonEntity()
    {
        $payload = $this->getOpenAccountNaturalPersonPayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('codiceConto', $return_to_array));
        $this->assertTrue(array_key_exists('titolareConto', $return_to_array));
        $this->assertTrue(array_key_exists('numeroLimiti', $return_to_array));
        $this->assertTrue(array_key_exists('limite', $return_to_array));
        $this->assertTrue(array_key_exists('idTransazione', $return_to_array));

        $this->assertEquals($payload['account_code'], $return_to_array['codiceConto']);

        $this->assertInstanceOf(NaturalPersonType::class, $this->stub->account_holder);
        $this->assertInstanceOf(LimitListType::class, $this->stub->limits);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getOpenAccountNaturalPersonPayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));

        $this->assertCount(3, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('account_holder', $this->stub->errors));
        $this->assertTrue(array_key_exists('limits', $this->stub->errors));

        unset($payload['account_code']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(! array_key_exists('account_holder', $this->stub->errors));
        $this->assertTrue(! array_key_exists('limits', $this->stub->errors));

        unset($payload['account_holder']);
        $this->stub->validate($payload);
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('account_holder', $this->stub->errors));
        $this->assertTrue(! array_key_exists('limits', $this->stub->errors));

        unset($payload['limits']);
        $this->stub->validate($payload);
        $this->assertCount(3, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('account_holder', $this->stub->errors));
        $this->assertTrue(array_key_exists('limits', $this->stub->errors));
    }

    /**
     * @return array
     */
    private function getOpenAccountNaturalPersonPayload(): array
    {
        return [
            'transaction_id' => time(),
            'account_code' => 4002,
            'account_holder' => [
                'tax_code'=> 'RNLNCL65R15H501M',
                'surname' => 'ARNALDI',
                'name' => 'NICCOLO\'',
                'gender' => 'M',
                'email' => 'tester@acme.com',
                'pseudonym' => 'devtestit_2',
                'birth_data' => [
                    'country' => BirthDataType::ITALY,
                    'date_of_birth' => [
                        'day' => '15',
                        'month' => '10',
                        'year' => '1965',
                    ],
                    'birthplace' => 'Roma',
                    'birthplace_province_acronym' => 'RM',
                ],
                'residence' => [
                    'country' => ResidenceType::ITALY,
                    'residential_address' => 'Some street',
                    'municipality_of_residence' => 'Roma',
                    'residential_province_acronym' => 'RM',
                    'residential_post_code' => '12345',
                ],
                'document' => [
                    'document_type' => 3,
                    'date_of_issue' => [
                        'day' => '02',
                        'month' => '01',
                        'year' => '2018',
                    ],
                    'document_number' => 'YA1234567',
                    'issuing_authority' => 'Ministro Affari Esteri',
                    'where_issued' => 'Roma',
                ],
            ],
            'limits' => [
                [
                    'limit_type' => 3,
                    'amount' => 20000,
                ]
            ]
        ];
    }
}