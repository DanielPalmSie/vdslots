<?php

declare(strict_types=1);

namespace ES\ICS\Reports;

class RUD extends BaseProxy
{
    public const TYPE = 'RU';
    public const SUBTYPE = 'RUD';
    public const USER_DATA_NEW = 'A';
    public const USER_DATA_MODIFIED = 'S';
    public const USER_DATA_NON_CHANGED = 'N';
}
