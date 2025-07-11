<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForUnknownDepositFailureError extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'paynplay.deposit.unknown.failure.description' => "We’re sorry, but an unexpected error has occurred during the deposit process.
                            Please try again later. If the problem persists, please contact our customer service at support@kungaslottet.se for more information."
        ]
    ];
}