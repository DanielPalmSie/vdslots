<?php
namespace IT\Pacg\Tables;

/**
 * Appendices 5.3
 * Final class StatusChangeReasonCode
 */
final class StatusChangeReasonCode extends AbstractTable
{

    public static $adm = 1;

    public static $licensee = 2;

    public static $gambling_account_holder = 3;

    public static $judicial_authority = 4;

    public static $adm_following_the_decease_of_the_holder = 5;

    public static $licensee_due_to_failure_send_id_document = 6;

    public static $licensee_due_to_suspected_fraud = 7;

    public static $account_owner_for_reasons_of_self_exclusion = 8;

}