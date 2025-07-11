<?php
namespace IT\Pgda;

use IT\Pgda\Actions\Enums\ActionsEnum;
use IT\Traits\ExecuteActionTrait;
use Exception;

/**
 * Trait PgdaTrait
 * @package IT\Pgda
 */
trait PgdaTrait
{
    use ExecuteActionTrait;

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function acquisitionParticipationRightMessage(array $data): array
    {
        return $this->execAction(
            ActionsEnum::ACQUISITION_PARTICIPATION_RIGHT_MESSAGE,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function endParticipationFinalPlayerBalance(array $data): array
    {
        return $this->execAction(
            ActionsEnum::END_PARTICIPATION_FINAL_PLAYER_BALANCE,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function gameSessionsAlignmentCommunication(array $data): array
    {
        return $this->execAction(
            ActionsEnum::GAME_SESSIONS_ALIGNMENT_COMMUNICATION,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function sessionEndDateUpdateRequest(array $data): array
    {
        return $this->execAction(
            ActionsEnum::SESSION_END_DATE_UPDATE_REQUEST,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function endGameSession(array $data): array
    {
        return $this->execAction(
            ActionsEnum::END_GAME_SESSION,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function startGameSessions(array $data): array
    {
        return $this->execAction(
            ActionsEnum::START_GAME_SESSIONS,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function sessionReportedAnomalies(array $data): array
    {
        return $this->execAction(
            ActionsEnum::SESSION_REPORTED_ANOMALIES,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function reportedAnomalies(array $data): array
    {
        return $this->execAction(
            ActionsEnum::REPORTED_ANOMALIES,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function requestFinancialAccounting(array $data): array
    {
        return $this->execAction(
            ActionsEnum::REQUEST_FINANCIAL_ACCOUNTING,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function installedSoftwareVersionCommunication(array $data): array
    {
        return $this->execAction(
            ActionsEnum::INSTALLED_SOFTWARE_VERSION_COMMUNICATION,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function additionSignatureCertificate(array $data): array
    {
        return $this->execAction(
            ActionsEnum::ADDITION_SIGNATURE_CERTIFICATE,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function gameExecutionCommunication(array $data): array
    {
        return $this->execAction(
            ActionsEnum::GAME_EXECUTION_COMMUNICATION,
            $data
        );
    }
}