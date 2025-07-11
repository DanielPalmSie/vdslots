<?php
namespace IT\Pacg;

use IT\Pacg\Actions\Enums\ActionsEnum;
use IT\Traits\ExecuteActionTrait;
use Exception;

/**
 * Trait PacgTrait
 * @package IT\Pacg
 */
trait PacgTrait
{
    use ExecuteActionTrait;

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function updateEmailAccount(array $data): array
    {
        return $this->execAction(
            ActionsEnum::UPDATE_EMAIL_ACCOUNT,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function onOpenAccountNaturalPerson(array $data): array
    {
        return $this->execAction(
            ActionsEnum::OPEN_ACCOUNT_NATURAL_PERSON,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function onOpenAccountLegalEntity(array $data): array
    {
        return $this->execAction(
            ActionsEnum::OPEN_ACCOUNT_LEGAL,
            $data
        );
    }

    /**
     *
     * /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function onAccountStatusQuery(array $data)
    {
        return $this->execAction(
            ActionsEnum::QUERY_ACCOUNT_STATUS,
            $data
        );
    }

    /**
     * Returns the province of residence as is registered at the italian regulator
     *
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function onAccountProvinceQuery(array $data): array
    {
        return $this->execAction(
            ActionsEnum::QUERY_ACCOUNT_PROVINCE,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function onAccountDocumentQuery(array $data): array
    {
        return $this->execAction(
            ActionsEnum::QUERY_ACCOUNT_DOCUMENT,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function summaryOfTransactionOperations(array $data): array
    {
        return $this->execAction(
            ActionsEnum::SUMMARY_OF_TRANSACTION_OPERATIONS,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function accountBalance(array $data): array
    {
        return $this->execAction(
            ActionsEnum::ACCOUNT_BALANCE,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function changeAccountProvinceOfResidence(array $data): array
    {
        return $this->execAction(
            ActionsEnum::UPDATE_ACCOUNT_PROVINCE_OF_RESIDENCE,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function updatingOwnerIdDocumentDetails(array $data): array
    {
        return $this->execAction(
            ActionsEnum::UPDATING_OWNER_ID_DOCUMENT_DETAILS,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function summaryOfServiceOperations(array $data): array
    {
        return $this->execAction(
            ActionsEnum::SUMMARY_OF_SERVICE_OPERATIONS,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function accountTransactions(array $data): array
    {
        return $this->execAction(
            ActionsEnum::ACCOUNT_TRANSACTIONS,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function bonusAccountTransactions(array $data): array
    {
        return $this->execAction(
            ActionsEnum::ACCOUNT_BONUS_TRANSACTIONS,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function bonusReversalAccountTransaction(array $data): array
    {
        return $this->execAction(
            ActionsEnum::ACCOUNT_BONUS_REVERSAL_TRANSACTION,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function reversalAccountTransactions(array $data): array
    {
        return $this->execAction(
            ActionsEnum::ACCOUNT_TRANSACTIONS_REVERSAL,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function changeAccountStatus(array $data): array
    {
        return $this->execAction(
            ActionsEnum::UPDATE_ACCOUNT_STATUS,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function subregistration(array $data): array
    {
        return $this->execAction(
            ActionsEnum::SUBREGISTRATION,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function listAccountsWithoutSubRegistration(array $data): array
    {
        return $this->execAction(
            ActionsEnum::LIST_ACCOUNTS_WITHOUT_SUB_REGISTRATION,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function dormantAccount(array $data): array
    {
        return $this->execAction(
            ActionsEnum::ACCOUNT_DORMANT,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function listDormantAccounts(array $data): array
    {
        return $this->execAction(
            ActionsEnum::LIST_DORMANT_ACCOUNTS,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function updateAccountLimit(array $data): array
    {
        return $this->execAction(
            ActionsEnum::UPDATE_ACCOUNT_LIMIT,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function accountLimitQuery(array $data): array
    {
        return $this->execAction(
            ActionsEnum::QUERY_ACCOUNT_LIMIT,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function trasversalSelfExclusionManagement(array $data): array
    {
        return $this->execAction(
            ActionsEnum::TRASVERSAL_SELF_EXCLUSION_MANAGEMENT,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function listSelfExcludedAccounts(array $data): array
    {
        return $this->execAction(
            ActionsEnum::LIST_SELF_EXCLUDED_ACCOUNTS,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function querySelfExcludedSubject(array $data): array
    {
        return $this->execAction(
            ActionsEnum::QUERY_SELF_EXCLUDED_SUBJECT,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function querySelfExcludedSubjectHistory(array $data): array
    {
        return $this->execAction(
            ActionsEnum::QUERY_SELF_EXCLUDED_SUBJECT_HISTORY,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function accountPseudonymQuery(array $data): array
    {
        return $this->execAction(
            ActionsEnum::QUERY_ACCOUNT_PSEUDONYM,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function updateAccountPseudonym(array $data): array
    {
        return $this->execAction(
            ActionsEnum::UPDATE_ACCOUNT_PSEUDONYM,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function accountEmailQuery(array $data): array
    {
        return $this->execAction(
            ActionsEnum::QUERY_ACCOUNT_EMAIL,
            $data
        );
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function accountMigration(array $data): array
    {
        return $this->execAction(
            ActionsEnum::ACCOUNT_MIGRATION,
            $data
        );
    }
}