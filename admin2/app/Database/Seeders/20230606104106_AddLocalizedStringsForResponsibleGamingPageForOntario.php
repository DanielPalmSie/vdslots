<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForResponsibleGamingPageForOntario extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'registration.page.rg.info' => 'Please Play Responsibly. If you have questions or concerns about your gambling or someone close to you, please contact <b>ConnexOntario</b> at <b>1-866-531-2600</b> to speak to an advisor, free of charge',
            'help.and.contact' => 'Help & Contact',
            'responsible.gaming' => 'Responsible Gaming',
            ]
    ];
}