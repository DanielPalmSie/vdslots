<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class ForgotPasswordScreenTranslation extends Seeder
{
    private $connection;
    private string $brand;
    private string $table = 'localized_strings';

    private array $data = [
        'forgot.password.username' => '<div class="forgotform-header">
                            <div class="forgotform-header-title">Forgot your Password?</div>
                            <div class="forgotform-header-description">Enter your username and date of birth below to have a new password sent to your email.</div>
                        </div>',
        'forgot.username.email' => '<div class="forgotform-header">
                            <div class="forgotform-header-title">Forgot your Username?</div>
                            <div class="forgotform-header-description">Enter your date of birth and your email address below to have your username sent to you.</div>
                        </div>',
    ];

    private array $all_brands = [
        'forgotform-placeholder-username' => 'Username',
        'forgotform-placeholder-email' => 'Email'
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
    }

    public function up()
    {
        $this->updateData($this->all_brands);

        if ($this->brand !== 'megariches') {
            return;
        }
        $this->updateData($this->data);
    }

    public function down()
    {

        $this->removeData($this->all_brands);
        if ($this->brand !== 'megariches') {
            return;
        }
        $this->removeData($this->data);
    }

    function updateData(array $data) {
        foreach ($data as $alias => $value) {

            $exists =  $this->connection
                ->table($this->table)
                ->where('alias', $alias)
                ->where('language', 'en')
                ->exists();

            if($exists) {
                $this->connection
                    ->table($this->table)
                    ->where('alias', $alias)
                    ->where('language', 'en')
                    ->update(['value' => $value]);
            }else {
                $this->connection
                    ->table($this->table)
                    ->insert([
                        'language' => 'en',
                        'alias' => $alias,
                        'value' => $value
                    ]);
            }
        }
    }

    function removeData(array $data) {
        foreach ($data as $alias => $value) {
            $this->connection
                ->table($this->table)
                ->where('alias', $alias)
                ->where('value', $value)
                ->where('language', 'en')
                ->delete();
        }
    }
}
