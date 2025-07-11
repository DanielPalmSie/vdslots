<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddRtpOverallTranslations extends Seeder
{

    private $connection;

    private string $table = 'localized_strings';

    private array $data = [
        ['alias' => 'account.mobile-rtp_overall.headline', 'language' => 'en', 'value' => 'Overall RTP'],
        ['alias' => 'account.mobile-rtp_hi.headline', 'language' => 'en', 'value' => 'Highest RTP'],
        ['alias' => 'account.mobile-rtp_low.headline', 'language' => 'en', 'value' => 'Lowest RTP'],
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $this->connection
            ->table($this->table)
            ->insert($this->data);
    }

    public function down()
    {
        $aliases = array_column($this->data, 'alias');
        $this->connection
            ->table($this->table)
            ->whereIn('alias', $aliases)
            ->delete();
    }
}
