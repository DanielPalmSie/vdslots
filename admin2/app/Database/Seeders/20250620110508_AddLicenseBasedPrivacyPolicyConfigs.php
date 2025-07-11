<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

/**
 * Adds pp-version and pp-page configs for all license tags
 */
class AddLicenseBasedPrivacyPolicyConfigs extends Seeder
{
    private const TABLE = 'config';

    private const MGA_CONFIG_TAG = 'users';
    private const PP_VERSION_CONFIG_NAME = 'pp-version';
    private const PP_VERSION_CONFIG_TYPE = '{"type":"number"}';
    private const PP_PAGE_CONFIG_NAME = 'pp-page';
    private const PP_PAGE_CONFIG_TYPE = '{"type":"text"}';
    private const PP_PAGE_CONFIG_VALUE = 'simple.1261.html';

    private const LICENSE_TAGS = [
        'license-de',
        'license-dk',
        'license-es',
        'license-gb',
        'license-it',
        'license-nl',
        'license-se',
        'license-ca-on',
    ];

    private $connection;

    private string $currentConfigValue;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->currentConfigValue = (string)$this->connection->table(self::TABLE)
            ->where('config_tag', self::MGA_CONFIG_TAG)
            ->where('config_name', self::PP_VERSION_CONFIG_NAME)
            ->max('config_value');
    }

    public function up()
    {
        foreach (self::LICENSE_TAGS as $tag) {
            // Insert pp-version config (ignore if already exists)
            $this->connection->table(self::TABLE)
                ->insert([
                    'config_name' => self::PP_VERSION_CONFIG_NAME,
                    'config_tag' => $tag,
                    'config_value' => $this->currentConfigValue,
                    'config_type' => self::PP_VERSION_CONFIG_TYPE,
                ]);

            // Insert pp-page config (ignore if already exists)
            $this->connection->table(self::TABLE)
                ->insert([
                    'config_name' => self::PP_PAGE_CONFIG_NAME,
                    'config_tag' => $tag,
                    'config_value' => self::PP_PAGE_CONFIG_VALUE,
                    'config_type' => self::PP_PAGE_CONFIG_TYPE,
                ]);
        }

        // Insert pp-page config for MGA (ignore if already exists)
        $this->connection->table(self::TABLE)
            ->insert([
                'config_name' => self::PP_PAGE_CONFIG_NAME,
                'config_tag' => self::MGA_CONFIG_TAG,
                'config_value' => self::PP_PAGE_CONFIG_VALUE,
                'config_type' => self::PP_PAGE_CONFIG_TYPE,
            ]);
    }

    public function down()
    {
        foreach (self::LICENSE_TAGS as $tag) {
            // Delete pp-version config
            $this->connection->table(self::TABLE)
                ->where('config_name', self::PP_VERSION_CONFIG_NAME)
                ->where('config_tag', $tag)
                ->delete();

            // Delete pp-page config
            $this->connection->table(self::TABLE)
                ->where('config_name', self::PP_PAGE_CONFIG_NAME)
                ->where('config_tag', $tag)
                ->delete();
        }

        // Delete pp-page config for MGA
        $this->connection->table(self::TABLE)
            ->where('config_name', self::PP_PAGE_CONFIG_NAME)
            ->where('config_tag', self::MGA_CONFIG_TAG)
            ->delete();
    }
}
