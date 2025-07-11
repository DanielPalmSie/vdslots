<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddTransactionBlockedLocalizedStrings extends SeederTranslation
{
    const TRANSLATION_ALIAS = 'transaction.blocked.html';

    protected array $data = [
        'en' => [
            self::TRANSLATION_ALIAS => '<p>You are currently being prevented&nbsp;from making any further transactions. Please contact our Support Team at <a href="/customer-service/"><span style="text-decoration: underline;">support@videoslots.com</span></a> for more information.&nbsp;</p>',
        ],
//TODO: add translations for other languages
    ];
}