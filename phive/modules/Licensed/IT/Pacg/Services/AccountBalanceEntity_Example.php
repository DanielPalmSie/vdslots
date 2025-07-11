<?php
namespace IT\Pacg\Services;

/**
 * This example was created to demonstrate usage of Laravel style fillables
 *
 * Class AccountBalanceEntityExample
 */
class AccountBalanceEntityExample extends AbstractService
{

    protected $fillable = [
        'account_code',
        'payment_method',
        'number_bonus_account_details',
        'bonus_balance_share_amount',
        'balance_amount',
        'transaction_amount',
        'transaction_reason',
        'transaction_datetime',
        'account_sales_network_id',
        'bonus_details',
        'account_network_id',

        // from parent
        'transaction_id',
        'network_id'
    ];


    /**
     * @return array
     */
    public function toArray(): array
    {
        $values = [
            "idReteConto"           => $this->account_sales_network_id,
            "idCnConto"             => $this->account_network_id,
            "causaleMovimento"      => $this->transaction_reason,
            "importoMovimento"      => $this->balance_amount,
            "importoSaldo"          => $this->balance_amount,
            "mezzoDiPagamento"      => $this->payment_method,
            "importoBonusSaldo"     => $this->bonus_balance_share_amount,
            "numDettagliBonusSaldo" => $this->number_bonus_account_details,
            "codiceConto"           => $this->account_code,
            "dataOraSaldo"          => $this->transaction_datetime->toArray(),
            "dettaglioBonusSaldo"   => $this->bonus_details->toArray(),
        ];

        return parent::toArray($values);
    }

}
