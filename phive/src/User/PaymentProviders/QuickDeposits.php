<?php

declare(strict_types=1);

namespace Videoslots\User\PaymentProviders;

use \CasinoCashier;

class QuickDeposits
{
    private $user;
    private CasinoCashier $cashier;

    const DISPLAY_COUNT = 2;

    public function __construct($user)
    {
        $this->user = $user;
        $this->cashier = phive('Cashier');
    }

    public function getRecurringDeposits(): array
    {
        $mts = new \Mts('', $this->user);

        $recurring_deposits = $mts->rpc(
            'query',
            'recurring',
            'getAllForRepeat',
            [
                'user_id' => $this->user->getId(),
                'max_amount' => $this->cashier->max_quick_amount
            ]
        );

        return empty($recurring_deposits) ? [] : $recurring_deposits;
    }

    public function getRepeatCreditCards(array $recurringDeposits): array
    {
        $repeatCreditCards = [];

        if ($this->cashier->canQuickDepositViaCard($this->user)) {
            $mts = new \Mts('', $this->user);
            $creditCardSupplierConfigs = $mts->getValidCcSupplierConfigs();
            $creditCardSupplier = $mts->getCcSupplier();

            $countryConfigNoLimits = $this->user->checkCountryConfig('countries', 'no-limits');
            $isUserVerified = $this->user->isVerified();

            foreach ($recurringDeposits as $supplier => $repeats) {

                // If the supplier does not do repeats (or is not even in the config array) we move on right away.
                if (empty($creditCardSupplierConfigs[$supplier]['repeat_type'])) {
                    continue;
                }

                // We're looking at the correct CC supplier
                if ($supplier == $creditCardSupplier) {
                    foreach ($repeats as $repeat) {
                        // The max quick amount needs to be bigger than the repeat amount in question
                        if (
                            $repeat['amount'] < $this->cashier->max_quick_amount &&
                            $this->cashier->checkCreditCardIsActive($this->user, 'creditcard', $repeat['card_id'])
                        ) {
                            if (!$countryConfigNoLimits && $repeat['three_d'] == 0) {
                                // TODO, fix this, it is super inefficient just to check if a certain amount is above the limit, just fetch the limit and compare against
                                // the pre-fetched limit in the loop instead.
                                if (
                                    $this->cashier->checkInCashLimit(
                                        $repeat['amount'],
                                        $this->user,
                                        '2011-01-01 00:00:00',
                                        '',
                                        'card'
                                    ) &&
                                    $isUserVerified
                                ) {
                                    $repeatCreditCards[$repeat['id']] = $repeat;
                                }
                            } else {
                                $repeatCreditCards[$repeat['id']] = $repeat;
                            }
                        }
                    }
                }
            }
        }

        return $repeatCreditCards;
    }

    public function getRepeatBanks(): array
    {
        return $this->getRepeat('bank', ['bill', 'bank', 'visa', 'mc', 'maestro', 'swish']);
    }

    public function getRepeatSwish(): array
    {
        return $this->getRepeat('swish');
    }

    private function getRepeat(string $methodType, array $excludedSchemes = []): array
    {
        $quickDepositConfig = $this->cashier->getSetting('quick_deposit')[$methodType];

        if (!$quickDepositConfig['active']) {
            return [];
        }

        $userCountry = $this->user->getCountry();
        $depositTypes = [];

        foreach ($quickDepositConfig['providers'] as $bank => $countries) {
            if (in_array($userCountry, $countries)) {
                $depositTypes[] = $bank;
            }
        }

        return $this->cashier->getDepositsForRepeat($this->user, $depositTypes, $excludedSchemes, self::DISPLAY_COUNT);
    }
}
