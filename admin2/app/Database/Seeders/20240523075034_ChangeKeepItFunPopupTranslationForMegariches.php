<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class ChangeKeepItFunPopupTranslationForMegariches extends Seeder
{
    private Connection $connection;
    private string $table;
    private array $translations = [
        [
            'alias' => 'ukgc.rg.popup.title',
            'value' => 'Mega Riches - Keep it fun',
            'oldValue' => 'Mr Vegas - Keep it fun'
        ],
        [
            'alias' => 'understand.accpolicy.html',
            'value' => '<div><img class="popup-v2-img" src="/diamondbet/images/megariches/keep-it-fun.png"><h6 class="popup-v2-subtitle">Keep it fun</h6><div class="popup-v2-body"><p>We are required by our licence to inform customers about what happens to funds which we hold on account for you, and the extent to which funds are protected in the event of insolvency.</p><p>We hold customer funds separate from company funds in a designated players account. This means that steps have been taken to protect customer funds but that there is no absolute guarantee that all funds will be repaid.</p><p>This meets the Gambling Commission&rsquo;s requirements for the segregation of customer funds at the level: medium protection.</p></div></div>',
            'oldValue' => '<p>We are required by our licence to inform customers about what happens to funds which we hold on account for you, and the extent to which funds are protected in the event of insolvency.</p>
<p>We hold customer funds separate from company funds in a designated players account. This means that steps have been taken to protect customer funds but that there is no absolute guarantee that all funds will be repaid.</p>
<p>This meets the Gambling Commission&rsquo;s requirements for the segregation of customer funds at the level: medium protection.</p>
<div>&nbsp;</div>'
        ],
    ];
    private string $brand;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'localized_strings';
        $this->brand = phive('BrandedConfig')->getBrand();
    }

    public function up()
    {
        if ($this->brand !== 'megariches') {
            return;
        }

        foreach ($this->translations as $translation) {
            $this->connection
                ->table($this->table)
                ->where('alias', $translation['alias'])
                ->where('language', 'en')
                ->update([
                    'value' => $translation['value']
                ]);
        }
    }

    public function down()
    {
        if ($this->brand !== 'megariches') {
            return;
        }

        foreach ($this->translations as $translation) {
            $this->connection
                ->table($this->table)
                ->where('alias', $translation['alias'])
                ->where('language', 'en')
                ->update([
                    'value' => $translation['oldValue']
                ]);
        }
    }
}
