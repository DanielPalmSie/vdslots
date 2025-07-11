<?php
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\SeederTranslation;
use App\Extensions\Database\Connection\Connection;

class AddLocalizedStringForBandyPromotion extends SeederTranslation
{
    private string $table = 'localized_strings';
    private Connection $connection;
    private string $brand;

    protected array $data = [
        'en' => [
            'seasonal.privacy.agree.personal.data.marketing' => 'I agree to my personal data being processed for marketing communications from {{1}} in accordance with the',
            'seasonal.conform.18.years' => "I confirm I'm 18 years or older.",
            'seasonal.promotion.already-participated' => 'Oops! It looks like you have already taken part in this seasonal offer.',
            'seasonal.promotion.form.submitted' => 'Your submission was successful! Please check your email for further details',
        ]
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();

        $brandSpecificTranslations = [
            'sv' => [
                'bandy.goals' => 'Bandymål',
                'goals' => 'Mål',
                'seasonal.promotion.content.main.bandy' => "Gissa målen och vinn Bandy-merch! <br/> Skicka in din gissning nedan för att delta.",
                'seasonal.promotion.term.condition.bandy.html' => "<div class='promotion-partner__term-condition-bandy'>
                    <h4>Regler och villkor</h4>
                    <p>1.Promoter: Videoslots Ltd (‘Immense Group’), Telghet Gwardiamangia 105, Tal Pieta, Malta.</p>
                    <p>2.Promoter: Videoslots Ltd (‘Immense Group’), Telghet Gwardiamangia 105, Tal Pieta, Malta.</p>
                    <p>3.Promoter: Videoslots Ltd (‘Immense Group’), Telghet Gwardiamangia 105, Tal Pieta, Malta.</p>
                    <p>4.Promoter: Videoslots Ltd (‘Immense Group’), Telghet Gwardiamangia 105, Tal Pieta, Malta.</p>
                    <p>5.Promoter: Videoslots Ltd (‘Immense Group’), Telghet Gwardiamangia 105, Tal Pieta, Malta.</p>
                    <p>6.Promoter: Videoslots Ltd (‘Immense Group’), Telghet Gwardiamangia 105, Tal Pieta, Malta.</p>
                    <p>7.Promoter: Videoslots Ltd (‘Immense Group’), Telghet 105, Tal Pieta, Malta.</p>

                </div>",
            ]
        ];


        if ($this->isTargetBrand()) {
            foreach ($brandSpecificTranslations['sv'] as $key => $value) {
                $this->data['sv'][$key] = $value;
            }
        }

    }

    public function up()
    {
        $this->init(); // Ensure data is populated

        if (!$this->isTargetBrand()) {
            return;
        }

        foreach ($this->data as $language => $translations) {
            foreach ($translations as $alias => $value) {
                $exists = $this->connection
                    ->table($this->table)
                    ->where('alias', $alias)
                    ->where('language', $language)
                    ->first();

                if (!empty($exists)) {
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
        $this->init(); // Ensure data is populated

        if (!$this->isTargetBrand()) {
            return;
        }

        foreach ($this->data as $language => $translations) {
            foreach ($translations as $alias => $value) {
                $this->connection
                    ->table($this->table)
                    ->where('alias', $alias)
                    ->where('language', $language)
                    ->delete();
            }
        }
    }

    private function isTargetBrand(): bool
    {
        return $this->brand === phive('BrandedConfig')::BRAND_DBET;
    }
}
