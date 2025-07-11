<?php

namespace App\Constants;

class Networks
{
    public const BETRADAR = [
        'name' => 'betradar',
        'product' => 'S'
    ];

    public const POOLX = [
        'name' => 'poolx',
        'product' => 'P'
    ];

    public const ALTENAR = [
        'name' => 'altenar',
        'product' => 'S'
    ];

    public const BINGO = [
        // for Bingo we use game provider as a network instead of hardcoded string
        'name' => null,
        'product' => 'B'
    ];
}
