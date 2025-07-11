<?php
namespace IT\Loaders;

use Exception;
use IT\Abstractions\AbstractAction;
use IT\Pacg\Actions\AccountBalanceAction;
use IT\Pacg\Actions\AccountBonusTransactionsAction;
use IT\Pacg\Actions\BonusReversalAccountTransactionAction;
use IT\Pacg\Actions\AccountDormantAction;
use IT\Pacg\Actions\AccountMigrationAction;
use IT\Pacg\Actions\AccountTransactionsAction;
use IT\Pacg\Actions\AccountTransactionsReversalAction;
use IT\Pacg\Actions\Enums\ActionsEnum as ActionPacgEnum;
use IT\Pacg\Actions\ListAccountsWithoutSubRegistrationAction;
use IT\Pacg\Actions\ListDormantAccountsAction;
use IT\Pacg\Actions\ListSelfExcludedAccountsAction;
use IT\Pacg\Actions\OpenAccountLegalAction;
use IT\Pacg\Actions\OpenAccountNaturalPersonAction;
use IT\Pacg\Actions\QueryAccountDocumentAction;
use IT\Pacg\Actions\QueryAccountEmailAction;
use IT\Pacg\Actions\QueryAccountLimitAction;
use IT\Pacg\Actions\QueryAccountProvinceAction;
use IT\Pacg\Actions\QueryAccountPseudonymAction;
use IT\Pacg\Actions\QueryAccountStatusAction;
use IT\Pacg\Actions\QuerySelfExcludedSubjectAction;
use IT\Pacg\Actions\QuerySelfExcludedSubjectHistoryAction;
use IT\Pacg\Actions\SubregistrationAction;
use IT\Pacg\Actions\SummaryOfServiceOperationsAction;
use IT\Pacg\Actions\SummaryOfTransactionOperationsAction;
use IT\Pacg\Actions\TrasversalSelfExclusionManagementAction;
use IT\Pacg\Actions\UpdateAccountLimitAction;
use IT\Pacg\Actions\UpdateAccountProvinceOfResidenceAction;
use IT\Pacg\Actions\UpdateAccountPseudonymAction;
use IT\Pacg\Actions\UpdateAccountStatusAction;
use IT\Pacg\Actions\UpdateEmailAccountAction;
use IT\Pacg\Actions\UpdatingOwnerIdDocumentDetailsAction;
use IT\Pacg\Client\PacgClient;
use IT\Pgda\Actions\AcquisitionParticipationRightMessageAction;
use IT\Pgda\Actions\AdditionSignatureCertificateAction;
use IT\Pgda\Actions\EndParticipationFinalPlayerBalanceAction;
use IT\Pgda\Actions\Enums\ActionsEnum as ActionPgdaEnum;
use IT\Pgda\Actions\GameExecutionCommunicationAction;
use IT\Pgda\Actions\InstalledSoftwareVersionCommunicationAction;
use IT\Pgda\Actions\ReportedAnomaliesAction;
use IT\Pgda\Actions\RequestFinancialAccountingAction;
use IT\Pgda\Actions\GameSessionsAlignmentCommunicationAction;
use IT\Pgda\Actions\SessionEndDateUpdateRequestAction;
use IT\Pgda\Actions\EndGameSessionAction;
use IT\Pgda\Actions\SessionReportedAnomaliesAction;
use IT\Pgda\Actions\StartGameSessionsAction;
use IT\Pgda\Client\PgdaClient;

/**
 * Class LoaderAction
 * @package IT\Pacg\Actions\Loaders
 */
class LoaderAction
{
    /**
     * @var PacgClient
     */
    private $client_pacg;

    /**
     * @var PgdaClient
     */
    private $client_pgda;

    /**
     * @var array
     */
    private $settings = [];


    /**
     * LoaderAction constructor.
     * @param $settings
     */
    public function __construct(array $settings = [])
    {
        if (empty($settings)) {
            $settings = phive('Licensed')->getSetting('IT');
        }

        $this->settings = $settings;
    }

    /**
     * @param $settings
     * @return PacgClient
     * @throws \SoapFault
     */
    public function getPacgClient(): PacgClient
    {
        if (empty($this->client_pacg)) {
            $this->client_pacg = new PacgClient($this->settings['pacg']);
        }

        return $this->client_pacg;
    }

    /**
     * @param $settings
     * @return PgdaClient
     */
    public function getPgdaClient(): PgdaClient
    {
        if (empty($this->client_pgda)) {
            $this->client_pgda = new PgdaClient($this->settings);
        }

        return $this->client_pgda;
    }

    /**
     * @param $actionName
     * @return AbstractAction
     * @throws \Exception
     */
    public function getAction($actionName): AbstractAction
    {
        switch ($actionName) {
            case ActionPacgEnum::ACCOUNT_BALANCE:
                return new AccountBalanceAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::ACCOUNT_TRANSACTIONS:
                return new AccountTransactionsAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::ACCOUNT_BONUS_TRANSACTIONS:
                return new AccountBonusTransactionsAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::ACCOUNT_BONUS_REVERSAL_TRANSACTION:
                return new BonusReversalAccountTransactionAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::UPDATE_ACCOUNT_STATUS:
                return new UpdateAccountStatusAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::OPEN_ACCOUNT_LEGAL:
                return new OpenAccountLegalAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::UPDATE_EMAIL_ACCOUNT:
                return new UpdateEmailAccountAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::OPEN_ACCOUNT_NATURAL_PERSON:
                return new OpenAccountNaturalPersonAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::UPDATE_ACCOUNT_PROVINCE_OF_RESIDENCE:
                return new UpdateAccountProvinceOfResidenceAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::UPDATING_OWNER_ID_DOCUMENT_DETAILS:
                return new UpdatingOwnerIdDocumentDetailsAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::SUMMARY_OF_TRANSACTION_OPERATIONS:
                return new SummaryOfTransactionOperationsAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::SUMMARY_OF_SERVICE_OPERATIONS:
                return new SummaryOfServiceOperationsAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::ACCOUNT_TRANSACTIONS_REVERSAL:
                return new AccountTransactionsReversalAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::SUBREGISTRATION:
                return new SubregistrationAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::LIST_ACCOUNTS_WITHOUT_SUB_REGISTRATION:
                return new ListAccountsWithoutSubRegistrationAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::ACCOUNT_DORMANT:
                return new AccountDormantAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::LIST_DORMANT_ACCOUNTS:
                return new ListDormantAccountsAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::UPDATE_ACCOUNT_LIMIT:
                return new UpdateAccountLimitAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::QUERY_ACCOUNT_LIMIT:
                return new QueryAccountLimitAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::TRASVERSAL_SELF_EXCLUSION_MANAGEMENT:
                return new TrasversalSelfExclusionManagementAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::LIST_SELF_EXCLUDED_ACCOUNTS:
                return new ListSelfExcludedAccountsAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::QUERY_SELF_EXCLUDED_SUBJECT:
                return new QuerySelfExcludedSubjectAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::QUERY_SELF_EXCLUDED_SUBJECT_HISTORY:
                return new QuerySelfExcludedSubjectHistoryAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::QUERY_ACCOUNT_PSEUDONYM:
                return new QueryAccountPseudonymAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::QUERY_ACCOUNT_EMAIL:
                return new QueryAccountEmailAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::ACCOUNT_MIGRATION:
                return new AccountMigrationAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::QUERY_ACCOUNT_STATUS:
                return new QueryAccountStatusAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::QUERY_ACCOUNT_PROVINCE:
                return new QueryAccountProvinceAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::QUERY_ACCOUNT_DOCUMENT:
                return new QueryAccountDocumentAction($this->getPacgClient(), $this->settings);
            case ActionPacgEnum::UPDATE_ACCOUNT_PSEUDONYM:
                return new UpdateAccountPseudonymAction($this->getPacgClient(), $this->settings);
            case ActionPgdaEnum::ACQUISITION_PARTICIPATION_RIGHT_MESSAGE:
                return new AcquisitionParticipationRightMessageAction($this->getPgdaClient(), $this->settings);
            case ActionPgdaEnum::END_PARTICIPATION_FINAL_PLAYER_BALANCE:
                return new EndParticipationFinalPlayerBalanceAction($this->getPgdaClient(), $this->settings);
            case ActionPgdaEnum::SESSION_END_DATE_UPDATE_REQUEST:
                return new SessionEndDateUpdateRequestAction($this->getPgdaClient(), $this->settings);
            case ActionPgdaEnum::GAME_SESSIONS_ALIGNMENT_COMMUNICATION:
                return new GameSessionsAlignmentCommunicationAction($this->getPgdaClient(), $this->settings);
            case ActionPgdaEnum::END_GAME_SESSION:
                return new EndGameSessionAction($this->getPgdaClient(), $this->settings);
            case ActionPgdaEnum::START_GAME_SESSIONS:
                return new StartGameSessionsAction($this->getPgdaClient(), $this->settings);
            case ActionPgdaEnum::REQUEST_FINANCIAL_ACCOUNTING:
                return new RequestFinancialAccountingAction($this->getPgdaClient(), $this->settings);
            case ActionPgdaEnum::INSTALLED_SOFTWARE_VERSION_COMMUNICATION:
                return new InstalledSoftwareVersionCommunicationAction($this->getPgdaClient(), $this->settings);
            case ActionPgdaEnum::ADDITION_SIGNATURE_CERTIFICATE:
                return new AdditionSignatureCertificateAction($this->getPgdaClient(), $this->settings);
            case ActionPgdaEnum::REPORTED_ANOMALIES:
                return new ReportedAnomaliesAction($this->getPgdaClient(), $this->settings);
            case ActionPgdaEnum::SESSION_REPORTED_ANOMALIES:
                return new SessionReportedAnomaliesAction($this->getPgdaClient(), $this->settings);
            case ActionPgdaEnum::GAME_EXECUTION_COMMUNICATION:
                return new GameExecutionCommunicationAction($this->getPgdaClient(), $this->settings);
            default:
                throw new Exception('The action does not exist.');
        }
    }
}