<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForDepositToPlayMessage extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'paynplay.deposit.to.play.message' => 'Deposit to Play',
            'paynplay.deposit.play.description' => 'You are required to deposit to be able to play.'
        ]
    ];
}