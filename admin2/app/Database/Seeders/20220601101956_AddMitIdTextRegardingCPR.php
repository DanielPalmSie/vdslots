<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddMitIdTextRegardingCPR extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'mitid.cpr.error' => 'You did not enter valid CPR number, please try again. Please contact our Customer Service via live chat or email <b>(support@videoslots.com)</b> if you have any further questions.',
            'verify.with.nid.mitid.dk' => 'Verify with MIT ID'
        ]
    ];
}