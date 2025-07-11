<?php
namespace IT\Pacg\Services;

require_once __DIR__ . '/../../../../../traits/MassAssignmentTrait.php';
require_once __DIR__ . '/../../../../../traits/ValidationTrait.php';

abstract class AbstractService
{
    use \MassAssignmentTrait;
    use \ValidationTrait;

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array)
    {
        return array_merge(
            [
                "idTransazione" => $this->transaction_id,
            ],
            $array
        );
    }

}