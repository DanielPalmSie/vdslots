<?php

require_once __DIR__ . '/../../api/PhModule.php';

use Laraphive\Domain\User\Models\User;

class RgEvaluation extends PhModule
{
    /** @var SQL $db */
    public $db;

    protected string $table = 'users_rg_evaluation';
    public const STEP_STARTED = "started";
    public const STEP_SELF_ASSESSMENT = "self-assessment";
    public const STEP_MANUAL_REVIEW = "manual-review";

    function __construct()
    {
        $this->db = phive('SQL');
        $this->abilities = $this->getSetting('abilities');
        $this->userModel = new User();
    }

    /**
     * @param DBUser $user
     * @param string $trigger_name
     *
     * @return void
     */
    public function startEvaluation(DBUser $user, string $trigger_name): void
    {
        $rg_evaluation_state = phive('Config')->getValue('RG', 'rg-evaluation-state');

        if ($rg_evaluation_state !== 'on') {
            return;
        }

        $jurisdiction = $user->getJurisdiction();
        $trigger_evaluation_in_jurisdictions = phive('Config')->valAsArray('RG', "$trigger_name-evaluation-in-jurisdictions", ',');

        if (!in_array($jurisdiction, $trigger_evaluation_in_jurisdictions, true)) {
            return;
        }

        $this->db->sh($user->getId())->insertArray(
            'users_rg_evaluation',
            [
                'user_id' => $user->getId(),
                'trigger_name' => $trigger_name,
                'step' => static::STEP_STARTED
            ]
        );
    }
}
