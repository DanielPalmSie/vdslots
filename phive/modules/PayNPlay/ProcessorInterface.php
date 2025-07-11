<?php

namespace PayNPlay;
/**
 *
 */
interface ProcessorInterface
{
    /**
     * @param string $supplier
     * @param array $payload
     * @return mixed
     */
    public function process(ProcessorPayload $payload): ProcessorResponse;


    /**
     * @param string $transaction_id
     * @return void
     */
    public function onSuccess(string $transaction_id): void;
}
