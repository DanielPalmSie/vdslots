<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Types;

use IT\Pacg\Types\DateType;
use IT\Pacg\Types\DocumentType;

/**
 * Class DocumentTypeTest
 */
class DocumentTypeTest extends AbstractTypeTest
{
    /**
     * @var DocumentType
     */
    protected $stub;

    /**
     * DocumentTypeTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(DocumentType::class)->makePartial();
    }

    public function testSuccessSettingDocumentType()
    {
        $payload = $this->getDocumentPayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('tipo', $return_to_array));
        $this->assertTrue(array_key_exists('numero', $return_to_array));
        $this->assertTrue(array_key_exists('dataRilascio', $return_to_array));
        $this->assertTrue(array_key_exists('autoritaRilascio', $return_to_array));
        $this->assertTrue(array_key_exists('localitaRilascio', $return_to_array));

        $this->assertEquals($payload['document_type'], $return_to_array['tipo']);
        $this->assertEquals($payload['document_number'], $return_to_array['numero']);
        $this->assertEquals($payload['issuing_authority'], $return_to_array['autoritaRilascio']);
        $this->assertEquals($payload['where_issued'], $return_to_array['localitaRilascio']);
        $this->assertInstanceOf(DateType::class, $this->stub->date_of_issue);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getDocumentPayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(5, $this->stub->errors);
        $this->assertTrue(array_key_exists('document_type', $this->stub->errors));
        $this->assertTrue(array_key_exists('document_number', $this->stub->errors));
        $this->assertTrue(array_key_exists('issuing_authority', $this->stub->errors));
        $this->assertTrue(array_key_exists('where_issued', $this->stub->errors));
        $this->assertTrue(array_key_exists('date_of_issue', $this->stub->errors));

        unset($payload['document_type']);
        $this->stub->validate($payload);

        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('document_type', $this->stub->errors));
        $this->assertTrue(! array_key_exists('document_number', $this->stub->errors));
        $this->assertTrue(! array_key_exists('issuing_authority', $this->stub->errors));
        $this->assertTrue(! array_key_exists('where_issued', $this->stub->errors));
        $this->assertTrue(! array_key_exists('date_of_issue', $this->stub->errors));

        unset($payload['document_number']);
        $this->stub->validate($payload);
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('document_type', $this->stub->errors));
        $this->assertTrue(array_key_exists('document_number', $this->stub->errors));
        $this->assertTrue(! array_key_exists('issuing_authority', $this->stub->errors));
        $this->assertTrue(! array_key_exists('where_issued', $this->stub->errors));
        $this->assertTrue(! array_key_exists('date_of_issue', $this->stub->errors));

        unset($payload['issuing_authority']);
        $this->stub->validate($payload);
        $this->assertCount(3, $this->stub->errors);
        $this->assertTrue(array_key_exists('document_type', $this->stub->errors));
        $this->assertTrue(array_key_exists('document_number', $this->stub->errors));
        $this->assertTrue(array_key_exists('issuing_authority', $this->stub->errors));
        $this->assertTrue(! array_key_exists('where_issued', $this->stub->errors));
        $this->assertTrue(! array_key_exists('date_of_issue', $this->stub->errors));

        unset($payload['where_issued']);
        $this->stub->validate($payload);
        $this->assertCount(4, $this->stub->errors);
        $this->assertTrue(array_key_exists('document_type', $this->stub->errors));
        $this->assertTrue(array_key_exists('document_number', $this->stub->errors));
        $this->assertTrue(array_key_exists('issuing_authority', $this->stub->errors));
        $this->assertTrue(array_key_exists('where_issued', $this->stub->errors));
        $this->assertTrue(! array_key_exists('date_of_issue', $this->stub->errors));

        unset($payload['date_of_issue']);
        $this->stub->validate($payload);
        $this->assertCount(5, $this->stub->errors);
        $this->assertTrue(array_key_exists('document_type', $this->stub->errors));
        $this->assertTrue(array_key_exists('document_number', $this->stub->errors));
        $this->assertTrue(array_key_exists('issuing_authority', $this->stub->errors));
        $this->assertTrue(array_key_exists('where_issued', $this->stub->errors));
        $this->assertTrue(array_key_exists('date_of_issue', $this->stub->errors));
    }

    public function testDocumentPayloadValidateFail()
    {
        $payload = $this->getDocumentPayload();
        $payload['document_type'] = '11';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('document_type', $this->stub->errors));

        $payload = $this->getDocumentPayload();
        $payload['document_number'] = '123456789012345678901';
        $this->stub->validate($payload);

        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('document_number', $this->stub->errors));

        $payload = $this->getDocumentPayload();
        $payload['issuing_authority'] = '';
        for ($i = 0; $i < 10; $i++ ) {
            $payload['issuing_authority'] .= '1234567890';
        }
        $payload['issuing_authority'] .= '0';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('issuing_authority', $this->stub->errors));


        $payload = $this->getDocumentPayload();
        $payload['where_issued'] = '';
        for ($i = 0; $i < 10; $i++ ) {
            $payload['where_issued'] .= '1234567890';
        }
        $payload['where_issued'] .= '0';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('where_issued', $this->stub->errors));
    }

    /**
     * @return array
     */
    private function getDocumentPayload(): array
    {
        return  [
            'document_type' => 1,
            'date_of_issue'=> [
                'day' => '01',
                'month' => '04',
                'year' => '2020'
            ],
            'document_number' => 'ABC123',
            'issuing_authority' => 'issuing authority',
            'where_issued' => 'whereissued',
        ];
    }
}