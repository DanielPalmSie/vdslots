<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForRedesignContactUs extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'contactus.email' => 'Your email',
            'contactus.subject' => 'Subject',
            'contactus.message' => 'Your message',
            'contactus.code' => 'Enter code',
            'contactus.start.live.chat.html' => '<p> You can <a href="#" onclick="{{click_url}}"> Start a Live Chat </a> instead. </p>'
        ]
    ];
}