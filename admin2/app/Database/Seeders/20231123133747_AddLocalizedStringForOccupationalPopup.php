<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForOccupationalPopup extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'rg.spending.popup.main.message.html' =>
                '<p>Please note, we will automatically set a loss limit on your account of the amount you have set as your monthly gambling budget.</p>
<p>By ticking the box below and clicking "I understand", I confirm that I understand where the options are located under "Responsible Gaming" in "My Profile" and that I know how to use them.</p>',
            'rg.spending.popup.tick.box.label' => 'I Understand',
            'rg.spending.popup.bottom.message.html' => '<p class="bottom-description">Before you start playing, we recommend you review these settings.</p>'
        ]
    ];
}
