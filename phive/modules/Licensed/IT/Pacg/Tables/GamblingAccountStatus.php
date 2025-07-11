<?php
namespace IT\Pacg\Tables;

/**
 * Appendices 5.2
 * Final class GamblingAccountStatus
 */
final class GamblingAccountStatus extends AbstractTable
{

    public static $open = 1;

    public static $suspended = 2;

    public static $closed = 3;

    public static $dormant = 4;

    public static $blocked = 5;

}