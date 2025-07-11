<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;

class RemoveWirayaConfig extends Migration
{
    private const TABLE = 'config';
    private const CONFIG_TAG = 'crm';
    private const CONFIG_NAME = 'wiraya-language-map';
    private $connection;
    private string $brand;

    private array $config;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->config = [
            'config_name' => self::CONFIG_NAME,
            'config_tag' => self::CONFIG_TAG,
            'config_type' => '{"type":"template","delimiter":"::","next_data_delimiter":"^_^","format":"<:Language><delimiter><:ContactListId><delimiter><:WirayaProjectId>"}',
            'config_value' => 'Sweden::2::Videoslots-Activation-Sweden^_^UK::3::Videoslots-Activation-UK^_^FI::4::Videoslots-Activation-FI^_^NO::5::Videoslots-Activation-NO^_^DE::48::Videoslots-Activation-DE^_^DK::49::Videoslots-Activation-DK^_^JP::59::Videoslots-Activation-JP'
        ];
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if ($this->brand === 'mrvegas' || $this->brand === 'videoslots') {
            return;
        }

        $this->connection
            ->table(self::TABLE)
            ->where('config_tag', '=', self::CONFIG_TAG)
            ->where('config_name', '=', self::CONFIG_NAME)
            ->delete();
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if (! ($this->brand === 'mrvegas' || $this->brand === 'videoslots')) {
            return;
        }

        $this->connection
            ->table(self::TABLE)
            ->insert($this->config);
    }
}
