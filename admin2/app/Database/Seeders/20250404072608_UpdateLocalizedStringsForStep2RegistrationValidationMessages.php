<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class UpdateLocalizedStringsForStep2RegistrationValidationMessages extends Seeder
{
    private Connection $connection;
    private string $table;
    private string $alias;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'localized_strings';
    }

    public function up()
    {
        $updates = [
            'register.custom.error.message' => 'Must be 1-50 characters. Use letters, accented characters, apostrophes, hyphens, and spaces. Avoid special characters at the beginning and at the end.',
            'register.address.error.message' => 'Address must be 3-100 characters. Use letters, numbers, accented characters, commas, periods, hyphens, apostrophes, spaces, slashes, ampersands, and pound signs. Avoid special characters at the beginning and at the end.',
            'register.zipcode.error.message' => 'Zip code must be 3-20 characters. Use letters, numbers, hyphens, and spaces. Avoid special characters at the beginning and at the end.'
        ];
        
        foreach ($updates as $alias => $value) {
            $this->connection
                ->table($this->table)
                ->where('alias', $alias)
                ->where('language', 'en')
                ->update(['value' => $value]);
        }
            
    }
}
