<?php 
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddLocalizedStringsForForgotPasswordForKungaMGA extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'alias'    => 'new.pwd.info.html',
            'language' => 'en',
            'value'    => '<div><img src="/diamondbet/images/kungaslottet/king_captcha.png"><p>A temporary password has been used, please change it in the below form.</p></div>'
        ],
        [
            'alias'    => 'register.password',
            'language' => 'en',
            'value'    => 'Password'
        ],
        [
            'alias'    => 'register.secpassword',
            'language' => 'en',
            'value'    => 'Password Again'
        ],
        [
            'alias'    => 'password.changed.successfully',
            'language' => 'en',
            'value'    => '<div><img src="/diamondbet/images/kungaslottet/pay-n-play/login-success.svg"><p>Success!</p><span>Your Password has been updated.</span></div>'
        ]
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->table = 'localized_strings';
    }

    public function up()
    {
        if ($this->brand === 'kungaslottet') {

            $this->connection
                ->table($this->table)
                ->whereIn('alias',['new.pwd.info.html', 'register.password', 'register.secpassword', 'password.changed.successfully'])
                ->where('language', 'en')
                ->delete();

            $this->connection
                ->table($this->table)
                ->insert($this->data);
        }
    }

    public function down()
    {
        if ($this->brand === 'kungaslottet') {
            $this->connection
                ->table($this->table)
                ->whereIn('alias',['new.pwd.info.html', 'register.password', 'register.secpassword', 'password.changed.successfully'])
                ->where('language', 'en')
                ->delete();
        }
    }
}