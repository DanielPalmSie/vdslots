<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddIndefiniteExcludeLocalizedStrings extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'exclude.account.indefinite' => 'Indefinite self-exclusion',
            'exclude.account.indefinite.info.html' => '<p style="text-align: justify;">Below you have the option to permanently self-exclude yourself. By doing this your account will be closed and terminated. Permanent self-exclusion takes effect immediately which means you cannot open your account under any circumstances.</p>
<p style="text-align: justify;">If you would like to seek further assistance, we can also offer the services of a few organisations that are ready to talk to you regarding possible gambling problems. Please click <a href="#">[HERE]</a></p>',
            'exclude.indefinite.days' => 'Indefinite',
        ]
    ];
}