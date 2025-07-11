<?php
namespace IT\Pacg\Tables;

/**
 * Self-exclusion values that can be used in TRASVERSAL SELF-EXCLUSION MANAGEMENT message
 *
 * Final class TrasversalSelfExclusion
 */
final class TrasversalSelfExclusion extends AbstractTable
{
    // $non_significant value is used during account reactivation
    public static $non_significant = 0;

    public static $open_ended = 1;

    public static $days_30 = 2;

    public static $days_60 = 3;

    public static $days_90 = 4;
}
