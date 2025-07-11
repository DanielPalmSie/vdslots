<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForVoucherCaptchaError extends SeederTranslation
{
    protected array $data = [
	    'en' => [
		    'voucher.captcha.header.text' => 'Too many failed attempts. Please enter the characters you see in the image below to proceed.',
		    'voucher.captcha.error' => 'The characters were not entered correctly. Please try again.'
	    ]
    ];
}