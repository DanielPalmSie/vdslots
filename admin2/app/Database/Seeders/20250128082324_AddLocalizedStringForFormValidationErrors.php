<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForFormValidationErrors extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'email.required' => 'Email field is required',
            'email.invalid.format' => 'Please enter a valid email format',
            'email.local.length.exceeded' => 'The local part of the email address must be 64 characters or fewer.',
            'email.length.exceeded' => "The email address must be {{maxLength}} characters or fewer.",
            'subject.required' => 'Subject field is required',
            'message.required' => 'Message field is required',
            'captcha.required' => 'Captcha field is required',
        ]
    ];
}