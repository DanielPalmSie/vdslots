<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class AddZendeskChatTranslations extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;
    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'chat.offline',
            'value' => 'Chat Offline'
        ],
        [
            'language' => 'en',
            'alias' => 'chat.offline.message.title',
            'value' => 'Chat Unavailable'
        ],
        [
            'language' => 'en',
            'alias' => 'chat.offline.message.subtitle',
            'value' => 'Scheduled Maintenance in Progress'
        ]
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->table = 'localized_strings';
    }

    public function up()
    {
        if ($this->brand === 'videoslots') {
            $this->data[] =
                [
                    'language' => 'en',
                    'alias' => 'chat.offline.message.description',
                    'value' => 'We appreciate your interest in our live chat feature.
                                Please note that the chat function is currently undergoing scheduled maintenance and will be temporarily disabled during this time.
                                We apologise for any inconvenience caused. We kindly request your patience and understanding.
                                If you have any urgent inquiries or require immediate assistance,
                                please reach out to us via email at <strong><u>support@videoslots.com</u></strong>.</p>
                                <p>Thank you for your cooperation, and we look forward to serving you better when maintenance is complete.</p>'
                ];

            $this->connection
                ->table($this->table)
                ->insert($this->data);
        } elseif ($this->brand === 'mrvegas') {
            $this->data[] =
                [
                    'language' => 'en',
                    'alias' => 'chat.offline.message.description',
                    'value' => '<p>We appreciate your interest in our live chat feature.
                                Please note that the chat function is currently undergoing scheduled maintenance and will be temporarily disabled during this time.
                                We apologise for any inconvenience caused. We kindly request your patience and understanding.
                                If you have any urgent inquiries or require immediate assistance,
                                please reach out to us via email at <strong><u>support@mrvegas.com</u></strong>.</p>
                                <p>Thank you for your cooperation, and we look forward to serving you better when maintenance is complete.</p>'
                ];

            $this->connection
                ->table($this->table)
                ->insert($this->data);
        }
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->whereIn('alias',
                [
                    'chat.offline',
                    'chat.offline.message.title',
                    'chat.offline.message.subtitle',
                    'chat.offline.message.description'
                ]
            )
            ->where('language', 'en')
            ->delete();
    }
}