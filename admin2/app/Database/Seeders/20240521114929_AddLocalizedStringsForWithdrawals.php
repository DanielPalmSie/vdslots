<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddLocalizedStringsForWithdrawals extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;


    protected array $data = [
        [
            'language' => 'en',
            'alias' => 'withdraw.desktop.start.headline',
            'value' => 'Make a secure withdrawal from Mega Riches'
        ],
        [
            'language' => 'en',
            'alias' => 'withdraw.desktop.start.html',
            'value' => '<p>It\'s easy and secure to make a withdrawal from Megariches.com.</p><p>When you withdraw funds from Megariches.com, you need to use the same payment method as when you made your deposit.</p><p>You have one fee-free withdrawal per day, but you can, of course, withdraw more frequently if you\'d like. Those withdrawals come with a {{csym}} {{modm:2.50}} fee though.</p><p>Note that you need to verify your account to be able to withdraw. This is done in the "Document" section in your profile. If you have any questions, please email <a href=\"mailto:support@megariches.com\">support@megariches.com</a>. They will be happy to assist you.</p><p>All withdrawals are manually checked, and they are processed daily.</p>'
        ],
        [
            'language' => 'da',
            'alias' => 'withdraw.desktop.start.headline',
            'value' => 'Foretag en sikker udbetaling fra Mega Riches'
        ],
        [
            'language' => 'da',
            'alias' => 'withdraw.desktop.start.html',
            'value' => '<p>Det er nemt og sikkert at lave en udbetaling fra Megariches.com.</p><p>N&aring;r du laver en udbetaling fra Megariches.com, skal du udbetale til det samme betalingsmiddel du brugte, da du lavede din indbetaling.</p><p>Du har en gebyrfri udbetaling pr. dag, men du kan naturligvis udbetale oftere, hvis du &oslash;nsker det. Disse udbetalinger har dog et gebyr p&aring; {{csym}} {{modm:2.50}}.</p><p>V&aelig;r opm&aelig;rksom p&aring;, du skal verificere din konto, for at have mulighed for at udbetale. Dette bliver gjort i &quot;Dokument&quot; sektionen p&aring; din profil. Hvis du har nogen sp&oslash;rgsm&aring;l, s&aring; send en email til <a href=&quot;mailto:support@megariches.com&quot;>support@megariches.com</a>. De vil med gl&aelig;de hj&aelig;lpe dig.</p><p>Alle udbetalinger bliver tjekket manuelt, og de er bearbejdet hver dag.</p>',
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

        if($this->brand === 'megariches') {
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
        if($this->brand === 'megariches') {
            $this->connection
                ->table($this->table)
                ->whereIn('alias', ['withdraw.desktop.start.html', 'withdraw.desktop.start.headline'])
                ->where('language', 'en')
                ->delete();
        }
    }
}
