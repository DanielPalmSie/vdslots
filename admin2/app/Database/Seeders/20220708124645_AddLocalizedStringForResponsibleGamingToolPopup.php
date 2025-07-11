<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForResponsibleGamingToolPopup extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'responsible.gaming.tools.description' => 'Do you feel you understand our responsible gaming help tools available onsite?',
            'responsible.gaming.popup.header' => 'Responsible Gaming Tools',
            'responsible.gaming.message.description' => 'Responsible gaming is important to us and this is why we have a large variety of responsible 
                                                         gaming controls available to you, to help you gamble responsibly and keep it fun.',
            'responsible.gaming.visit.page.description.html' => 'Visit our <a href="{{responsibleGamingUrl}}" style="color:blue;text-decoration:underline;"> Responsible Gambling Page </a> 
                                                                 for more information and have a look at our <a href="{{accountResponsibleGamingUrl}}" style="color:blue;text-decoration:underline;"> Responsible Gaming tools </a> available within your profile.',
            'responsible.gaming.contact.description.html' => 'Please contact our Customer Support team via Email (<a href="mailto:support@videoslots.com" style="color:blue;text-decoration:underline;">support@videoslots.com </a>) or live chat if you require any further assistance.'
        ]
    ];
}