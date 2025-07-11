<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForOntarioPopUp extends SeederTranslation
{

    public function init() {
        parent::init();

        $this->data = [
            'en' => [
                'msg.ontario.popup.header' => 'Welcome back',
                'msg.ontario.popup.box.button' => 'I confirm the information above',
            ]
        ];

    }

}
