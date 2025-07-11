<?php
namespace IT\Pacg\Services;

use IT\Abstractions\AbstractEntity;
use IT\Pacg\Types\BonusDetailListType;
use IT\Pacg\Types\DateTimeType;

/**
 * Class AccountMigrationEntity
 * @package IT\Pacg\Services
 */
class AccountMigrationEntity extends PacgService
{
    public $account_code;
    public $account_sales_network_id_destination;
    public $account_network_id_destination;
    public $account_code_destination;
    public $tax_code;
    public $balance_bonus_detail;
    public $transaction_datetime;

    protected $fillable = [
        'account_code',
        'account_sales_network_id_destination',
        'account_network_id_destination',
        'account_code_destination',
        'tax_code',
        'balance_bonus_detail',
    ];

    protected $rules = [
        'account_code' => 'required',
        'account_sales_network_id_destination' => 'required',
        'account_network_id_destination' => 'required',
        'account_code_destination' => 'required',
        'tax_code' => 'required',
        'balance_bonus_detail' => 'required|array',
    ];

    /**
     * @param array $property_values
     * @return AbstractEntity
     */
    public function fill(array $property_values): AbstractEntity
    {
        parent::fill($property_values);

        if (is_array($this->balance_bonus_detail)) {
            $this->balance_bonus_detail = (new BonusDetailListType())->fill(['bonus_detail' => $this->balance_bonus_detail]);
        }
        return $this;
    }

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        $values = [
            "idCnContoOriginario" => $this->account_network_id,
            "idReteContoOriginario" => $this->account_sales_network_id,
            "codiceContoOriginario" => $this->account_code,

            "idReteContoDestinazione" => $this->account_sales_network_id_destination,
            "idCnContoDestinazione" => $this->account_network_id_destination,
            "codiceContoDestinazione" => $this->account_code_destination,

            "codiceFiscale" => $this->tax_code,
            "importoSaldo" => $this->balance_amount,
            "importoBonusSaldo" => $this->balance_bonus_amount,

            "numDettagliBonusSaldo" => $this->balance_bonus_detail->getNumberOfBonuses(),
            "dettaglioBonusSaldo" => $this->balance_bonus_detail->toArray(),
        ];

        return parent::toArray($values);
    }
}