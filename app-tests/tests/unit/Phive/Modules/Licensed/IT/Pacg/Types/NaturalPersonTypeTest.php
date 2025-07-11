<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Types;

use IT\Pacg\Types\BirthDataType;
use IT\Pacg\Types\DocumentType;
use IT\Pacg\Types\NaturalPersonType;
use IT\Pacg\Types\ResidenceType;

/**
 * Class NaturalPersonTypeTest
 */
class NaturalPersonTypeTest extends AbstractTypeTest
{
    /**
     * @var NaturalPersonType
     */
    protected $stub;

    /**
     * NaturalPersonTypeTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(NaturalPersonType::class)->makePartial();
    }

    public function testSuccessSettingNaturalPersonType()
    {
        $payload = $this->getNaturalPersonPayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('codiceFiscale', $return_to_array));
        $this->assertTrue(array_key_exists('cognome', $return_to_array));
        $this->assertTrue(array_key_exists('nome', $return_to_array));
        $this->assertTrue(array_key_exists('sesso', $return_to_array));
        $this->assertTrue(array_key_exists('nascita', $return_to_array));
        $this->assertTrue(array_key_exists('residenza', $return_to_array));
        $this->assertTrue(array_key_exists('documento', $return_to_array));
        $this->assertTrue(array_key_exists('postaElettronica', $return_to_array));
        $this->assertTrue(array_key_exists('pseudonimo', $return_to_array));

        $this->assertEquals($payload['tax_code'], $return_to_array['codiceFiscale']);
        $this->assertEquals($payload['surname'], $return_to_array['cognome']);
        $this->assertEquals($payload['name'], $return_to_array['nome']);
        $this->assertEquals($payload['gender'], $return_to_array['sesso']);
        $this->assertEquals($payload['email'], $return_to_array['postaElettronica']);
        $this->assertEquals($payload['pseudonym'], $return_to_array['pseudonimo']);

        $this->assertInstanceOf(BirthDataType::class, $this->stub->birth_data);
        $this->assertInstanceOf(ResidenceType::class, $this->stub->residence);
        $this->assertInstanceOf(DocumentType::class, $this->stub->document);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getNaturalPersonPayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(9, $this->stub->errors);
        $this->assertTrue(array_key_exists('tax_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('surname', $this->stub->errors));
        $this->assertTrue(array_key_exists('name', $this->stub->errors));
        $this->assertTrue(array_key_exists('gender', $this->stub->errors));
        $this->assertTrue(array_key_exists('birth_data', $this->stub->errors));
        $this->assertTrue(array_key_exists('residence', $this->stub->errors));
        $this->assertTrue(array_key_exists('document', $this->stub->errors));
        $this->assertTrue(array_key_exists('email', $this->stub->errors));
        $this->assertTrue(array_key_exists('pseudonym', $this->stub->errors));

        unset($payload['tax_code']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('tax_code', $this->stub->errors));
        $this->assertTrue(! array_key_exists('surname', $this->stub->errors));
        $this->assertTrue(! array_key_exists('name', $this->stub->errors));
        $this->assertTrue(! array_key_exists('gender', $this->stub->errors));
        $this->assertTrue(! array_key_exists('birth_data', $this->stub->errors));
        $this->assertTrue(! array_key_exists('residence', $this->stub->errors));
        $this->assertTrue(! array_key_exists('document', $this->stub->errors));
        $this->assertTrue(! array_key_exists('email', $this->stub->errors));
        $this->assertTrue(! array_key_exists('pseudonym', $this->stub->errors));

        unset($payload['surname']);
        $this->stub->validate($payload);
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('tax_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('surname', $this->stub->errors));
        $this->assertTrue(! array_key_exists('name', $this->stub->errors));
        $this->assertTrue(! array_key_exists('gender', $this->stub->errors));
        $this->assertTrue(! array_key_exists('birth_data', $this->stub->errors));
        $this->assertTrue(! array_key_exists('residence', $this->stub->errors));
        $this->assertTrue(! array_key_exists('document', $this->stub->errors));
        $this->assertTrue(! array_key_exists('email', $this->stub->errors));
        $this->assertTrue(! array_key_exists('pseudonym', $this->stub->errors));

        unset($payload['name']);
        $this->stub->validate($payload);
        $this->assertCount(3, $this->stub->errors);
        $this->assertTrue(array_key_exists('tax_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('surname', $this->stub->errors));
        $this->assertTrue(array_key_exists('name', $this->stub->errors));
        $this->assertTrue(! array_key_exists('gender', $this->stub->errors));
        $this->assertTrue(! array_key_exists('birth_data', $this->stub->errors));
        $this->assertTrue(! array_key_exists('residence', $this->stub->errors));
        $this->assertTrue(! array_key_exists('document', $this->stub->errors));
        $this->assertTrue(! array_key_exists('email', $this->stub->errors));
        $this->assertTrue(! array_key_exists('pseudonym', $this->stub->errors));

        unset($payload['gender']);
        $this->stub->validate($payload);
        $this->assertCount(4, $this->stub->errors);
        $this->assertTrue(array_key_exists('tax_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('surname', $this->stub->errors));
        $this->assertTrue(array_key_exists('name', $this->stub->errors));
        $this->assertTrue(array_key_exists('gender', $this->stub->errors));
        $this->assertTrue(! array_key_exists('birth_data', $this->stub->errors));
        $this->assertTrue(! array_key_exists('residence', $this->stub->errors));
        $this->assertTrue(! array_key_exists('document', $this->stub->errors));
        $this->assertTrue(! array_key_exists('email', $this->stub->errors));
        $this->assertTrue(! array_key_exists('pseudonym', $this->stub->errors));

        unset($payload['birth_data']);
        $this->stub->validate($payload);
        $this->assertCount(5, $this->stub->errors);
        $this->assertTrue(array_key_exists('tax_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('surname', $this->stub->errors));
        $this->assertTrue(array_key_exists('name', $this->stub->errors));
        $this->assertTrue(array_key_exists('gender', $this->stub->errors));
        $this->assertTrue(array_key_exists('birth_data', $this->stub->errors));
        $this->assertTrue(! array_key_exists('residence', $this->stub->errors));
        $this->assertTrue(! array_key_exists('document', $this->stub->errors));
        $this->assertTrue(! array_key_exists('email', $this->stub->errors));
        $this->assertTrue(! array_key_exists('pseudonym', $this->stub->errors));

        unset($payload['residence']);
        $this->stub->validate($payload);
        $this->assertCount(6, $this->stub->errors);
        $this->assertTrue(array_key_exists('tax_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('surname', $this->stub->errors));
        $this->assertTrue(array_key_exists('name', $this->stub->errors));
        $this->assertTrue(array_key_exists('gender', $this->stub->errors));
        $this->assertTrue(array_key_exists('birth_data', $this->stub->errors));
        $this->assertTrue(array_key_exists('residence', $this->stub->errors));
        $this->assertTrue(! array_key_exists('document', $this->stub->errors));
        $this->assertTrue(! array_key_exists('email', $this->stub->errors));
        $this->assertTrue(! array_key_exists('pseudonym', $this->stub->errors));

        unset($payload['document']);
        $this->stub->validate($payload);
        $this->assertCount(7, $this->stub->errors);
        $this->assertTrue(array_key_exists('tax_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('surname', $this->stub->errors));
        $this->assertTrue(array_key_exists('name', $this->stub->errors));
        $this->assertTrue(array_key_exists('gender', $this->stub->errors));
        $this->assertTrue(array_key_exists('birth_data', $this->stub->errors));
        $this->assertTrue(array_key_exists('residence', $this->stub->errors));
        $this->assertTrue(array_key_exists('document', $this->stub->errors));
        $this->assertTrue(! array_key_exists('email', $this->stub->errors));
        $this->assertTrue(! array_key_exists('pseudonym', $this->stub->errors));

        unset($payload['email']);
        $this->stub->validate($payload);
        $this->assertCount(8, $this->stub->errors);
        $this->assertTrue(array_key_exists('tax_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('surname', $this->stub->errors));
        $this->assertTrue(array_key_exists('name', $this->stub->errors));
        $this->assertTrue(array_key_exists('gender', $this->stub->errors));
        $this->assertTrue(array_key_exists('birth_data', $this->stub->errors));
        $this->assertTrue(array_key_exists('residence', $this->stub->errors));
        $this->assertTrue(array_key_exists('document', $this->stub->errors));
        $this->assertTrue(array_key_exists('email', $this->stub->errors));
        $this->assertTrue(! array_key_exists('pseudonym', $this->stub->errors));

        unset($payload['pseudonym']);
        $this->stub->validate($payload);
        $this->assertCount(9, $this->stub->errors);
        $this->assertTrue(array_key_exists('tax_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('surname', $this->stub->errors));
        $this->assertTrue(array_key_exists('name', $this->stub->errors));
        $this->assertTrue(array_key_exists('gender', $this->stub->errors));
        $this->assertTrue(array_key_exists('birth_data', $this->stub->errors));
        $this->assertTrue(array_key_exists('residence', $this->stub->errors));
        $this->assertTrue(array_key_exists('document', $this->stub->errors));
        $this->assertTrue(array_key_exists('email', $this->stub->errors));
        $this->assertTrue(array_key_exists('pseudonym', $this->stub->errors));
    }

    public function testgetNaturalPersonPayloadValidateFail()
    {
        $payload = $this->getNaturalPersonPayload();
        $payload['tax_code'] = 'MRCFNZ85P17F205';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('tax_code', $this->stub->errors));

        $payload = $this->getNaturalPersonPayload();
        $payload['surname'] = '';
        for ($i = 0; $i < 10; $i++ ) {
            $payload['surname'] .= '1234567890';
        }
        $payload['surname'] .= '0';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('surname', $this->stub->errors));

        $payload = $this->getNaturalPersonPayload();
        $payload['name'] = '';
        for ($i = 0; $i < 10; $i++ ) {
            $payload['name'] .= '1234567890';
        }
        $payload['name'] .= '0';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('name', $this->stub->errors));

        $payload = $this->getNaturalPersonPayload();
        $payload['gender'] = 'W';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('gender', $this->stub->errors));

        $payload = $this->getNaturalPersonPayload();
        $payload['email'] = 'test';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('email', $this->stub->errors));

        $payload = $this->getNaturalPersonPayload();
        $payload['pseudonym'] = '';
        for ($i = 0; $i < 10; $i++ ) {
            $payload['pseudonym'] .= '1234567890';
        }
        $payload['pseudonym'] .= '0';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('pseudonym', $this->stub->errors));
    }

    public function testErrorToSetDateOfBirthException()
    {
        $payload = $this->getNaturalPersonPayload();
        unset($payload['birth_data']['country']);
        $this->expectException(\Exception::class);
        $this->stub->setBirthData($payload['birth_data']);
    }

    public function testErrorToSetResidenceException()
    {
        $payload = $this->getNaturalPersonPayload();
        unset($payload['residence']['country']);
        $this->expectException(\Exception::class);
        $this->stub->setResidence($payload['residence']);
    }

    public function testErrorToSetDocumentException()
    {
        $payload = $this->getNaturalPersonPayload();
        unset($payload['document']['document_type']);
        $this->expectException(\Exception::class);
        $this->stub->setDocument($payload['document']);
    }

    /**
     * @return array
     */
    private function getNaturalPersonPayload(): array
    {
        return [
            'tax_code' => 'MRCFNZ85P17F205C',
            'surname' => 'ARNALDI',
            'name' => 'test',
            'gender' => 'M',
            'birth_data' => [
                'country' => BirthDataType::ITALY,
                'date_of_birth' => [
                    'day'   => '15',
                    'month' => '10',
                    'year'  => '1965',
                ],
                'birthplace'                  => 'Roma',
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
                'document_type'  => 3,
                'date_of_issue' => [
                    'day' => '02',
                    'month' => '01',
                    'year' => '2018',
                ],
                'document_number' => 'YA1234567',
                'issuing_authority' => 'Ministro Affari Esteri',
                'where_issued' => 'Roma',
            ],
            'email' => 'test@videoslots.com',
            'pseudonym' => 'test',
        ];
    }
}