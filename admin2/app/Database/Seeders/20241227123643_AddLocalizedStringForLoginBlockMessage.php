<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForLoginBlockMessage extends SeederTranslation
{
    protected array $data = [
        "en" => [
            "blocked.login_block.html" => "<p>We're sorry, but logging in from your current location is not supported at this time. please contact our support team for assistance</p>",
        ]
    ];
}
