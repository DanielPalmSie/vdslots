<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Services;

use IT\Pacg\Services\TrasversalSelfExclusionManagementEntity;

/**
 * Class TrasversalSelfExclusionManagementEntityTest
 */
class TrasversalSelfExclusionManagementEntityTest extends AbstractServiceTest
{
    /**
     * @var TrasversalSelfExclusionManagementEntity
     */
    protected $stub;

    /**
     * TrasversalSelfExclusionManagementEntityTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(TrasversalSelfExclusionManagementEntity::class)->makePartial();
    }

    public function testSuccessSettingTrasversalSelfExclusionManagementEntity()
    {
        $payload = $this->getTrasversalSelfExclusionManagementPayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('codiceFiscale', $return_to_array));
        $this->assertTrue(array_key_exists('gestioneAutoesclusione', $return_to_array));
        $this->assertTrue(array_key_exists('tipoAutoesclusione', $return_to_array));
        $this->assertTrue(array_key_exists('idTransazione', $return_to_array));

        $this->assertEquals($payload['tax_code'], $return_to_array['codiceFiscale']);
        $this->assertEquals($payload['self_exclusion_management'], $return_to_array['gestioneAutoesclusione']);
        $this->assertEquals($payload['self_exclusion_type'], $return_to_array['tipoAutoesclusione']);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getTrasversalSelfExclusionManagementPayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(3, $this->stub->errors);
        $this->assertTrue(array_key_exists('tax_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('self_exclusion_management', $this->stub->errors));
        $this->assertTrue(array_key_exists('self_exclusion_type', $this->stub->errors));

        unset($payload['tax_code']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('tax_code', $this->stub->errors));
        $this->assertTrue(! array_key_exists('self_exclusion_management', $this->stub->errors));
        $this->assertTrue(! array_key_exists('self_exclusion_type', $this->stub->errors));

        unset($payload['self_exclusion_management']);
        $this->stub->validate($payload);
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('tax_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('self_exclusion_management', $this->stub->errors));
        $this->assertTrue(! array_key_exists('self_exclusion_type', $this->stub->errors));

        unset($payload['self_exclusion_type']);
        $this->stub->validate($payload);
        $this->assertCount(3, $this->stub->errors);
        $this->assertTrue(array_key_exists('tax_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('self_exclusion_management', $this->stub->errors));
        $this->assertTrue(array_key_exists('self_exclusion_type', $this->stub->errors));
    }

    /**
     * @return array
     */
    private function getTrasversalSelfExclusionManagementPayload(): array
    {
        return [
            'tax_code' => 'MRCTST85P17F205C',
            'self_exclusion_management' => '1', //1 for a self-exclusion and 2 for a reactivation
            'self_exclusion_type' => '1',
            'transaction_id' => time()
        ];
    }
}