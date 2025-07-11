<?php
namespace IT\Pacg\Tables;

/**
 * Appendices 5.9
 * Final class PaymentMethod
 */
final class PaymentMethod extends AbstractTable
{

    public static $credit_card = 2;

    public static $debit_card = 3;

    public static $bank_or_post_office_transfer = 4;

    public static $postal_order = 5;

    public static $current_account_check = 6;

    public static $cashier_check = 7;

    public static $money_order = 8;

    public static $scratch_top_up = 9;

    public static $elmi = 11;

    public static $gambling_account = 12;

    public static $conversion_from_bonus = 13;

    public static $e_wallet = 14;

    public static $point_of_sale = 15;

    /**
     * Payment Institute art. 1, paragraph 2, lett. h- septies.l. nos. 3 and 6 of Italian Legislative Decree No. 385/1993
     * @var int
     */
    public static $payment_institute = 16;


}