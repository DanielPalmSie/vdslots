<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class AddLocalizedStringForFreshChatErrorInfoMessage extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table;
    protected array $data;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->table = 'localized_strings';
        $this->data = [];
    }

    public function up()
    {
        if ($this->brand === 'kungaslottet') {
            $this->data[] = [
                'language' => 'en',
                'alias' => 'freshchat-error-msg',
                'value' => "We're sorry, but our live chat isn't available right now. Please reach out to us via email at support@kungaslottet.com, or try again later."
            ];
        } elseif ($this->brand === 'mrvegas') {
            $this->data[] = [
                'language' => 'en',
                'alias' => 'freshchat-error-msg',
                'value' => "We're sorry, but our live chat isn't available right now. Please reach out to us via email at support@mrvegas.com, or try again later."
            ];
        } elseif ($this->brand === 'videoslots') {
            $this->data[] = [
                'language' => 'en',
                'alias' => 'freshchat-error-msg',
                'value' => "We're sorry, but our live chat isn't available right now. Please reach out to us via email at support@videoslots.com, or try again later."
            ];
        }

        if (!empty($this->data)) {
            $this->connection
                ->table($this->table)
                ->insert($this->data);
        }
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->whereIn('alias',
                [
                    'freshchat-error-msg'
                ]
            )
            ->where('language', 'en')
            ->delete();
    }
}
