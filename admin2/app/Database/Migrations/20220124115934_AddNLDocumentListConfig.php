<?php

use App\Models\Config;
use Phpmig\Migration\Migration;

class AddNLDocumentListConfig extends Migration
{
    private array $configs = [
        [
            'config_name' => 'registration-document-list',
            'config_tag' => 'license-nl',
            'config_type' => '{"type":"template", "delimiter":":", "next_data_delimiter":" ", "format":"<:Id><delimiter><:Description>"}',
            'config_value' => '1:identity.card 2:driving.license 3:Passportidentity.card 4:documents.residence-permit 5:documents.alien-travel 6:documents.refugee-travel'
        ],
        [
            'config_name' => 'registration-document-issuer-list',
            'config_tag' => 'license-nl',
            'config_type' => '{"type":"template", "delimiter":":", "next_data_delimiter":" ", "format":"<:Id><delimiter><:Description>"}',
            'config_value' => '1:authorities.dutch 2:authorities.foreign'
        ]
    ];

    /**
     * Do the migration
     */
    public function up()
    {
        foreach ($this->configs as $config) {
            $exists = Config::shs()
                ->where('config_name', $config['config_name'])
                ->where('config_tag', $config['config_tag'])
                ->first();

            if (empty($exists)) {
                Config::shs()->insert($config);
            }
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        foreach ($this->configs as $config) {
            Config::shs()
                ->where('config_name', $config['config_name'])
                ->where('config_tag', $config['config_tag'])
                ->delete();
        }
    }
}
