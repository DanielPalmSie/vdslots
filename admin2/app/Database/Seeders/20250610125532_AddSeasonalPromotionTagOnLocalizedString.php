<?php
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\SeederTranslation;
use App\Extensions\Database\Connection\Connection;

class AddSeasonalPromotionTagOnLocalizedString extends SeederTranslation
{
    // Test pipeline trigger 7
    private string $table = 'localized_strings';
    private Connection $connection;
    private string $brand;

    protected array $data = [];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();

        // Base aliases to copy
        $originalAliases = [
            'seasonal.promotion.content.main.html',
            'seasonal.promotion.term.condition.html',
            'seasonal.winner.prize.agree',
        ];

        // Define prefix based on brand
        $prefix = null;
        if ($this->brand === phive('BrandedConfig')::BRAND_MRVEGAS) {
            $prefix = 'shw.';
        } elseif ($this->brand === phive('BrandedConfig')::BRAND_MEGARICHES) {
            $prefix = 'wba.';
        }

        // Populate data only for supported brands
        if ($prefix !== null) {
            foreach ($originalAliases as $alias) {
                $value = $this->getTranslationValue($alias, 'en');
                if ($value !== null) {
                    $this->data['en']["{$prefix}{$alias}"] = $value;
                }
            }
        }
    }

    private function getTranslationValue(string $alias, string $language): ?string
    {
        $row = $this->connection
            ->table($this->table)
            ->where('alias', $alias)
            ->where('language', $language)
            ->first();

        return $row->value ?? null;
    }

    public function up()
    {
        if (empty($this->data)) {
            return;
        }

        foreach ($this->data as $language => $translations) {
            foreach ($translations as $alias => $value) {
                $exists = $this->connection
                    ->table($this->table)
                    ->where('alias', $alias)
                    ->where('language', $language)
                    ->first();

                if ($exists) {
                    $this->connection
                        ->table($this->table)
                        ->where('alias', $alias)
                        ->where('language', $language)
                        ->update(['value' => $value]);
                } else {
                    $this->connection
                        ->table($this->table)
                        ->insert([
                            'alias' => $alias,
                            'language' => $language,
                            'value' => $value,
                        ]);
                }
            }
        }
    }

    public function down()
    {
        if (empty($this->data)) {
            return;
        }

        foreach ($this->data as $language => $translations) {
            foreach (array_keys($translations) as $alias) {
                $this->connection
                    ->table($this->table)
                    ->where('alias', $alias)
                    ->where('language', $language)
                    ->delete();
            }
        }
    }
}





