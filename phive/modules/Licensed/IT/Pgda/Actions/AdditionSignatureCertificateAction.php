<?php
namespace IT\Pgda\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pgda\Requests\AdditionSignatureCertificateRequest;
use IT\Pgda\Services\AdditionSignatureCertificateEntity;

/**
 * Class AdditionSignatureCertificateAction
 * @package IT\Pgda\Actions
 */
class AdditionSignatureCertificateAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function request(): string
    {
        return AdditionSignatureCertificateRequest::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return AdditionSignatureCertificateEntity::class;
    }
}