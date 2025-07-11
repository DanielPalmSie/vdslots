<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForBonusForfeitGamePlay extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'active.bonus.not.forfeit.gameplay.html' => '<p>Active bonus cannot be forfeit during gameplay.</p>
                                                         <p>Kind regards</p>
                                                         <p>Support Videoslots</p>'
        ],

    ];
}