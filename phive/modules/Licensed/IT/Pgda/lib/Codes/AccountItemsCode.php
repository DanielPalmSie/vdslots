<?php

require_once 'Abstract/AbstractCode.php';

/**
 * code and description of accounting items
 * Section 8.3
 * Class AccountItemsCode
 */
class AccountItemsCode extends AbstractCode
{
    /**
     * TODO Add the other codes
     * @var array
     */
    protected static $codes = [
        // By this mean that the gambling sessions have taken place correctly during the time requested
        1 => "Validated session",
        // Gambling sessions for which an invalidation authorisation has been issued
        2 => "Invalidated session",
        // These are participation rights sold in sessions using modalities 1 and 2
        3 => "Participation rights sold",
        // These represent participation rights that have been cancelled for sessions operating under modality 2
        4 => "Cancelled participation rights",
        // These are functional participation rights operating under modalities 1 and 2
        5 => "Validated participation rights",
        // Invalidated participation rights
        6 => "Invalidated participation rights",
        // Value of the amounts collected on which taxes are calculated.
        7 => "Taxable amount",
        // Value of the tax calculated based on the type of game the taxable amount refers
        8 => "Tax",
        // Value of the taxes related to invalidated participation rights
        9 => "Reversal of invalidated participation right taxes"
    ];




}