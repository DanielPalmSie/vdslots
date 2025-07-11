<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class FixAMLConfigType extends Seeder
{
    public function up()
    {
        Config::whereIn('config_name', [
            'AML23-frequency',
            'AML17-frequency',
            'AML19-frequency',
            'AML28-frequency',
            'AML28-target',
        ])->update([
            'config_type' => json_encode([
                "type" => "text",
            ], JSON_THROW_ON_ERROR)
        ]);

        Config::where('config_name', 'action-limit-recipients')
            ->where('config_tag', 'emails')
            ->update([
                'config_type' => json_encode([
                    "type" => "template",
                    "next_data_delimiter" => ",",
                    "format" => "<:Email><delimiter>"
                ], JSON_THROW_ON_ERROR)
            ]);

        Config::whereIn('config_name', ['AML23', 'AML49'])
            ->where('config_tag', 'AML')
            ->update([
                'config_type' => json_encode([
                    "type" => "template",
                    "delimiter" => ":",
                    "next_data_delimiter" => " ",
                    "format" => "<:Name><delimiter><:Number>"
                ], JSON_THROW_ON_ERROR)
            ]);

        Config::where('config_name', 'muted-events')
            ->where('config_tag', 'event-queues')
            ->update([
                'config_type' => json_encode([
                    "type" => "template",
                    "next_data_delimiter" => ",",
                    "format" => "<:String><delimiter>"
                ], JSON_THROW_ON_ERROR)
            ]);

        Config::where('config_name', 'nodes')
            ->where('config_tag', 'event-queues')
            ->update([
                'config_type' => json_encode([
                    "type" => "template",
                    "next_data_delimiter" => ",",
                    "format" => "<:Number><delimiter>"
                ], JSON_THROW_ON_ERROR)
            ]);

        Config::where('config_name', 'enabled')
            ->where('config_tag', 'event-queues')
            ->update([
                'config_type' => json_encode([
                    "type" => "text",
                ], JSON_THROW_ON_ERROR)
            ]);
    }
}