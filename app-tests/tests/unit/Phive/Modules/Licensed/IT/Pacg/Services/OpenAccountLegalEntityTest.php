<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Services;

use IT\Pacg\Services\OpenAccountLegalEntity;
use IT\Pacg\Types\LegalEntityType;
use IT\Pacg\Types\ResidenceType;

/**
 * Class OpenAccountLegalEntityTest
 */
class OpenAccountLegalEntityTest extends AbstractServiceTest
{
    /**
     * @var OpenAccountLegalEntity
     */
    protected $stub;

    /**
     * OpenAccountLegalEntityTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(OpenAccountLegalEntity::class)->makePartial();
    }

    public function testSuccessSettingOpenAccountLegalEntity()
    {
        $payload = $this->getOpenAccountLegalPayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('codiceConto', $return_to_array));
        $this->assertTrue(array_key_exists('titolareConto', $return_to_array));
        $this->assertTrue(array_key_exists('idTransazione', $return_to_array));

        $this->assertEquals($payload['account_code'], $return_to_array['codiceConto']);

        $this->assertInstanceOf(LegalEntityType::class, $this->stub->account_holder);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getOpenAccountLegalPayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('account_holder', $this->stub->errors));

        unset($payload['account_code']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(! array_key_exists('account_holder', $this->stub->errors));

        unset($payload['account_holder']);
        $this->stub->validate($payload);
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('account_holder', $this->stub->errors));
    }

    public function testErrorToSetAccountHoldertException()
    {
        $payload = $this->getOpenAccountLegalPayload();
        unset($payload['account_holder']['vat_number']);
        $this->expectException(\Exception::class);
        $this->stub->setAccountHolder($payload['account_holder']);
    }

    /**
     * @return array
     */
    private function getOpenAccountLegalPayload(): array
    {
        return [
            'account_code' => 4002,
            'account_holder' => [
                'vat_number' => '12345678901',
                'company_name' => 'test',
                'company_headquarter' => [
                    'country' => ResidenceType::ITALY,
                    'residential_address'          => 'Some street',
                    'municipality_of_residence'    => 'Roma',
                    'residential_province_acronym' => 'RM',
                    'residential_post_code'        => '12345',
                ],
                'email' => 'test@videoslots.com',
                'pseudonym' => 'test',
            ],
            'transaction_id' => time(),
        ];
    }
}