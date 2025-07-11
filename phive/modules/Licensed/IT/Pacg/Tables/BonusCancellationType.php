<?php
namespace IT\Pacg\Tables;

/**
 * Appendices 5.14
 *
 *  1. Ordinary Bonus Cancellation. It should be used when the amount of the bonus to be canceled is
    less than or equal to the amount of the bonus awarded;
    2. Reversal of the winning bonus. It should be used when the amount of the bonus to be canceled is
    higher than the amount of the bonus awarded;
    3. Cancellation of the conversion bonus. It should be used when a bonus needs to be
    transformed into a withdrawable amount. In this case the account balance remains
    unchanged but only the bonus part of the balance for is deducted (transformed)
    the amount indicated in the cancellation message.
 * Final class PersonalDataOriginType
 */
final class BonusCancellationType extends AbstractTable
{

    public static int $ordinary = 1; // Ordinary Bonus Cancellation.

    public static int $reversal = 2; // Reversal of the winning bonus

    public static int $conversion = 3; // Cancellation of the conversion bonus

}