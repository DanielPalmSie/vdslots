<?php

// TODO: remove this file once Review and QA is done

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/../z-config/vendor/autoload.php';
include_once __DIR__ . '/phive.php';

class VsTools
{
    public string $base_location = __DIR__;
    public string $suffix = '.config.php';
    public array $settings = [];

    public function __construct($dir)
    {
        $this->base_location .= "/$dir/";
    }

    public function export($file): void
    {
        if (empty($file)) {
            die("Didn't export.");
        }

        $result = [];
        foreach ($this->getConfigFolder() as $f) {
            $this->settings = [];
            $this->readSettings(str_replace($this->suffix, '', $f));
            $result[$f] = $this->settings;
        }

        $this->dumpToFile($file, $result);
    }

    public function compare($file1, $file2): void
    {
        if (empty($file1) || empty($file2)) {
            die("Didn't compare.");
        }
        $f1 = explode('/', $file1);
        $f2 = explode('/', $file2);
        $file = 'compare:'
            . str_replace('.json', '', $f1[count($f1) - 1])
            . '+'
            . str_replace('.json', '', $f2[count($f2) - 1])
            . '.json';

        $this->dumpToFile($file, $this->compareFiles($file1, $file2));
    }

    private function getConfigFolder(): array
    {
        return array_values(array_filter(scandir($this->base_location), function ($file) {
            return strpos($file, $this->suffix) !== false;
        }));
    }

    private function readSettings($file): void
    {
        echo $this->base_location . $file . $this->suffix . PHP_EOL;

        include $this->base_location . $file . $this->suffix;
    }

    private function dumpToFile($file, $data): void
    {
        file_put_contents(__DIR__ . '/../z-config/' . $file, json_encode($data, JSON_PRETTY_PRINT));
    }

    private function compareFiles($file1, $file2): array
    {
        $data1 = json_decode(file_get_contents($file1), true);
        $data2 = json_decode(file_get_contents($file2), true);

        $data1_res = $this->arrayToList($data1);
        $data2_res = $this->arrayToList($data2);

        $res = [];

        foreach ($data1_res as $key => $val) {
            if ($this->shouldSkip($key, $data2_res[$key])) {
                continue;
            }
            // assign the old config 
            if (empty($res[$key])) {
                $res[$key] = [
                    $file1 => $val,
                    $file2 => $data2_res[$key]
                ];
            }

            $res = $this->handleDynamicFileLocation($key, $val, $res);
            // if old config value is same as new config value then remove the item from result
            if ($res[$key][$file1] === $res[$key][$file2]) {
                unset($res[$key]);
            }
        }

        foreach ($data2_res as $key => $val) {
            if ($this->shouldSkip($key, $val)) {
                continue;
            }
            // assign the new config 
            if (empty($res[$key])) {
                $res[$key] = [
                    $file1 => $data1_res[$key], // old config value
                    $file2 => $val, // new config value
                    'delete' => true
                ];
            }

            $res = $this->handleDynamicFileLocation($key, $val, $res);
            if ($res[$key][$file1] === $res[$key][$file2]) {
                unset($res[$key]);
            }
        }

        return $res;
    }

    private function arrayToList($data, $prefix = '')
    {
        if (!is_array($data)) {
            return $data;
        }
        $tmp = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $tmp = array_merge($tmp, $this->arrayToList($value, $prefix . "[$key]"));
            } else {
                $tmp[$prefix . "[$key]"] = $this->arrayToList($value, $prefix . "[$key]");
            }
        }
        return $tmp;
    }

    /**
     * using a custom list of files which have been manually checked
     *  to ensure that bugs are not introduced
     *
     * @param $key
     * @param $new_value
     *
     * @return bool
     */
    private function shouldSkip($key, $new_value): bool
    {
        if ($_ENV['APP_ENVIRONMENT'] === 'prod') {
            // on prod test=false was added which is missing in old config 
            return $new_value === false
                && in_array($key, [
                    "[Edict.config.php][test]",
                    "[Egt.config.php][test]",
                    "[Evolution.config.php][test]",
                    "[Greentube.config.php][test]",
                    "[Mosms.config.php][test]",
                    "[Multislot.config.php][test]",
                    "[Netent.config.php][test]",
                    "[Nyx.config.php][test]",
                    "[Oryx.config.php][test]",
                    "[Pariplay.config.php][test]",
                    "[Playtech.config.php][test]",
                    "[Pragmatic.config.php][test]",
                    "[Qspin.config.php][test]",
                    "[QuickFire.config.php][test]",
                    "[Relax.config.php][test]",
                    "[Rival.config.php][test]",
                    "[SQL.config.php][test]",
                    "[Skywind.config.php][test]",
                    "[Stakelogic.config.php][test]",
                    "[Swintt.config.php][test]",
                    "[Thunderkick.config.php][test]",
                    "[Tomhorn.config.php][test]",
                    "[Wazdan.config.php][test]",
                ], true);
        }

        return false;
    }

    private function handleDynamicFileLocation($key, $val, $res): array
    {
        $exception_files = [
            '[Phive.config.php][captcha-config][png_backgrounds][0]',
            '[Phive.config.php][captcha-config][fonts][0]',
        ];

        if (in_array($key, $exception_files)) {
            if (file_exists($val)) {
                unset($res[$key]);
            } else {
                $res[$key]['wrong_file_location'] = true;
            }
        }
        return $res;
    }

    // $this->getSetting is used in config files
    private function getSetting($key)
    {
        return $this->settings[$key];
    }

    // $this->setSetting is used in config files
    private function setSetting($key, $value): void
    {
        $this->settings[$key] = $value;
    }
}

function execVsTools($folder, $file, $config_dir)
{
    $prefix = 'new_';

    (new VsTools($folder))->export($folder . ':' . $file);
    (new VsTools($config_dir))->export($config_dir . ':' . $prefix . $file);
    (new VsTools($config_dir))->compare(
        'z-config/' . $folder . ':' . $file,
        'z-config/' . $config_dir . ':' . $prefix . $file
    );
}

# To sync VS DEV: php phive/vs-tools.php config_vs_dev vs_dev.json
# To sync VS PROD: php phive/vs-tools.php config_vs_prod vs_prod.json
# To sync MRV PROD: php phive/vs-tools.php config_mrv_prod mrv_prod.json
if (!empty($_ENV['COMPARE_CONFIGS'])) {
    [, $old_config_folder, $export_file, $new_config_folder] = $argv;
    execVsTools($old_config_folder, $export_file, $new_config_folder ?? 'config-new');
}

die('DONE');

