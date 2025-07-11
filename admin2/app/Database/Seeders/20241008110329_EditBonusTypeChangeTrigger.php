<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class EditBonusTypeChangeTrigger extends Seeder
{
    protected $mailTable;
    protected $localizedStringTable;
    protected $connection;
    protected $columnTitleMap = [
        'id'                      => 'Id',
        'bonus_name'              => 'Bonus Name',
        'expire_time'             => 'Expire Time',
        'num_days'                => 'Number of Days',
        'cost'                    => 'Cost',
        'reward'                  => 'Reward',
        'deposit_limit'           => 'Deposit Limit',
        'rake_percent'            => 'Rake Percent',
        'bonus_code'              => 'Bonus Code',
        'bonus_type'              => 'Bonus Type',
        'exclusive'               => 'Exclusive',
        'bonus_tag'               => 'Bonus Tag',
        'type'                    => 'Type',
        'game_tags'               => 'Game Tags',
        'cash_percentage'         => 'Cash Percentage',
        'max_payout'              => 'May Payout',
        'reload_code'             => 'Reload Code',
        'excluded_countries'      => 'Excluded Countries',
        'deposit_amount'          => 'Deposit Amount',
        'deposit_max_bet_percent' => 'Deposit Max Bet Percent',
        'bonus_max_bet_percent'   => 'Bonus Max Bet Percent',
        'max_bet_amount'          => 'Max Bet Amount',
        'included_countries'      => 'Included Countries',
        'fail_limit'              => 'Fail Limit',
        'game_percents'           => 'Game Percents',
        'loyalty_percent'         => 'Loyalty Percents',
        'top_up'                  => 'Top Up',
        'stagger_percent'         => 'Stagger Percent',
        'ext_ids'                 => 'External IDs',
        'progress_type'           => 'Progress Type',
        'deposit_threshold'       => 'Deposit Threshold',
        'game_id'                 => 'Game ID',
        'allow_race'              => 'Allow Race',
        'forfeit_bonus'           => 'Forfeit Bonus',
        'frb_coins'               => 'Free Spins Bet Coins',
        'frb_denomination'        => 'Free Spins Bet Denomination',
        'frb_lines'               => 'Free Spins Bet Lines',
        'frb_cost'                => 'Free Spins Bet Cost',
        'award_id'                => 'Award ID',
        'keep_winnings'           => 'Keep Winnings',
        'deposit_multiplier'      => 'Deposit Multiplier',
    ];

    private function getData(): array
    {
        $columns = '';
        foreach ($this->columnTitleMap as $column => $title) {
            $columns .= <<<COLUMN
                <tr>
                    <td style="text-align: center;">
                        <em><strong>$title</strong></em>
                    </td>
                    <td style="text-align: center;">
                        __OLD-BONUS-TYPE-{$column}__
                    </td>
                    <td style="text-align: center;">
                        __NEW-BONUS-TYPE-{$column}__
                    </td>
                </tr>
            COLUMN;
        }

        $content = <<<HTML
            <p> <i>Bonus type changed:</i> </p>
            <p> <i>Change timestamp: _TIMESTAMP_ </i></p>
            <p> <i>Change made by: __MADE-BY__ </i></p>
            <p>&nbsp;</p>
            <p><i>Unchanged columns marked as <b>-</b></i></p>
            <table border="1" cellpadding="1" cellspacing="1" style="width: 500px;">
                <tbody>
                    <tr>
                        <td style="text-align: center;">
                            <em><strong>Value Name</strong></em>
                        </td>
                        <td style="text-align: center;">
                            <em><strong>From</strong></em>
                        </td>
                        <td style="text-align: center;">
                            <em><strong>To</strong></em>
                        </td>
                    </tr>
                    $columns
                </tbody>
            </table>
            <p>
                <br />
                Thanks
            </p>
        HTML;

        return [
            [
                'language' => 'en',
                'alias' => 'mail.bonus-type.change.subject',
                'value' => 'Bonus type has been Edited',
            ],
            [
                'language' => 'en',
                'alias' => 'mail.bonus-type.change.content',
                'value' => $content
            ]
        ];
    }


    public function init()
    {
        $this->mailTable = 'mails';
        $this->localizedStringTable = 'localized_strings';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {

        $this->connection
            ->table($this->mailTable)
            ->insert([
                'mail_trigger' => 'bonus-type.changes',
                'subject' => 'mail.bonus-type.change.subject',
                'content' => 'mail.bonus-type.change.content'
            ]);

        $this->connection
            ->table($this->localizedStringTable)
            ->insert($this->getData());
    }

    public function down()
    {
        $this->connection
            ->table($this->mailTable)
            ->where('mail_trigger', 'bonus-type.changes')
            ->delete();

        $this->connection
            ->table($this->localizedStringTable)
            ->whereIn('alias', ['mail.bonus-type.change.subject',
                'mail.bonus-type.change.content'])
            ->delete();
    }
}
