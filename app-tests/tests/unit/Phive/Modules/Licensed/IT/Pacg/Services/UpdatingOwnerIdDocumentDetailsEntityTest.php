<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Services;

use IT\Pacg\Services\UpdatingOwnerIdDocumentDetailsEntity;
use IT\Pacg\Types\DocumentType;

/**
 * Class UpdatingOwnerIdDocumentDetailsEntityTest
 */
class UpdatingOwnerIdDocumentDetailsEntityTest extends AbstractServiceTest
{
    /**
     * @var UpdatingOwnerIdDocumentDetailsEntity
     */
    protected $stub;

    /**
     * UpdatingOwnerIdDocumentDetailsEntityTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(UpdatingOwnerIdDocumentDetailsEntity::class)->makePartial();
    }

    public function testSuccessSettingUpdatingOwnerIdDocumentDetailsEntity()
    {
        $payload = $this->getUpdatingOwnerIdDocumentDetailsPayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('codiceConto', $return_to_array));
        $this->assertTrue(array_key_exists('documento', $return_to_array));
        $this->assertTrue(array_key_exists('idTransazione', $return_to_array));

        $this->assertEquals($payload['account_code'], $return_to_array['codiceConto']);

        $this->assertInstanceOf(DocumentType::class, $this->stub->document);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getUpdatingOwnerIdDocumentDetailsPayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('document', $this->stub->errors));

        unset($payload['account_code']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(! array_key_exists('document', $this->stub->errors));

        unset($payload['document']);
        $this->stub->validate($payload);
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('document', $this->stub->errors));
    }

    public function testErrorToSetDocumentException()
    {
        $payload = $this->getUpdatingOwnerIdDocumentDetailsPayload();
        unset($payload['document']['document_type']);
        $this->expectException(\Exception::class);
        $this->stub->setDocument($payload['document']);
    }

    /**
     * @return array
     */
    private function getUpdatingOwnerIdDocumentDetailsPayload(): array
    {
        return [
            'account_code' => 4002,
            'transaction_id' => time(),
            'document' => [
                'document_type' => 1,
                'date_of_issue'=> [
                    'day' => '01',
                    'month' => '04',
                    'year' => '2020'
                ],
                'document_number' => 'ABC123',
                'issuing_authority' => 'issuing_authority',
                'where_issued' => 'where_issued',
            ],
        ];
    }
}