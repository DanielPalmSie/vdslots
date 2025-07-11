<?php

namespace Tests\Unit\Phpunit;

use App\Models\Currency;
use App\Models\RiskProfileRating;
use App\Models\User;
use App\Repositories\RiskProfileRatingRepository;
use PHPUnit\Framework\TestCase;
use Silex\Application;
use Tests\Unit\Phpunit\Traits\ApplicationTrait;

class RiskProfileRatingRepositoryTest extends TestCase
{
    use ApplicationTrait;

    /**
     * @var Application|null
     */
    private static ?Application $app;

    private static ?User $user;
    private static array $country_jurisdiction_map;
    private static array $rating_settings;

    private $risk_profile_rating_repository;

    public static function setUpBeforeClass(): void
    {
        self::loadApplication();
        self::$user = User::where('username', 'LIKE', "%{$_ENV['test_username']}%")->first();
        self::$rating_settings = RiskProfileRatingRepository::rating_settings;
    }

    public static function tearDownAfterClass(): void
    {
        self::$app = null;
        self::$rating_settings = [];
        self::$country_jurisdiction_map = [];
        self::$user = null;
    }

    public function setUp(): void
    {
        self::$country_jurisdiction_map = phive('Licensed')->getSetting('country_by_jurisdiction_map');
        $this->risk_profile_rating_repository = \Mockery::mock(
            RiskProfileRatingRepository::class,
            [self::$app, new Currency])
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
    }

    protected function tearDown(): void
    {
        \Mockery::close();
    }

    /**
     * @dataProvider scoreProvider
     */
    public function testGetMinimumAmlScore($data, $score, $assert_score)
    {
        $actual_score = $this->risk_profile_rating_repository->getMinimumAmlScore($data, $score);
        $this->assertEquals($assert_score, $actual_score);
    }

    public function scoreProvider(): array
    {
        $acceptable_data = collect([]);
        $unacceptable_data = collect([]);

        $categories = ['deposited_last_12_months', 'ngr_last_12_months', 'wagered_last_12_months'];
        foreach ($categories as $name){
            $acceptable_data->push(new RiskProfileRating(['name'=>$name, 'section' => 'AML', 'score' => 100]));
            $unacceptable_data->push(new RiskProfileRating(['name'=>$name, 'section' => 'AML', 'score' => random_int(10, 90)]));
        }

        return [
            'acceptable' => [
                'data' => $acceptable_data,
                'score' => 1,
                'assert' => 80, // default config value `aml-minimum-grs`
            ],
            'unacceptable' => [
                'data' => $unacceptable_data,
                'score' => 90,
                'assert' => 90,
            ],
        ];
    }
    /**
     * @dataProvider dataProvider
     */
    public function testGetScore($section, \Closure $setVariation)
    {
        $data = $setVariation(self::$country_jurisdiction_map, $section, self::$user->id);
        foreach ($data as $parameter) {
            $jurisdiction = $parameter['jurisdiction'];
            // make sure the depositVsWager accepts right rpr that is relevant to jurisdiction
            $this->risk_profile_rating_repository->shouldReceive('depositVsWager')
                ->once()
                ->withArgs(function ($rpr, $user) use ($jurisdiction) {
                    static::assertInstanceOf(RiskProfileRating::class, $rpr);
                    static::assertEquals($jurisdiction, $rpr->jurisdiction);
                    static::assertInstanceOf(User::class, $user);
                    return true;
                })
                ->andReturnUsing(function ($rpr, $user) {
                    $rpr->found_children = collect([]);
                    return $rpr;
                });

            $score = $this->risk_profile_rating_repository->getScore(
                $section,
                $parameter['user_id'],
                $jurisdiction,
                $parameter['single_rpr'],
                $parameter['only_score'],
                $parameter['log_score'],
                $parameter['with_details'],
            );
            $expected_settings = array_keys(self::$rating_settings[$section]);
            array_push($expected_settings, "global");
            array_push($expected_settings, "tag");
            // make sure the structure of returned result is correct
            $this->assertEqualsCanonicalizing($expected_settings, array_keys($score));
        }
    }

    /**
     * @return array[]
     */
    public function dataProvider(): array
    {
        $closure = function ($country_jurisdiction_map, $section, $user_id) {
            $data = [];
            foreach ($country_jurisdiction_map as $jurisdiction) {
                $data[$jurisdiction] = [
                    'section' => $section,
                    'user_id' => $user_id,
                    'jurisdiction' => $jurisdiction,
                    'single_rpr' => null,
                    'only_score' => true,
                    'log_score' => false,
                    'with_details' => true,
                ];
            }
            return $data;
        };

        return [
            'AML' => ['AML', $closure],
            'RG' => ['RG', $closure],
        ];
    }
}
