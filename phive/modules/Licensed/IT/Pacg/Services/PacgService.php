<?php
namespace IT\Pacg\Services;

use IT\Abstractions\AbstractEntity;

require_once __DIR__ . '/../../../../../traits/MassAssignmentTrait.php';

/**
 * Class PacgService
 * @package IT\Pacg\Services
 */
class PacgService extends AbstractEntity
{
    /**
     * @return string
     */
    protected function getTransactionId(): string
    {
        /**
         * @todo it's necessary to check the best way to create a transaction_id
         */
        return intval(bcmul(microtime(true), 100000));
    }

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        return array_merge(
            [
                "idTransazione" => $this->getTransactionId(),
            ],
            $array
        );
    }
}