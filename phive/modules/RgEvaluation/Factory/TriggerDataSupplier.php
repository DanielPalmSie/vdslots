<?php

namespace RgEvaluation\Factory;

interface TriggerDataSupplier
{
    public function getRgPopupVariables(): array;

    public function getUserCommentsVariables(): array;

    public function getRgWarningEmailVariables(): array;
}