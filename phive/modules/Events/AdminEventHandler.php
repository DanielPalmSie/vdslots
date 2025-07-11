<?php

class AdminEventHandler
{
    private $rg_limits_module;

    /**
     *
     */
    public function __construct()
    {
        $this->rg_limits_module = rgLimits();
    }

    /**
     *
     */
    public function onConfigUpdateGlobalCustomerNetDepositEvent(string $action)
    {
        try {
            $this->rg_limits_module->refreshCustomerNetDepositLimit($action);
        } catch (\Throwable $e) {
            phive('Logger')
                ->getLogger()
                ->error(__METHOD__, ["on action:" . $action . " Error message:" . $e->getMessage()]);
        }
    }

}
