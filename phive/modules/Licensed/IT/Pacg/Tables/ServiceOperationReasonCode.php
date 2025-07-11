<?php
namespace IT\Pacg\Tables;

/**
 * Appendices 5.10
 * Final class ServiceOperationReasonCode
 */
final class ServiceOperationReasonCode extends AbstractTable
{

    public static $natural_person_opening_account = 20;

    public static $legal_entity_opening_account = 21;

    public static $change_of_account_status = 22;

    public static $account_balance = 23;

    public static $change_of_province_of_residence = 24;

    public static $account_state_query = 25;

    public static $sub_registration = 26;

    public static $change_of_account_holder_document_data = 27;

    public static $gambling_account_migration = 28;

    public static $simplified_opening_of_natural_person_gambling_account = 33;

    public static $integration_of_simplified_opening_of_natural_person_gambling_account = 34;

    public static $dormant_account = 35;

    public static $id_document_detail_query = 36;

    public static $transversal_self_exclusion_management = 37;

    public static $update_account_limits = 38;

    public static $update_account_pseudonym = 39;

    public static $update_account_email = 40;

}