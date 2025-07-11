<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class UpdateEncoreAndBosFAQpageAlias extends Seeder
{
    private Connection $connection;
    private string $brand;
    private string $table = 'localized_strings';
    private array $languages = [
        'br', 'cl', 'da', 'de', 'dgoj', 'en', 'es', 
        'fi', 'hi', 'it', 'ja', 'no', 'on', 'pe', 'sv'
    ];
    private array $brandPageAlias = [
        'videoslots' => 'battle.of.slots.battle-of-slots.html',
        'mrvegas' => 'simple.1626.html',
    ];
    private array $data = [];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        
        if (isset($this->brandPageAlias[$this->brand])) {
            $this->data = $this->prepareAliasContent();
        }
    }

    private function prepareAliasContent(): array
    {
        $data = [];
        foreach ($this->languages as $lang) {
            $filePath = $this->getFilePath($lang);
            $newValue = $this->readFile("{$filePath}.new.txt");
            $oldValue = $this->readFile("{$filePath}.old.txt");

            if ($newValue === null || $oldValue === null) {
                error_log("Missing file content for language: $lang");
                continue;
            }

            $data[$lang] = [
                'value' => $newValue,
                'old_value' => $oldValue,
            ];
        }
        return $data;
    }

    private function getFilePath(string $lang): string
    {
        return __DIR__ . "/../data/bos-encore-faq-page/{$this->brand}/{$lang}/{$this->brandPageAlias[$this->brand]}";
    }

    private function readFile(string $filePath): ?string
    {
        if (!file_exists($filePath)) {
            error_log("File not found: $filePath");
            return null;
        }
        $content = file_get_contents($filePath);
        if ($content === false) {
            error_log("Failed to read file: $filePath");
            return null;
        }
        return $content;
    }

    private function getPageAlias(): string
    {
        return $this->brandPageAlias[$this->brand];
    }

    public function up()
    {
        if (!isset($this->brandPageAlias[$this->brand])) {
            return;
        }

        foreach ($this->data as $lang => $row) {
            $this->connection
                ->table($this->table)
                ->where('alias', $this->getPageAlias())
                ->where('language', $lang)
                ->update(['value' => $row['value']]);
        }
    }

    public function down()
    {
        if (!isset($this->brandPageAlias[$this->brand])) {
            return;
        }

        foreach ($this->data as $lang => $row) {
            $this->connection
                ->table($this->table)
                ->where('alias', $this->getPageAlias())
                ->where('language', $lang)
                ->update(['value' => $row['old_value']]);
        }
    }
}
