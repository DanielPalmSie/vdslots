<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForPayNPLayLoginSuccess extends SeederTranslation
{

    /* Example ['lang' => ['alias1' => 'value1',...]]*/
   protected array $data = [
        'en' => [
            'paynplay.error.login-success.title' => 'LOGIN',
            'paynplay.error.login-success.sub-title' => 'SUCCESS',
            'paynplay.error.login-success.description' => 'Logged in successfully'
        ]
   ];
}
