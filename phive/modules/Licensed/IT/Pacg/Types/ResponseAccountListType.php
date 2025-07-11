<?php
namespace IT\Pacg\Types;

use IT\Abstractions\AbstractEntity;

/**
 * Class ResponseAccountListType
 * @package IT\Pacg\Types
 */
class ResponseAccountListType extends AbstractEntity
{
    /**
     * Accounts
     * @var array
     */
    public $dettaglioConti;

    /**
     * Self excluded accounts
     * @var array
     */
    public $dettaglioContiAutoesclusi;

    /**
     * @var array
     */
    protected $fillable = [
        'dettaglioConti',
        'dettaglioContiAutoesclusi',

    ];

    /**
     * @param array $propertyValues
     * @return AbstractEntity
     * @throws \Exception
     */
    public function fill(array $propertyValues): AbstractEntity
    {
        parent::fill($propertyValues);
        $this->setAccountList();

        return $this;
    }

    /**
     * @throws \Exception
     */
    private function setAccountList()
    {
        $accounts = $this->dettaglioConti;
        $this->dettaglioConti = [];
        if(is_array($accounts)) {
            foreach ($accounts as $key => $account) {
                $this->dettaglioConti[] = (new ResponseAccountType())->fill($account);
            }
        }

        $self_excluded_accounts = $this->dettaglioContiAutoesclusi;
        $this->dettaglioContiAutoesclusi = [];
        if(is_array($self_excluded_accounts)) {
            foreach ($self_excluded_accounts as $key => $account) {
                $this->dettaglioContiAutoesclusi[] = (new ResponseAccountType())->fill($account);
            }
        }

    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getAccountList(): array
    {
        $result_account_list = [];
        foreach ($this->dettaglioConti as $account) {
            $result_account_list[] = $account->toArray();
        }
        foreach ($this->dettaglioContiAutoesclusi as $account) {
            $result_account_list[] = $account->toArray();
        }

        return $result_account_list;
    }

    /**
     * @inheritDoc
     */
    public function toArray(array $array = []): array
    {
        return $this->getAccountList();
    }
}