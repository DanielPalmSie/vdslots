<?php
namespace IT\Pacg\Tables;

/**
 * Appendices 5.5
 * Final class TransactionResonCode
 */
final class TransactionReasonCode extends AbstractTable
{

    public static $top_up = 1;

    public static $top_up_reversal = 2;

    public static $withdrawal = 3;

    public static $withdrawal_reversal = 4;

    public static $additional_service_costs = 7;

    public static $additional_service_cost_reversal = 8;

}