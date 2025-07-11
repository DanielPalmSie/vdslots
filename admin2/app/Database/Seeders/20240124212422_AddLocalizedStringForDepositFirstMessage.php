<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForDepositFirstMessage extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'err.depositfirst' => "You have to deposit with this supplier before you can withdraw.",
        ]
    ];
}
