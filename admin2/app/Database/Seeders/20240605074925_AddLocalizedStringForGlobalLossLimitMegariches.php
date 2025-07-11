<?php 
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddLocalizedStringForGlobalLossLimitMegariches extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;

    protected array $data = [
        [
            'alias'    => 'RG39.rg.info.description.html',
            'language' => 'en',
            'value'    => '
                        You have lost a substantial sum of money the last 30 days.
                        Are you sure your comfortable with that?<br/>
                        We strongly recommend you to take a break and advise you to urgently review your limits within our <br/> <span class="rg-info-popup__link"> <a href="{{accountResponsibleGamingUrl}}"> responsible gambling tools.</a> </span><br/><br/>
                        Remember, gambling should be fun and safe!<br/>
                        If it\'s not fun <strong>STOP!</strong>'
        ],
        [
            'alias'    => 'rg.info.box.top.headline',
            'language' => 'en',
            'value'    => 'Welcome Back!' 
        ]
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'localized_strings';
    }

    public function up()
    {
        $this->connection
            ->table($this->table)
            ->whereIn('alias',['RG39.rg.info.description.html', 'rg.info.box.top.headline'])
            ->where('language', 'en')
            ->delete();

        $this->connection
            ->table($this->table)
            ->insert($this->data);
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->whereIn('alias',['RG39.rg.info.description', 'rg.info.box.top.headline'])
            ->where('language', 'en')
            ->delete();
    }
}