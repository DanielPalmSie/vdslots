<?php

use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\SeederTranslation;
use App\Models\Config;

/**
 * Inserts the config values and localized strings required for Closed Loop
 *
 * Usage: ./console seeder:up 20220602061518 | ./console seeder:down 20220602061518
 */
class ClosedLoop extends SeederTranslation
{
    private array $config = [
        [
            'config_name' => 'fifo-countries',
            'config_tag' => 'cashier',
            'config_type' => '{"type":"ISO2", "delimiter":" "}',
            'config_value' => ''
        ],
        [
            'config_name' => 'closed-loop-duration',
            'config_tag' => 'cashier',
            'config_type' => '{"type":"template", "delimiter":":", "next_data_delimiter":"|", "format":"<:Name><delimiter><:Number>"}',
            'config_value' => 'ROW:45'
        ]
    ];

    protected array $data = [
        'en' => [
            'closed.loop.explanation.headline' => 'Closed Loop',
            'closed.loop.explanation.body' => 'Your withdrawals are limited to the below options and the remaining withdrawal amounts. Once you have in aggregate withdrawn the amounts you will be able to withdraw further funds with other options such as bank wire.',
            'cashier.source' => 'Source',
            'closed.loop.amount' => 'Closed Loop Remaining Amount',
            'closed.loop.limit.overage.error' => "Your current closed loop situation limits you to a withdrawal as per the following: {{1}}",
            'err.disabled.by.closed.loop' => "This option is disabled due to pending closed loop withdrawals.",
            'temp.account' => 'Temporary accounts are not able to withdraw.',
            'withdraw.blocked' => 'Withdrawals have been disabled.',
            'source.of.funds.pending' => 'Source of funds is pending.',
            'pending.documents' => 'Pending KYC documents.',
            'payment.provider.missing' => 'Missing payment provider.'
        ],
    ];

    /**
     *
     */
    public function up()
    {
        parent::up();

        foreach ($this->config as $config) {
            Config::shs()->insert($config);
        }
    }

    /**
     *
     */
    public function down()
    {
        parent::down();

        $connection = DB::getMasterConnection();
        foreach ($this->config as $config) {
            $connection
                ->table('config')
                ->where('config_name', '=', $config['config_name'])
                ->where('config_tag', '=', $config['config_tag'])
                ->delete();
        }
    }
}