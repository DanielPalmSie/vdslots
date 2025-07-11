<?php

namespace RgEvaluation\Factory;

class RG77DataSupplier extends BaseDataSupplier
{
    private const TRIGGER_NAME = 'RG77';

    public function getRgPopupVariables(): array
    {
        return $this->getTriggerDynamicData();
    }

    public function getUserCommentsVariables(): array
    {
        return $this->getTriggerDynamicData();
    }

    public function getTriggerDynamicData(): array
    {
        $data = $this->uh->getArrayFromLastTriggerData($this->user->getId(), self::TRIGGER_NAME);

        return [
            'top_depositors' => $data['top_depositors'] ?? 'N.A.',
            'months' => $data['months'] ?? 'N.A.',
        ];
    }

}
