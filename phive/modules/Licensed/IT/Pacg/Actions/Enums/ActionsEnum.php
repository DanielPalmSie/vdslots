<?php
namespace IT\Pacg\Actions\Enums;

/**
 * Class ActionsEnum
 * @package IT\Pacg\Actions\Enums
 */
class ActionsEnum
{
    const ACCOUNT_BALANCE = 'AccountBalance';
    const ACCOUNT_TRANSACTIONS = 'AccountTransactions';
    const ACCOUNT_BONUS_TRANSACTIONS = 'BonusAccountTransactions';
    const ACCOUNT_BONUS_REVERSAL_TRANSACTION = 'BonusReversalAccountTransaction';
    const UPDATE_ACCOUNT_STATUS = 'ChangeAccountStatus';
    const OPEN_ACCOUNT_LEGAL = 'OpenAccountLegal';
    const UPDATE_EMAIL_ACCOUNT = 'UpdateEmailAccount';
    const UPDATING_OWNER_ID_DOCUMENT_DETAILS = 'UpdatingOwnerIdDocumentDetails';
    const OPEN_ACCOUNT_NATURAL_PERSON = 'OpenAccountNaturalPerson';
    const UPDATE_ACCOUNT_PROVINCE_OF_RESIDENCE = 'changeAccountProvinceOfResidence';
    const SUMMARY_OF_TRANSACTION_OPERATIONS = 'SummaryOfTransactionOperations';
    const SUMMARY_OF_SERVICE_OPERATIONS = 'SummaryOfServiceOperations';
    const ACCOUNT_TRANSACTIONS_REVERSAL = 'ReversalAccountTransactions';
    const SUBREGISTRATION = 'Subregistration';
    const LIST_ACCOUNTS_WITHOUT_SUB_REGISTRATION = 'ListAccountsWithoutSubRegistration';
    const ACCOUNT_DORMANT = 'DormantAccount';
    const LIST_DORMANT_ACCOUNTS = 'ListDormantAccounts';
    const UPDATE_ACCOUNT_LIMIT = 'UpdateAccountLimit';
    const QUERY_ACCOUNT_LIMIT = 'AccountLimitQuery';
    const TRASVERSAL_SELF_EXCLUSION_MANAGEMENT = 'TrasversalSelfExclusionManagement';
    const LIST_SELF_EXCLUDED_ACCOUNTS = 'ListSelfExcludedAccounts';
    const QUERY_SELF_EXCLUDED_SUBJECT = 'QuerySelfExcludedSubject';
    const QUERY_SELF_EXCLUDED_SUBJECT_HISTORY = 'QuerySelfExcludedSubjectHistory';
    const QUERY_ACCOUNT_PSEUDONYM = 'AccountPseudonymQuery';
    const QUERY_ACCOUNT_EMAIL = 'AccountEmailQuery';
    const ACCOUNT_MIGRATION = 'accountMigration';
    const QUERY_ACCOUNT_STATUS = 'AccountStatusQuery';
    const QUERY_ACCOUNT_PROVINCE = 'AccountProvinceQuery';
    const QUERY_ACCOUNT_DOCUMENT = 'AccountDocumentQuery';
    const UPDATE_ACCOUNT_PSEUDONYM = 'updateAccountPseudonym';
}