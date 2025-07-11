<?php

trait UnitTestableTrait
{

    /**
     * Wraps call to global method, so it can be overwritten in tests
     *
     * @return string
     */
    protected function getClientIp()
    {
        return remIp();
    }

    /**
     * Wrap die method, so it can be tested properly
     *
     * @param  string  $message
     *
     * @return void
     */
    protected function terminate(string $message)
    {
        die($message);
    }

    /**
     * Wraps call to global method, so it can be overwritten in tests
     *
     * @return bool
     */
    protected function getIsCli()
    {
        return isCli();
    }
}