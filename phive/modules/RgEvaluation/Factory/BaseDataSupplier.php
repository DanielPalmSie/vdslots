<?php

namespace RgEvaluation\Factory;

use DBUser;
use RuntimeException;

class BaseDataSupplier implements TriggerDataSupplier
{
    protected DBUser $user;
    protected $arf;
    protected $uh;
    protected array $variables = [];

    public function __construct(DBUser $user)
    {
        $this->user = $user;
        $this->arf = phive('Cashier/Arf');
        $this->uh = phive('DBUserHandler');
    }

    public function getRgPopupVariables(): array
    {
        return $this->getCommonVariables();
    }

    public function getUserCommentsVariables(): array
    {
        return $this->getCommonVariables();
    }

    public function getRgWarningEmailVariables(): array
    {
        return $this->formatVariablesAccordingToEmailTemplateFormat(
            $this->getCommonVariables()
        );
    }

    protected function getCommonVariables(): array
    {
        if (!empty($this->variables)) {
            return $this->variables;
        }

        $this->setCommonVariables();

        return $this->variables;
    }

    /**
     * @throws RuntimeException
     */
    protected function setCommonVariables(): void
    {
        $this->variables = $this->uh->getArrayFromLastTriggerData($this->user->getId(), $this->getTriggerName());
    }

    /**
     * Returns trigger name automatically from a child class. E.g. RG72DataSupplier -> RG72
     *
     * @return string
     * @throws RuntimeException
     */
    protected function getTriggerName(): string
    {
        $reflection = new \ReflectionClass($this);
        $className = $reflection->getShortName();
        $triggerName = str_replace("DataSupplier", "", $className);

        if (empty($triggerName)) {
            throw new RuntimeException("Non standard DataSupplier class. Trigger name can't be fetched.");
        }

        return $triggerName;
    }

    /**
     * @param array $variables
     *
     * @return array
     */
    private function formatVariablesAccordingToEmailTemplateFormat(array $variables): array {
        $result = [];
        foreach ($variables as $key => $value) {
            $newKey = '__' . strtoupper($key) . '__';
            $result[$newKey] = $value;
        }
        return $result;
    }
}
