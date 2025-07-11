<?php

namespace RgEvaluation\Factory;

use DBUser;

abstract class DynamicVariablesSupplier
{
    protected DBUser $user;

    public function __construct(DBUser $user)
    {
        $this->user = $user;
    }

    abstract public function getSupplier(): TriggerDataSupplier;

    /**
     * Returns dynamic data to build popup context from {trigger_name}.rg.info.description.html
     *
     * @return array
     */
    public function getRgPopupVariables(): array
    {
        return $this->getSupplier()->getRgPopupVariables();
    }

    /**
     * Returns dynamic data for {trigger_name}.user.comment (localized string)
     *
     * @param array $mix
     *
     * @return array
     */
    public function getUserCommentsVariables(array $mix = []): array
    {
        $variables = $this->getSupplier()->getUserCommentsVariables();
        return array_merge($variables, $mix);
    }

    public function getRgWarningEmailVariables(array $mix = []): array
    {
        $variables = $this->getSupplier()->getRgWarningEmailVariables();
        return array_merge($variables, $mix);
    }
}