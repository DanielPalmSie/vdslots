<?php

class FallbackDepositDecorator
{
    private DBUser $user;
    private array $depositFallbacks;

    public function __construct(DBUser $user, array $depositFallbacks)
    {
        $this->depositFallbacks = $depositFallbacks;
        $this->user = $user;
    }

    public function decorate(array $result, array $rawErrors = []): array
    {
        $showErrors = [
            'credit.card.not.allowed',
            'card.bin.not.allowed'
        ];

        foreach ($rawErrors as $error) {
            if (in_array($error, [
                'err.toomuch',
                'err.toolittle',
                'err.empty',
                'deposits.over.limit.html',
                'cashier.error.amount',
            ])) {
                return $result;
            }

            if (in_array($error, $showErrors)) {
                $result['show_error'] = true;
            }
        }

        $country = $this->user->getCountry();
        if (isset($this->depositFallbacks[$country])) {
            $result['fallback_deposit'] = $this->depositFallbacks[$country];
        }

        return $result;
    }
}
