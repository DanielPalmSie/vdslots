<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddContentStringsForRg65 extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'credit.card.blocked' => 'You are currently being restricted from making deposits using credit cards. For more information please contact support@videoslots.com',
        ],
        'es' => [
            'credit.card.blocked' => 'Actualmente está restringido realizar depósitos con tarjetas de crédito. Para más información, póngase en contacto con support@videoslots.com',
        ]
    ];
}
