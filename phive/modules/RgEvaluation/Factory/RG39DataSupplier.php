<?php

namespace RgEvaluation\Factory;

class RG39DataSupplier extends BaseDataSupplier
{
    public function getRgPopupVariables(): array
    {
        return ['accountResponsibleGamingUrl' => phive('Licensed')->getRespGamingUrl($this->user)];
    }
}