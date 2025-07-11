<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class ChangeBankIdLoginPopupDescription extends Seeder
{
    private $connection;

    private string $table = 'localized_strings';
    private string $alias = 'login.verification.method.instructions.html';

    private array $translations = [
        'en' => '<p>Click on the button bellow to verify with your BankId application.</p>',
        'sv' => '<p>Klicka på knappen nedan för att verifiera med din BankId-applikation.</p>',
    ];

    private array $prevTranslations = [
        'en' => '<p>Enter your NID and click Log In. Start the BankID app in your mobile or tablet and enter your security code.</p>',
        'sv' => '<p>Fyll i ditt personnummer och klicka på logga in. Starta BankID appen på din mobil eller surfplatta och mata in säkerhetskod.</p>',
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        foreach ($this->translations as $lang => $value) {
            $this->connection
                ->table($this->table)
                ->where('alias', $this->alias)
                ->where('language', $lang)
                ->update(['value' => $value]);
        }
    }

    public function down()
    {
        foreach ($this->prevTranslations as $lang => $value) {
            $this->connection
                ->table($this->table)
                ->where('alias', $this->alias)
                ->where('language', $lang)
                ->update(['value' => $value]);
        }
    }
}
