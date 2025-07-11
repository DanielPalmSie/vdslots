<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class UpdateEditProfileAlias extends Seeder
{

    private string $table;
    private Connection $connection;

    public function init()
    {
        $this->table = 'localized_strings';
        $this->connection = DB::getMasterConnection();
    }

    private $languageStrings = [
        'register.email',
        'register.mobile',
        'register.city',
        'register.zipcode',
        'register.address',
        'register.password',
        'register.password2',
        'register.password3',
        'register.chooselang'
    ];

    public function up()
    {
        $strings = $this->connection
            ->table($this->table)
            ->whereIn('alias', $this->languageStrings)
            ->get();

        foreach ($strings as $languageString) {
            $this->connection
                ->table($this->table)
                ->where('alias', $languageString->alias)
                ->where('language', $languageString->language)
                ->update([
                    'value' => $this->removeLastAsterisk($languageString->value)
                ]);
        }
    }

    public function down()
    {
        $strings = $this->connection
            ->table($this->table)
            ->whereIn('alias', $this->languageStrings)
            ->get();

        foreach ($strings as $languageString) {
            $this->connection
                ->table($this->table)
                ->where('alias', $languageString->alias)
                ->where('language', $languageString->language)
                ->update([
                    'value' => $this->addLastAsterisk($languageString->value)
                ]);
        }
    }

    function removeLastAsterisk($string) {
        return rtrim($string, ' *');
    }

    function addLastAsterisk($string) {
        if (substr($string, -1) === '*') {
            return $string;
        }
        return $string.'*';
    }
}
