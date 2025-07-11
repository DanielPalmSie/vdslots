<?php

use \App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringLossLimitSetOverMaximum extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'loss-limit.set.over.maximum.html' => "<div class='lic-mbox-warning-icon'></div><h2 class='lic-mbox-color'>Loss Limit</h2><p>You tried to set a higher loss limit than the maximum available.</p><p>Your current loss limit has been set to {{loss_limit}} and if you wish to have a higher limit, please contact customer support.</p>",
        ]
    ];
}