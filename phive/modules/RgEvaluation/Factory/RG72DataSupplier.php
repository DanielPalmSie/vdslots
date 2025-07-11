<?php

namespace RgEvaluation\Factory;

use DBUser;

class RG72DataSupplier extends BaseDataSupplier
{
    protected function setCommonVariables(): void
    {
        $this->variables = $this->uh->getArrayFromLastTriggerData($this->user->getId(), $this->getTriggerName());

        $this->variables = [
            'net_deposit_amount' => $this->variables['net_deposit_amount'] ?? '',
            'deposit_amount' => $this->variables['deposit_amount'] ?? '',
            'time' => $this->variables['time'] ?? '',
        ];
    }
}
