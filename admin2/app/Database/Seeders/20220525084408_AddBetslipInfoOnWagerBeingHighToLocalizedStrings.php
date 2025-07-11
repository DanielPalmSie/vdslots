<?php
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\SeederTranslation;

class AddBetslipInfoOnWagerBeingHighToLocalizedStrings extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'sb.betslip.info_wager_amount' => 'You are allowed to bet {{wagerLimit}}'
        ]
    ];

    private $connection;

    private string $localized_strings_connections_table = 'localized_strings_connections';

    public function init()
    {
        parent::init();
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        parent::up();

        foreach ($this->data as $language => $translation) {
            foreach ($translation as $alias => $value) {
                $exist = $this->connection
                    ->table($this->localized_strings_connections_table)
                    ->where('target_alias', '=', $alias)
                    ->where('bonus_code', '=', 0)
                    ->first();

                if (!empty($exist)) {
                    continue;
                }

                $this->connection
                    ->table($this->localized_strings_connections_table)
                    ->insert([
                        [
                            'target_alias' => $alias,
                            'bonus_code' => 0,
                            'tag' => 'sb.betslip',
                        ]
                    ]);
            }
        }
    }

    public function down()
    {
        parent::down();

        foreach ($this->data as $language => $translation) {
            foreach ($translation as $alias => $value) {
                $this->connection
                    ->table($this->localized_strings_connections_table)
                    ->where('target_alias', $alias)
                    ->where('tag', 'sb.betslip')
                    ->delete();
            }
        }
    }
}