<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Services;

use IT\Pacg\Services\AccountBonusTransactionsEntity;
use IT\Pacg\Types\BonusDetailListType;
use IT\Pacg\Types\DateTimeType;

/**
 * Class AccountBonusTransactionsEntityTest
 */
class AccountBonusTransactionsEntityTest extends AbstractServiceTest
{
    /**
     * @var AccountBonusTransactionsEntity
     */
    protected $stub;

    /**
     * AccountBonusTransactionsEntityTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(AccountBonusTransactionsEntity::class)->makePartial();
    }

    public function testSuccessSettingAccountBonusTransactionsEntity()
    {
        $payload = $this->getAccountBonusTransactionsPayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('codiceConto', $return_to_array));
        $this->assertTrue(array_key_exists('causaleMovimento', $return_to_array));
        $this->assertTrue(array_key_exists('importoSaldo', $return_to_array));
        $this->assertTrue(array_key_exists('importoBonus', $return_to_array));
        $this->assertTrue(array_key_exists('importoBonusSaldo', $return_to_array));
        $this->assertTrue(array_key_exists('numDettagliBonus', $return_to_array));
        $this->assertTrue(array_key_exists('dettaglioBonus', $return_to_array));
        $this->assertTrue(array_key_exists('numDettagliBonusSaldo', $return_to_array));
        $this->assertTrue(array_key_exists('numDettagliBonusSaldo', $return_to_array));
        $this->assertTrue(array_key_exists('idTransazione', $return_to_array));

        $this->assertEquals($payload['account_code'], $return_to_array['codiceConto']);
        $this->assertEquals($payload['transaction_reason'], $return_to_array['causaleMovimento']);
        $this->assertEquals($payload['balance_amount'], $return_to_array['importoSaldo']);
        $this->assertEquals($payload['bonus_balance_amount'], $return_to_array['importoBonus']);
        $this->assertEquals($payload['total_bonus_balance_on_account'], $return_to_array['importoBonusSaldo']);

        $this->assertInstanceOf(BonusDetailListType::class, $this->stub->bonus_details);
        $this->assertInstanceOf(BonusDetailListType::class, $this->stub->bonus_balance_details);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getAccountBonusTransactionsPayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(8, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('total_bonus_balance_on_account', $this->stub->errors));
        $this->assertTrue(array_key_exists('bonus_balance_amount', $this->stub->errors));
        $this->assertTrue(array_key_exists('balance_amount', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_reason', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_amount', $this->stub->errors));
        $this->assertTrue(array_key_exists('bonus_details', $this->stub->errors));
        $this->assertTrue(array_key_exists('bonus_balance_details', $this->stub->errors));

        unset($payload['account_code']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(! array_key_exists('total_bonus_balance_on_account', $this->stub->errors));
        $this->assertTrue(! array_key_exists('bonus_balance_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('balance_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('transaction_reason', $this->stub->errors));
        $this->assertTrue(! array_key_exists('transaction_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('bonus_details', $this->stub->errors));
        $this->assertTrue(! array_key_exists('bonus_balance_details', $this->stub->errors));

        unset($payload['total_bonus_balance_on_account']);
        $this->stub->validate($payload);
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('total_bonus_balance_on_account', $this->stub->errors));
        $this->assertTrue(! array_key_exists('bonus_balance_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('balance_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('transaction_reason', $this->stub->errors));
        $this->assertTrue(! array_key_exists('transaction_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('bonus_details', $this->stub->errors));
        $this->assertTrue(! array_key_exists('bonus_balance_details', $this->stub->errors));

        unset($payload['bonus_balance_amount']);
        $this->stub->validate($payload);
        $this->assertCount(3, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('total_bonus_balance_on_account', $this->stub->errors));
        $this->assertTrue(array_key_exists('bonus_balance_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('balance_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('transaction_reason', $this->stub->errors));
        $this->assertTrue(! array_key_exists('transaction_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('bonus_details', $this->stub->errors));
        $this->assertTrue(! array_key_exists('bonus_balance_details', $this->stub->errors));

        unset($payload['balance_amount']);
        $this->stub->validate($payload);
        $this->assertCount(4, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('total_bonus_balance_on_account', $this->stub->errors));
        $this->assertTrue(array_key_exists('bonus_balance_amount', $this->stub->errors));
        $this->assertTrue(array_key_exists('balance_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('transaction_reason', $this->stub->errors));
        $this->assertTrue(! array_key_exists('transaction_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('bonus_details', $this->stub->errors));
        $this->assertTrue(! array_key_exists('bonus_balance_details', $this->stub->errors));

        unset($payload['transaction_reason']);
        $this->stub->validate($payload);
        $this->assertCount(5, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('total_bonus_balance_on_account', $this->stub->errors));
        $this->assertTrue(array_key_exists('bonus_balance_amount', $this->stub->errors));
        $this->assertTrue(array_key_exists('balance_amount', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_reason', $this->stub->errors));
        $this->assertTrue(! array_key_exists('transaction_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('bonus_details', $this->stub->errors));
        $this->assertTrue(! array_key_exists('bonus_balance_details', $this->stub->errors));

        unset($payload['transaction_amount']);
        $this->stub->validate($payload);
        $this->assertCount(6, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('total_bonus_balance_on_account', $this->stub->errors));
        $this->assertTrue(array_key_exists('bonus_balance_amount', $this->stub->errors));
        $this->assertTrue(array_key_exists('balance_amount', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_reason', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('bonus_details', $this->stub->errors));
        $this->assertTrue(! array_key_exists('bonus_balance_details', $this->stub->errors));

        unset($payload['bonus_details']);
        $this->stub->validate($payload);
        $this->assertCount(7, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('total_bonus_balance_on_account', $this->stub->errors));
        $this->assertTrue(array_key_exists('bonus_balance_amount', $this->stub->errors));
        $this->assertTrue(array_key_exists('balance_amount', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_reason', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_amount', $this->stub->errors));
        $this->assertTrue(array_key_exists('bonus_details', $this->stub->errors));
        $this->assertTrue(! array_key_exists('bonus_balance_details', $this->stub->errors));

        unset($payload['bonus_balance_details']);
        $this->stub->validate($payload);
        $this->assertCount(8, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('total_bonus_balance_on_account', $this->stub->errors));
        $this->assertTrue(array_key_exists('bonus_balance_amount', $this->stub->errors));
        $this->assertTrue(array_key_exists('balance_amount', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_reason', $this->stub->errors));
        $this->assertTrue(array_key_exists('transaction_amount', $this->stub->errors));
        $this->assertTrue(array_key_exists('bonus_details', $this->stub->errors));
        $this->assertTrue(array_key_exists('bonus_balance_details', $this->stub->errors));
    }

    public function testErrorToSetBonusDetailException()
    {
        $payload = $this->getAccountBonusTransactionsPayload();
        unset($payload['bonus_details'][0]['gaming_family']);
        $this->expectException(\Exception::class);
        $this->stub->setBonusDetails($payload['bonus_details']);
    }

    public function testErrorToSetBonusBalanceDetailsException()
    {
        $payload = $this->getAccountBonusTransactionsPayload();
        unset($payload['bonus_balance_details'][0]['gaming_family']);
        $this->expectException(\Exception::class);
        $this->stub->setBonusDetails($payload['bonus_balance_details']);
    }

    /**
     * @return array
     */
    private function getAccountBonusTransactionsPayload(): array
    {
        return [
            'account_code' => 4002,
            'payment_method' => '1',
            'total_bonus_balance_on_account' => '30',
            'bonus_balance_amount' => '200',
            'balance_amount' => '200',
            'transaction_reason' => '5',
            'transaction_amount' => '200',
            'account_sales_network_id' => '123',
            'bonus_details' => [
                [
                    'gaming_family' => '1',
                    'gaming_type' => '1',
                    'bonus_amount' => '30',
                ]
            ],
            'bonus_balance_details' => [
                [
                    'gaming_family' => '1',
                    'gaming_type' => '1',
                    'bonus_amount' => '30',
                ]
            ],
        ];
    }
}