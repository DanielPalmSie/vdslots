<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddLocalizedStringsForWithdrawalsDBET extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;


    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'withdraw.desktop.start.headline',
            'value' => 'Make a secure withdrawal from DBET'
        ],
        [
            'language' => 'en',
            'alias' => 'withdraw.desktop.start.html',
            'value' => '<p>It\'s easy and secure to make a withdrawal from DBET.</p><p>When you withdraw funds from dbet.com, you need to use the same payment method as when you made your deposit.</p><p>You have one fee-free withdrawal per day, but you can, of course, withdraw more frequently if you\'d like. Those withdrawals come with a {{csym}} {{modm:2.50}} fee though.</p><p>Note that you need to verify your account to be able to withdraw. This is done in the "Document" section in your profile. If you have any questions, please email <a href=\"mailto:support@dbet.com\">support@dbet.com</a>. They will be happy to assist you.</p><p>All withdrawals are manually checked, and they are processed daily.</p>'
        ],
        [
            'language' => 'sv',
            'alias' => 'withdraw.desktop.start.headline',
            'value' => 'Gör ett säkert uttag från DBET'
        ],
        [
            'language' => 'sv',
            'alias' => 'withdraw.desktop.start.html',
            'value' => '<p>Det &auml;r enkelt och s&auml;kert att g&ouml;ra ett uttag fr&aring;n DBET.</p>
            <p>N&auml;r du tar ut pengar fr&aring;n Videoslots.com m&aring;ste du anv&auml;nda samma <br /> betalningsmetod som n&auml;r du gjorde din ins&auml;ttning.</p>
            <p>Du har ett avgiftsfritt uttag per dag, men du kan naturligtvis ta ut oftare om du vill. Dessa uttag kommer med en {{csym}} {{modm:2.50}} avgift dock.</p>
            <p>Observera att du m&aring;ste verifiera ditt konto f&ouml;r att kunna g&ouml;ra uttag. Detta g&ouml;rs i avsnittet "Dokument" i din profil. Om du har n&aring;gra fr&aring;gor, v&auml;nligen maila <a href="mailto:support@dbet.com">support@dbet.com.</a> De kommer g&auml;rna att hj&auml;lpa dig.</p>
            <p>Alla uttag kontrolleras manuellt och de behandlas dagligen.</p>',
        ],
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->table = 'localized_strings';
    }

    public function up()
    {
        $this->connection
            ->table($this->table)
            ->where('alias', 'withdraw.desktop.start.image')
            ->delete();

        if($this->brand === 'dbet') {
            foreach ($this->data as $entry) {
                $this->connection
                    ->table($this->table)
                    ->where('language', $entry['language'])
                    ->where('alias', $entry['alias'])
                    ->update(['value' => $entry['value']]);
            }
        }
    }

    public function down()
    {
        if($this->brand === 'dbet') {
            $this->connection
                ->table($this->table)
                ->whereIn('alias', ['withdraw.desktop.start.html', 'withdraw.desktop.start.headline'])
                ->whereIn('language', ['en', 'sv'])
                ->update(['value' => '']);
        }
    }
}
