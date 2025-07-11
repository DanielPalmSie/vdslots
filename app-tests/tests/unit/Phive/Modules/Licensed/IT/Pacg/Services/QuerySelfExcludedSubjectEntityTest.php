<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Services;

use IT\Pacg\Services\QuerySelfExcludedSubjectEntity;

/**
 * Class QuerySelfExcludedSubjectEntityTest
 */
class QuerySelfExcludedSubjectEntityTest extends AbstractServiceTest
{
    /**
     * @var QuerySelfExcludedSubjectEntity
     */
    protected $stub;

    /**
     * QuerySelfExcludedSubjectEntityTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(QuerySelfExcludedSubjectEntity::class)->makePartial();
    }

    public function testSuccessSettingOpenAccountNaturalPersonEntity()
    {
        $payload = $this->getQuerySelfExcludedSubjectPayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('codiceFiscale', $return_to_array));
        $this->assertTrue(array_key_exists('idTransazione', $return_to_array));

        $this->assertEquals($payload['tax_code'], $return_to_array['codiceFiscale']);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getQuerySelfExcludedSubjectPayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('tax_code', $this->stub->errors));

        unset($payload['tax_code']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('tax_code', $this->stub->errors));
    }

    /**
     * @return array
     */
    private function getQuerySelfExcludedSubjectPayload(): array
    {
        return [
            'tax_code' => 'MRCFNZ85P17F205C',
            'transaction_id' => time()
        ];
    }
}