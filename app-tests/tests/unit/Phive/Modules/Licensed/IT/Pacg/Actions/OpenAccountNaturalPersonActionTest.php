<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Actions;

use IT\Pacg\Actions\OpenAccountNaturalPersonAction;
use IT\Pacg\Requests\OpenAccountNaturalPersonRequest;
use IT\Pacg\Responses\OpenAccountNaturalPersonResponse;
use IT\Pacg\Services\OpenAccountNaturalPersonEntity;

/**
 * Class OpenAccountNaturalPersonActionTest
 */
class OpenAccountNaturalPersonActionTest extends AbstractActionTest
{
    /**
     * @var string
     */
    protected $stub_type = OpenAccountNaturalPersonAction::class;

    /**
     * @var string
     */
    protected $request_name = OpenAccountNaturalPersonRequest::class;

    /**
     * @var string
     */
    protected $entity_name = OpenAccountNaturalPersonEntity::class;

    /**
     * @var string
     */
    protected $response_name = OpenAccountNaturalPersonResponse::class;
}