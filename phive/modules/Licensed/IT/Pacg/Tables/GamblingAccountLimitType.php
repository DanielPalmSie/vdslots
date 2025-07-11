<?php
namespace IT\Pacg\Tables;

/**
 * Appendices 5.12
 * Final class GamblingAccountLimitType
 */
final class GamblingAccountLimitType extends AbstractTable
{

    /**
     * Understood as being from midnight to midnight
     * @var int
     */
    public static $daily = 1;


    /**
     * From Monday to Sunday
     * @var int
     */
    public static $weekly = 2;


    /**
     * Refers to a calendar month
     * @var int
     */
    public static $monthly = 3;


}