<?php
namespace IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Requests\QueryAccountProvinceRequest;
use IT\Pacg\Services\QueryAccountProvinceEntity;

/**
 * Class QueryAccountProvinceAction
 * @package IT\Pacg\Actions
 */
class QueryAccountProvinceAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return QueryAccountProvinceRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return QueryAccountProvinceEntity::class;
    }
}