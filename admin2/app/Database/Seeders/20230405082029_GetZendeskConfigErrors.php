<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class GetZendeskConfigErrors extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'api.zendesk.error.config-incomplete' => 'Zendesk config is incomplete',
        ]
    ];
}