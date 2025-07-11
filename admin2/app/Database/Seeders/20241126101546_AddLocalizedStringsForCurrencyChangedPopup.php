<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForCurrencyChangedPopup extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'currency-changed.popup.header.title' => 'Message',
            'currency-changed.popup.title' => 'Automatic currency transition',
            'currency-changed.popup.description.NOK.to.EUR' => 'Please note that we are transitioning from Norwegian Krone (NOK) to Euro (EUR) as the default currency. No action is required; your balance will be automatically updated.',
            'currency-changed.popup.button.text' => 'Ok',

            'currency-changed.popup.description.default' => 'Please note that we are transitioning from {{currency_from}} to {{currency_to}} as the default currency. No action is required; your balance will be automatically updated.',
        ]
    ];
}
