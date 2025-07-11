<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class RemoveITFromNoDepositAndNewBonusEmailConfigs extends Seeder
{
    protected $table;

    public function init()
    {
        $this->table = 'config';
    }

    /**
     * Generating an array of target config names
     * nodeposit-newbonusoffers-mail-1 to 15
     * deposit-newbonusoffers-mail-1 to 15
     */
    protected function getTargetConfigNames(): array
    {
        $names = [];
        foreach (range(1, 15) as $i) {
            $names[] = "nodeposit-newbonusoffers-mail-$i";
            $names[] = "deposit-newbonusoffers-mail-$i";
        }
        return $names;
    }

    public function up()
    {
        DB::loopNodes(function ($connection) {
            $configNames = $this->getTargetConfigNames();

            //Fetching target config records
            $configs = $connection->table($this->table)
                ->where('config_tag', 'bonus-templates')
                ->whereIn('config_name', $configNames)
                ->get();

            foreach ($configs as $config) {
                if (!empty($config) && strpos($config->config_value, 'included_countries::') !== false) {
                    $lines = explode("\n", $config->config_value);
                    $updated = false;

                    //processing each line and remove IT if exists 
                    $newLines = array_map(function ($line) use (&$updated) {
                        if (strpos($line, 'included_countries::') === 0) {
                            list($key, $value) = explode('::', $line, 2);
                            $countries = preg_split('/\s+/', trim($value));

                            //removing IT from the list of countries
                            if (in_array('IT', $countries)) {
                                $countries = array_filter($countries, fn($c) => $c !== 'IT');
                                $updated = true;
                            }

                            return $key . '::' . implode(' ', $countries);
                        }
                        return $line;
                    }, $lines);

                    //if changes done, updating the config_value
                    if ($updated) {
                        $newConfigValue = implode("\n", $newLines);
                        $connection->table($this->table)
                            ->where('id', $config->id)
                            ->update(['config_value' => $newConfigValue]);
                    }
                }
            }
        }, true);
    }

    public function down()
    {
        DB::loopNodes(function ($connection) {
            $configNames = $this->getTargetConfigNames();

            //Fetching target config records
            $configs = $connection->table($this->table)
                ->where('config_tag', 'bonus-templates')
                ->whereIn('config_name', $configNames)
                ->get();

            foreach ($configs as $config) {
                if (!empty($config) && strpos($config->config_value, 'included_countries::') !== false) {
                    $lines = explode("\n", $config->config_value);
                    $updated = false;

                    //processing each line and add IT if missing
                    $newLines = array_map(function ($line) use (&$updated) {
                        if (strpos($line, 'included_countries::') === 0) {
                            list($key, $value) = explode('::', $line, 2);
                            $countries = preg_split('/\s+/', trim($value));

                            if (!in_array('IT', $countries)) {
                                $countries[] = 'IT';
                                sort($countries);
                                $updated = true;
                            }

                            return $key . '::' . implode(' ', $countries);
                        }
                        return $line;
                    }, $lines);

                    //if changes done, updating the config_value
                    if ($updated) {
                        $newConfigValue = implode("\n", $newLines);
                        $connection->table($this->table)
                            ->where('id', $config->id)
                            ->update(['config_value' => $newConfigValue]);
                    }
                }
            }
        }, true);
    }
}
