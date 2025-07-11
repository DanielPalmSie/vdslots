<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Services;

use IT\Pacg\Services\AccountTransactionsReversalEntity;
use IT\Pacg\Types\BonusDetailListType;
use IT\Pacg\Types\DateTimeType;

/**
 * Class AccountTransactionsReversalEntityTest
 */
class AccountTransactionsReversalEntityTest extends AbstractServiceTest
{
    /**
     * @var AccountTransactionsReversalEntity
     */
    protected $stub;

    /**
     * AccountTransactionsReversalEntityTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(AccountTransactionsReversalEntity::class)->makePartial();
    }

    public function testSuccessSettingAccountTransactionsReversalEntity()
    {
        $payload = $this->getAccountTransactionsReversalPayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('codiceConto', $return_to_array));
        $this->assertTrue(array_key_exists('idMovDaStornare', $return_to_array));
        $this->assertTrue(array_key_exists('mezzoDiPagamento', $return_to_array));
        $this->assertTrue(array_key_exists('causaleMovimento', $return_to_array));
        $this->assertTrue(array_key_exists('tipoStorno', $return_to_array));
        $this->assertTrue(array_key_exists('importoMovimento', $return_to_array));
        $this->assertTrue(array_key_exists('importoSaldo', $return_to_array));
        $this->assertTrue(array_key_exists('importoBonusSaldo', $return_to_array));

        $this->assertEquals($payload['account_code'], $return_to_array['codiceConto']);
        $this->assertEquals($payload['transaction_receipt_id'], $return_to_array['idMovDaStornare']);
        $this->assertEquals($payload['payment_method'], $return_to_array['mezzoDiPagamento']);
        $this->assertEquals($payload['transaction_description'], $return_to_array['causaleMovimento']);
        $this->assertEquals($payload['reversal_type'], $return_to_array['tipoStorno']);
        $this->assertEquals($payload['transaction_amount'], $return_to_array['importoSaldo']);
        $this->assertEquals($payload['balance_amount'], $return_to_array['importoSaldo']);
        $this->assertEquals($payload['balance_bonus_amount'], $return_to_array['importoBonusSaldo']);

        $this->assertInstanceOf(BonusDetailListType::class, $this->stub->balance_bonus_detail);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getAccountTransactionsReversalPayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(7, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_receipt_id', $this->stub->errors));
        $this->assertTrue(array_key_exists('payment_method', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_description', $this->stub->errors));
        $this->assertTrue(array_key_exists('reversal_type', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_amount', $this->stub->errors));
        $this->assertTrue(array_key_exists('balance_amount', $this->stub->errors));

        unset($payload['account_code']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(! array_key_exists('transaction_receipt_id', $this->stub->errors));
        $this->assertTrue(! array_key_exists('payment_method', $this->stub->errors));
        $this->assertTrue(! array_key_exists('transaction_description', $this->stub->errors));
        $this->assertTrue(! array_key_exists('reversal_type', $this->stub->errors));
        $this->assertTrue(! array_key_exists('transaction_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('balance_amount', $this->stub->errors));


        unset($payload['transaction_receipt_id']);
        $this->stub->validate($payload);
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_receipt_id', $this->stub->errors));
        $this->assertTrue(! array_key_exists('payment_method', $this->stub->errors));
        $this->assertTrue(! array_key_exists('transaction_description', $this->stub->errors));
        $this->assertTrue(! array_key_exists('reversal_type', $this->stub->errors));
        $this->assertTrue(! array_key_exists('transaction_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('balance_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('balance_bonus_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('balance_bonus_detail', $this->stub->errors));

        unset($payload['payment_method']);
        $this->stub->validate($payload);
        $this->assertCount(3, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_receipt_id', $this->stub->errors));
        $this->assertTrue(array_key_exists('payment_method', $this->stub->errors));
        $this->assertTrue(! array_key_exists('transaction_description', $this->stub->errors));
        $this->assertTrue(! array_key_exists('reversal_type', $this->stub->errors));
        $this->assertTrue(! array_key_exists('transaction_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('balance_amount', $this->stub->errors));

        unset($payload['transaction_description']);
        $this->stub->validate($payload);
        $this->assertCount(4, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_receipt_id', $this->stub->errors));
        $this->assertTrue(array_key_exists('payment_method', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_description', $this->stub->errors));
        $this->assertTrue(! array_key_exists('reversal_type', $this->stub->errors));
        $this->assertTrue(! array_key_exists('transaction_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('balance_amount', $this->stub->errors));

        unset($payload['reversal_type']);
        $this->stub->validate($payload);
        $this->assertCount(5, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_receipt_id', $this->stub->errors));
        $this->assertTrue(array_key_exists('payment_method', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_description', $this->stub->errors));
        $this->assertTrue(array_key_exists('reversal_type', $this->stub->errors));
        $this->assertTrue(! array_key_exists('transaction_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('balance_amount', $this->stub->errors));

        unset($payload['transaction_amount']);
        $this->stub->validate($payload);
        $this->assertCount(6, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_receipt_id', $this->stub->errors));
        $this->assertTrue(array_key_exists('payment_method', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_description', $this->stub->errors));
        $this->assertTrue(array_key_exists('reversal_type', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('balance_amount', $this->stub->errors));

        unset($payload['balance_amount']);
        $this->stub->validate($payload);
        $this->assertCount(7, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_receipt_id', $this->stub->errors));
        $this->assertTrue(array_key_exists('payment_method', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_description', $this->stub->errors));
        $this->assertTrue(array_key_exists('reversal_type', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_amount', $this->stub->errors));
        $this->assertTrue(array_key_exists('balance_amount', $this->stub->errors));

    }

    public function testErrorToSetBalanceBonusDetailException()
    {
        $payload = $this->getAccountTransactionsReversalPayload();
        unset($payload['balance_bonus_detail'][0]['gaming_family']);
        $this->expectException(\Exception::class);
        $this->stub->setBonusDetail($payload['balance_bonus_detail']);
    }

    public function testErrorToSetDateException()
    {
        $payload = $this->getAccountTransactionsReversalPayload();
        unset($payload['datetime']['date']['day']);
        $this->expectException(\Exception::class);
        $this->stub->setDatetime($payload['datetime']);
    }

    public function testErrorToSetTimeException()
    {
        $payload = $this->getAccountTransactionsReversalPayload();
        unset($payload['datetime']['time']['minutes']);
        $this->expectException(\Exception::class);
        $this->stub->setDatetime($payload['datetime']);
    }

    /**
     * @return array
     */
    private function getAccountTransactionsReversalPayload(): array
    {
        return [
            'account_code' => 4002,
            'transaction_receipt_id' => '1234567890123456789012345',
            'payment_method' => '3',
            'transaction_description' => '2',
            'reversal_type' => '1',
            'transaction_amount' => '1',
            'balance_amount' => '1',
            'balance_bonus_amount' => '1',
            'balance_bonus_detail' => [
                [
                    'gaming_family' => '1',
                    'gaming_type' => '1',
                    'bonus_amount' => '30',
                ]
            ],
            'datetime' => [
                'date' => [
                    'day' => '01',
                    'month' => '04',
                    'year' => '2020'
                ],
                'time' => [
                    'hours' => '01',
                    'minutes' => '05',
                    'seconds' => '00'
                ]
            ],
            'transaction_id' => time(),
        ];
    }
}