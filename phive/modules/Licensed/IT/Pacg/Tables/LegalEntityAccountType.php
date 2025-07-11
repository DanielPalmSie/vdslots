<?php
namespace IT\Pacg\Tables;

/**
 * Appendices 5.16
 * Final class LegalEntityAccountType
 */
final class LegalEntityAccountType extends AbstractTable
{

    /**
     * Gaming accounts for the special shared betting of the sports forecast contests
     *
     * @var int $sport
     */
    public static int $sport = 1;

    /**
     * Gaming accounts for functional checks in the real environment
     *
     * @var int $functional
     */
    public static int $functional = 2;

}
