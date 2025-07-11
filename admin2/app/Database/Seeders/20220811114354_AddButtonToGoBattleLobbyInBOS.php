<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddButtonToGoBattleLobbyInBOS extends SeederTranslation
{
    public function init()
    {
        parent::init();
        $brand = phive('BrandedConfig')->getBrand();
        $this->data['en']['battle.of.slots.battle-of-slots.bos.button'] = 'Go to Battle Lobby';

        if ($brand == 'mrvegas') {
            $this->data['en']['battle.of.slots.battle-of-slots.bos.button'] = 'Go to Encore Lobby';
        }
    }
}