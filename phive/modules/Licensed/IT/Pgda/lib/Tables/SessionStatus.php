<?php

require_once 'Abstract/AbstractTable.php';

/**
 * Gambling session status codes.
 * Section 8.8
 * Final class SessionStatus
 */
final class SessionStatus extends AbstractTable
{
    /**
     * Session in modality 1,3,4
     * @var int
     */
    public static $open = 1;

    /**
     * Session in modality 1,3,4
     * @var int
     */
    public static $closed = 9;

    /**
     * Session using modality 2 open (awaiting validation)
     * @var string
     */
    public static $awaiting_validation = 21;

    /**
     * Session using modality 2 cancelled
     * @var int
     */
    public static $cancelled = 22;

    /**
     * Session using modality 2 validated
     * @var string
     */
    public static $validated = 25;

    /**
     * Session using modality 2 closed validated
     * @var string
     */
    public static $closed_validated = 29;



}