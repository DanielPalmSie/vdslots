<?php
namespace App\Commands\Export;

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Ivoba\Silex\Command\Command;
use PDO;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZipArchive;

abstract class ExportBaseCommand extends Command
{
    protected const MASTER_TABLE_PREFIX = 'm';
    protected array $files = [];
    protected ?string $target_dir;
    protected ?string $target_filename;
    private ?int $shard_current = null;

    abstract protected function collectData(): void;

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->init($input, $output);
        $this->collectData();
        $this->finish($input, $output);

        return 0;
    }

    protected function configure()
    {
         $this->addOption('dir', 'd', InputOption::VALUE_OPTIONAL, 'Target directory to save the file. Default: <storage folder>/export')
             ->addOption('file', 'f', InputOption::VALUE_OPTIONAL, 'Set the name for the zip file, without extension.');
    }

    protected function init(InputInterface $input, OutputInterface $output): void
    {
        $this->target_dir = $input->getOption('dir');
        $this->target_filename = $input->getOption('file');
    }

    protected function finish(InputInterface $input, OutputInterface $output): void
    {
    }

    protected function putDataIntoFile(string $table_name, iterable $data): void
    {
        $first = true;

        foreach ($data as $datum) {
            if ($first) {
                $columns = array_keys($datum);
                $file = $this->createFile($columns, $table_name);
                $this->files[] = $file;
                $first = false;
            }

            $this->addRow($file['handler'], $datum);
        }
    }

    protected function createFile(array $columns, string $table_name): array
    {
        $shard = $this->getShardCurrent();
        $tmp_filename = tempnam(sys_get_temp_dir(), "sh");
        $filename = dirname($tmp_filename) . DIRECTORY_SEPARATOR . 'f' . ($shard !== null ? "__{$table_name}__{$shard}" : "__{$table_name}__" . self::MASTER_TABLE_PREFIX);
        rename($tmp_filename, $filename);
        $handler = fopen($filename, 'w');
        $file = ['filename' => $filename, 'handler' => $handler, 'table_name' => $table_name, 'shard' => $shard];
        $this->addRow($file['handler'], $columns);

        return $file;
    }

    protected function addRow($handler, array $data): void
    {
        fputcsv($handler, $data);
    }

    protected function zipAll(string $file_name_prefix = 'export'): string
    {
        if (empty($this->files)) {
            throw new \InvalidArgumentException('Empty property `files`');
        }

        $zip = new ZipArchive();

        $file_path = $this->target_dir ?? getenv('STORAGE_PATH') . "/export";

        if (!file_exists($file_path) && !mkdir($file_path, 0755, true) && !is_dir($file_path)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $file_path));
        }

        $file_name_prefix = $this->target_filename ?? (date('YmdHis').'_'.$file_name_prefix);

        $zip_file_name =  "{$file_path}/{$file_name_prefix}.zip";
        $zip->open($zip_file_name, ZipArchive::CREATE);

        foreach($this->files as $file) {
            if($file['handler']) {
                fclose($file['handler']);
            }
            $file_name = basename($file['filename']);
            $zip->addFile($file['filename'], $file_name);
        }

        $zip->close();

        // we have to unlink temp files only after zip is closed
        foreach($this->files as $file) {
            unlink($file['filename']);
        }

        return $zip_file_name;
    }

    protected function anonymizeIP(string $ip_num): string
    {
        if(strpos($ip_num, ':')){ //IPv6, we remove the first and last blocks
            $ip = explode(':', $ip_num);
            $ip[0] = '100'; //"discard address" reserved block
            $ip[count($ip)-1] = '0';
            return implode(':', $ip);
        }
        return substr($ip_num, 0,  strrpos($ip_num, '.')) . '.0';
    }

    protected function anonymizeCardHash(string $card_hash): string
    {
        return substr($card_hash, 0, 2).'** **** **** **'.substr($card_hash, -2);
    }

    protected function setShardCurrent(?int $shard = null): void
    {
        $this->shard_current = $shard;
    }

    protected function getShardCurrent(): ?int
    {
        return $this->shard_current;
    }

    /**
     * @return Connection
     */
    protected function getMasterConnection(): Connection
    {
        $conn = DB::getMasterConnection();
        $conn->setFetchMode(PDO::FETCH_ASSOC);

        return $conn;
    }
}
