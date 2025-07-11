<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Services;

use IT\Pacg\Services\AccountTransactionsEntity;
use IT\Pacg\Types\BonusDetailListType;
use IT\Pacg\Types\DateTimeType;

/**
 * Class AccountTransactionsEntityTest
 */
class AccountTransactionsEntityTest extends AbstractServiceTest
{
    /**
     * @var AccountTransactionsEntity
     */
    protected $stub;

    /**
     * AccountTransactionsEntityTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(AccountTransactionsEntity::class)->makePartial();
    }

    public function testSuccessSettingAccountTransactionsEntity()
    {
        $payload = $this->getAccountTransactionsPayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('codiceConto', $return_to_array));
        $this->assertTrue(array_key_exists('causaleMovimento', $return_to_array));
        $this->assertTrue(array_key_exists('importoMovimento', $return_to_array));
        $this->assertTrue(array_key_exists('importoSaldo', $return_to_array));
        $this->assertTrue(array_key_exists('mezzoDiPagamento', $return_to_array));
        $this->assertTrue(array_key_exists('importoBonusSaldo', $return_to_array));
        $this->assertTrue(array_key_exists('numDettagliBonusSaldo', $return_to_array));
        $this->assertTrue(array_key_exists('dettaglioBonusSaldo', $return_to_array));

        $this->assertEquals($payload['account_code'], $return_to_array['codiceConto']);
        $this->assertEquals($payload['transaction_reason'], $return_to_array['causaleMovimento']);
        $this->assertEquals($payload['balance_amount'], $return_to_array['importoMovimento']);
        $this->assertEquals($payload['balance_amount'], $return_to_array['importoSaldo']);
        $this->assertEquals($payload['payment_method'], $return_to_array['mezzoDiPagamento']);
        $this->assertEquals($payload['total_bonus_balance_on_account'], $return_to_array['importoBonusSaldo']);

        $this->assertInstanceOf(BonusDetailListType::class, $this->stub->bonus_details);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getAccountTransactionsPayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(6, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_reason', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_amount', $this->stub->errors));
        $this->assertTrue(array_key_exists('balance_amount', $this->stub->errors));
        $this->assertTrue(array_key_exists('total_bonus_balance_on_account', $this->stub->errors));
        $this->assertTrue(array_key_exists('payment_method', $this->stub->errors));

        unset($payload['account_code']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(! array_key_exists('transaction_reason', $this->stub->errors));
        $this->assertTrue(! array_key_exists('transaction_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('balance_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('total_bonus_balance_on_account', $this->stub->errors));
        $this->assertTrue(! array_key_exists('payment_method', $this->stub->errors));

        unset($payload['transaction_reason']);
        $this->stub->validate($payload);
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_reason', $this->stub->errors));
        $this->assertTrue(! array_key_exists('transaction_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('balance_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('total_bonus_balance_on_account', $this->stub->errors));
        $this->assertTrue(! array_key_exists('payment_method', $this->stub->errors));

        unset($payload['transaction_amount']);
        $this->stub->validate($payload);
        $this->assertCount(3, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_reason', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('balance_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('total_bonus_balance_on_account', $this->stub->errors));
        $this->assertTrue(! array_key_exists('payment_method', $this->stub->errors));

        unset($payload['balance_amount']);
        $this->stub->validate($payload);
        $this->assertCount(4, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_reason', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_amount', $this->stub->errors));
        $this->assertTrue(array_key_exists('balance_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('total_bonus_balance_on_account', $this->stub->errors));
        $this->assertTrue(! array_key_exists('payment_method', $this->stub->errors));

        unset($payload['total_bonus_balance_on_account']);
        $this->stub->validate($payload);
        $this->assertCount(5, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_reason', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_amount', $this->stub->errors));
        $this->assertTrue(array_key_exists('balance_amount', $this->stub->errors));
        $this->assertTrue(array_key_exists('total_bonus_balance_on_account', $this->stub->errors));
        $this->assertTrue(! array_key_exists('payment_method', $this->stub->errors));

        unset($payload['payment_method']);
        $this->stub->validate($payload);
        $this->assertCount(6, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_reason', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_amount', $this->stub->errors));
        $this->assertTrue(array_key_exists('balance_amount', $this->stub->errors));
        $this->assertTrue(array_key_exists('total_bonus_balance_on_account', $this->stub->errors));
        $this->assertTrue(array_key_exists('payment_method', $this->stub->errors));
    }

    public function testErrorToSetTransactionDateException()
    {
        $payload = $this->getAccountTransactionsPayload();
        unset($payload['transaction_datetime']['date']['day']);
        $this->expectException(\Exception::class);
        $this->stub->setTransactionDateTime($payload['transaction_datetime']);
    }

    public function testErrorToSetTransactionTimeException()
    {
        $payload = $this->getAccountTransactionsPayload();
        unset($payload['transaction_datetime']['time']['minutes']);
        $this->expectException(\Exception::class);
        $this->stub->setTransactionDateTime($payload['transaction_datetime']);
    }

    public function testErrorToSetBalanceBonusDetailException()
    {
        $payload = $this->getAccountTransactionsPayload();
        unset($payload['bonus_details'][0]['gaming_family']);
        $this->expectException(\Exception::class);
        $this->stub->setBonusDetails($payload['bonus_details']);
    }

    /**
     * @return array
     */
    private function getAccountTransactionsPayload(): array
    {
        return [
            'account_code' => 4002,
            'account_sales_network_id' => '14',
            'transaction_reason' => '1',
            'transaction_amount' => '200',
            'balance_amount' => '200',
            'total_bonus_balance_on_account' => '30',
            'payment_method' => '1',
            'bonus_details' => [
                [
                    'gaming_family' => '1',
                    'gaming_type' => '1',
                    'bonus_amount' => '30',
                ]
            ],
        ];
    }
}