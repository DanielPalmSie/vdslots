<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddOntarioSelfLockLoginError extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'blocked.self_locked.html' => '<p>For some reason your account has been deactivated.</p><p>Please contact support for more information.</p>']
    ];
}