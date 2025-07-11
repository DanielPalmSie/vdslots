<?php
/**
 * Created by PhpStorm.
 * User: iondum
 * Date: 09/11/18
 * Time: 11:17
 */

namespace App\Repositories;

loadPhive();

use App\Classes\Distributed;
use App\Classes\PaymentsHelper;
use App\Extensions\Database\FManager as DB;
use App\Factory\RiskProfileRating\GrsTrigger;
use App\Factory\RiskProfileRating\ProfileGrsTrigger;
use App\Helpers\DataFormatHelper;
use App\Helpers\SportsbookHelper;
use App\Helpers\GrsHelper;
use App\Models\Action;
use App\Models\Config;
use App\Models\RiskProfileRating;
use App\Models\TriggersLog;
use App\Models\User;
use App\Models\RiskProfileRatingLog;
use App\Traits\GlobalRatingScoreTrait;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use RuntimeException;
use Silex\Application;
use Videoslots\MtsSdkPhp\Endpoints\Arf\GetFailedDeposits;
use Videoslots\MtsSdkPhp\MtsClient;
use Videoslots\MtsSdkPhp\MtsClientFactory;

class RiskProfileRatingRepository
{
    use GlobalRatingScoreTrait;

    const PROFILE_RATING_MAX_SCORE = 100;
    const DEFAULT_PROFILE_RATING_MEDIUM_SCORE = 79;

    const PROFILE_RATING_MIN_TAG = 'Social Gambler';
    const PROFILE_RATING_MAX_TAG = 'High Risk';

    const SOCIAL_GAMBLER_RISK_TAG = 'Social Gambler';
    const LOW_RISK_TAG = 'Low Risk';
    const MEDIUM_RISK_TAG = 'Medium Risk';
    const HIGH_RISK_TAG = 'High Risk';

    const rating_settings = [
        'RG' => [
            'cancelled_withdrawals_last_x_days' => 'canceledWithdrawals',
            'failed_deposits_last_x_days' => 'failedDeposits',
            'have_deposit_and_loss_limits' => 'haveDepositAndLossLimits',
            'self_locked_excluded' => 'selfLockedExcluded',
            'avg_dep_amount_x_days' => 'avgDepositAmount',
            'avg_dep_count_x_days' => 'avgDepositCount',
            'avg_time_per_session_x_days' => 'avgTimePerSession',
            'avg_sessions_count_x_days' => 'avgCountPerSession',
//            'ngr_loss' => 'ngrLoss', // removed, can recover function from mercurial history
//            'countries' => 'amlCountry',
            'deposit_vs_wager' => 'depositVsWager',
            'ngr_last_6_months' => 'ngrLastXMonths',
            'ngr_last_12_months' => 'ngrLastXMonths',
//            'deposited_last_12_months' => 'depositedAmountLastXMonths',
//            'wagered_last_12_months' => 'wageredLastXMonths',
            'age' => 'age',
            'gameplay_time_interval' => 'gameplayTimeInterval',
            'intensive_gambler' => 'intensiveGambler',
            'interaction_profile_risk_factor' => 'interactionProfileRiskFactor',
            'popups_interaction' => 'popupsInteraction'
        ],
        'AML' => [
            'deposit_method' => 'depositMethod',
            'game_type' => 'gameType',
            'countries' => 'amlCountry',
            'deposit_vs_wager' => 'depositVsWager',
            'deposited_last_12_months' => 'depositedAmountLastXMonths',
            'ngr_last_12_months' => 'ngrLastXMonths',
            'wagered_last_12_months' => 'wageredLastXMonths',
            'wagered_last_12_months_sportsbook' => 'wageredLastXMonthsSportsbook',
            'age' => 'age',
            'pep' => 'pep',
            'sanction_list' => 'sanctionList',
            'criminal_records' => 'criminalRecord',
            'nationalities' => 'nationalities',
            'occupations' => 'occupations',
        ]
    ];
    protected $app = null;

    private bool $cache_enabled = false;
    /**
     * @var string|null
     */
    private ?string $jurisdiction = null;
    private MtsClient $client;

    public function __construct(Application $app, float $timeout = 2.0)
    {
        $this->app = $app;
        $this->client = MtsClientFactory::create(
            $app['mts.config']['base.uri'],
            getenv('MTS_API_KEY'),
            'admin2',
            phive('Logger')->channel('payments')
        );
    }

    public static function instance(Application $app)
    {
        return new self($app);
    }

    public function enableCache($bool = false)
    {
        $this->cache_enabled = $bool;

        return $this;
    }

    /**
     * Safely retrieve the score of any element in the list
     *
     * @param array  $data
     * @param string $key
     *
     * @return int|mixed
     */
    public function getCalculatedScore($data, $key)
    {
        if (empty($data)) {
            return 0;
        }
        if (empty($data[$key])) {
            return 0;
        }
        if (empty($data[$key]['score'])) {
            return 0;
        }
        return $data[$key]['score'];
    }

    /**
     * If calculated score is less than the configured minimum, return the minimum score
     *
     * @param Collection $data
     * @param int        $score
     *
     * @return mixed
     */
    public function getMinimumAmlScore($data, $score)
    {
        try {
            $config_min_score = (int)Config::getValue('aml-minimum-grs', 'grs', 80, true);
        } catch (Exception $e) {
            $this->app['monolog']->addError("[BO-GRS] {$e->getMessage()}");
            $config_min_score = 80;
        }

        $data = $data->where('section', '=', 'AML')->keyBy('name')->toArray();

        if ($score < $config_min_score
            && $this->getCalculatedScore($data, 'deposited_last_12_months') === self::PROFILE_RATING_MAX_SCORE
            && $this->getCalculatedScore($data, 'ngr_last_12_months') === self::PROFILE_RATING_MAX_SCORE
            && (
                $this->getCalculatedScore($data, 'wagered_last_12_months') === self::PROFILE_RATING_MAX_SCORE ||
                $this->getCalculatedScore($data, 'wagered_last_12_months_sportsbook') === self::PROFILE_RATING_MAX_SCORE
            )
        ) {
            return $config_min_score;
        }

        if ($score < $config_min_score
            && $this->getCalculatedScore($data, 'deposited_last_12_months') === self::PROFILE_RATING_MAX_SCORE
            && $this->getCalculatedScore($data, 'deposit_method') === self::PROFILE_RATING_MAX_SCORE
            && $this->getCalculatedScore($data, 'deposit_vs_wager') === self::PROFILE_RATING_MAX_SCORE
        ) {
            return $config_min_score;
        }

        return $score;
    }

    /**
     * Calculate the score based on percentage according to this formula:
     * score=SUM(el[0]['percent']/100*el[0]['score'], ..., el[length-1]['percent']/100*el[length-1]['score'])
     *
     * @param $items
     *
     * @return int
     */
    public function getScoreBasedOnPercentage($items): int
    {
        return (int)$items->reduce(function ($carry, $el) {
            return $carry + ($el['metadata']['percent'] / 100 * $el->score);
        }, 0);
    }

    /**
     * @param string $section      in [AML,RG]
     * @param        $user_id
     * @param        $jurisdiction
     * @param null   $single_rpr   #only used when we want a specific rpr element like for eg. deposit_vs_wager
     * @param bool   $only_score
     * @param bool   $log_score
     * @param bool   $with_details | $only_score will not be used here
     *
     * @return int|array
     */
    public function getScore(
        $section,
        $user_id,
        $jurisdiction,
        $single_rpr = null,
        $only_score = false,
        $log_score = false,
        $with_details = false
    ) {
        $this->jurisdiction = $jurisdiction;
        $user = User::sh($user_id)->find($user_id);
        $settings = self::rating_settings[$section];
        $rg_grs_frozen_setting = 'rg_grs_frozen_till';
        $is_rg_grs_frozen = $user->repo->hasSetting($rg_grs_frozen_setting);
        $latest_grs_query = RiskProfileRatingLog::query()
            ->where('user_id', $user_id)
            ->where('rating_type', $section)
            ->orderBy('created_at', 'DESC')
            ->limit(1);

        if (empty($single_rpr) && !empty($only_score) && !empty($this->cache_enabled)) {
            $grs = $latest_grs_query->first();

            if ($section === 'RG' && $is_rg_grs_frozen && !empty($grs) && $grs->rating_tag === static::HIGH_RISK_TAG) {
                return $grs->rating;
            }

            try {
                $profile_grs_trigger = $this->checkTriggers($user_id, $section, $jurisdiction);

                if ($profile_grs_trigger->exists()) {
                    $recalculated_score = $profile_grs_trigger->score();

                    if (
                        $is_rg_grs_frozen &&
                        !empty($grs) &&
                        $recalculated_score < $grs->score
                    ) {
                        return $grs->score;
                    }

                    return $recalculated_score;
                }
            } catch (RuntimeException $e) {
                $this->app['monolog']->addError(__METHOD__, [$e->getMessage()]);
            }

            $grs = $latest_grs_query->whereDate('created_at', Carbon::now()->toDateString())->first();

            if (!empty($grs)) {
                return $grs->rating;
            }
        }

        /**
         * calculate the score for each rating_setting
         */
        $data = RiskProfileRating::query()
            ->whereIn('name', array_keys($settings))
            ->where('category', '')
            ->where('section', $section)
            ->where('jurisdiction', $jurisdiction)
            ->get()
            ->filter(function ($rpr) use ($single_rpr) {
                if (!$single_rpr) {
                    // we want all rpr elements
                    return true;
                } else {
                    // we want only the requested element
                    return $rpr->name == $single_rpr;
                }
            })
            ->map(function ($rpr) use ($settings, $user, $section) {
                /** @var RiskProfileRating $rpr */
                $rpr = $this->{$settings[$rpr['name']]}($rpr, $user);

                if ($section === 'AML') {
                    if ($rpr['name'] === 'deposit_method') {
                        $rpr->score = (int)$rpr->found_children->reduce(function ($carry, $el) {
                            list (, $deposits) = $el['metadata'];

                            return $carry + ($deposits['percent'] / 100 * $el->score);
                        }, 0);

                        if ($rpr->score % 10 !== 0) {
                            $rpr->score = round($rpr->score / 10) * 10;
                        }

                        return $rpr;
                    }

                    if ($rpr['name'] === 'game_type') {
                        $rpr->score = $this->getScoreBasedOnPercentage($rpr->found_children);
                        return $rpr;
                    }
                } elseif ($section === 'RG') {
                    if ($rpr['name'] === 'gameplay_time_interval') {
                        $rpr->score = $this->getScoreBasedOnPercentage($rpr->found_children);
                        return $rpr;
                    }
                }

                $rpr->score = (int)($rpr->found_children->sum('score') / $rpr->found_children->count());
                return $rpr;
            });

        $score_data = $data->filter(function ($rpr) {
            return $rpr->score > 0;
        });

        // if score_data has no elements, the sum also will be 0 so we avoid division by 0
        if ($score_data->count() === 0) {
            $score = 0;
        } else {
            if ($section === 'RG') {
                $score = (int)$score_data->sum('score');
                if ($score > self::PROFILE_RATING_MAX_SCORE) {
                    $score = self::PROFILE_RATING_MAX_SCORE;
                }

                $customer_deposit_config = Config::getValue(
                    'customer-deposit-last-30-days',
                    'RG',
                    [],
                    false,
                    true,
                    true
                );
                foreach ($customer_deposit_config as $deposit_limit => $jurisdictions) {
                    $jurisdictions = explode(',', $jurisdictions);
                    if (in_array($jurisdiction, $jurisdictions)) {
                        $user_daily_stats_repository = new UserDailyStatsRepository();
                        $total_deposit = $user_daily_stats_repository->getTotalDepositLastXDays($this->app, $user);
                        if ($total_deposit < $deposit_limit) {
                            $score = $this->customerCannotTriggerMediumHighRisk($score, $jurisdiction, $section);
                        }
                        break;
                    }
                }
            } else {
                $score = (int)round($score_data->sum('score') / $score_data->count());
            }
        }

        $new_rating_tag = static::getGRSRatingTag($this->app, $score, $jurisdiction, $section);
        $grs = $latest_grs_query->first();

        if ($section === 'RG' && $is_rg_grs_frozen && !empty($grs) && $score < $grs->rating) {
            $score = (int)$grs->rating;
        }

        if (
            $section === 'AML' ||
            (
                !$is_rg_grs_frozen ||
                (!empty($grs) && $grs->rating_tag !== static::HIGH_RISK_TAG)
            )
        ) {
            try {
                $profile_grs_trigger = $this->checkTriggers($user_id, $section, $jurisdiction);
            } catch (RuntimeException $e) {
                $this->app['monolog']->addError(__METHOD__, [$e->getMessage()]);
            }
        }

        if (
            (!is_null($profile_grs_trigger) &&
            $profile_grs_trigger->exists() &&
            $profile_grs_trigger->score() > $score)
        ) {
            $score = $profile_grs_trigger->score();
            $new_rating_tag = $profile_grs_trigger->riskGroup();
        }

        if ($section === 'AML') {
            $score = $this->getMinimumAmlScore($score_data, $score);
        }

        $score_data = $data->reduce(function ($carry, $el) {
            $carry[$el['name']] = $el['score'];
            return $carry;
        }, []);

        if (
            $log_score &&
            empty($single_rpr) &&
            (
                $section === 'AML' ||
                (
                    !$is_rg_grs_frozen ||
                    ((!empty($grs) && $grs->rating_tag === static::MEDIUM_RISK_TAG) && $new_rating_tag === static::HIGH_RISK_TAG)
                )
            )
        ) {
            if (!is_null($profile_grs_trigger) && $profile_grs_trigger->exists()) {
                $influenced_by = json_encode([
                    $profile_grs_trigger->triggerName() ." triggered in the last " .
                    $profile_grs_trigger->within() . " " .
                    $profile_grs_trigger->period() . ", setting GRS to " .
                    $profile_grs_trigger->riskGroup() => $score
                ]);
            } else {
                $influenced_by = json_encode($score_data);
            }
            $rpr_log = RiskProfileRatingLog::logRating(
                $this->app,
                $user_id,
                $jurisdiction,
                $section,
                $score,
                $influenced_by
            );

            if (
                $section === 'RG' &&
                !$is_rg_grs_frozen &&
                in_array($new_rating_tag, [static::MEDIUM_RISK_TAG, static::HIGH_RISK_TAG])
            ) {
                $rg_grs_risk_days = (int)Config::getValue(
                    'rg-grs-risk-days',
                    'RG',
                    [],
                    7
                );
                ActionRepository::logAction(
                    $user->id,
                    "RG GRS reached $new_rating_tag via the categories calculation.
                This risk will stay for $rg_grs_risk_days days.",
                    "rg-monitoring",
                    false,
                    null,
                    true

                );
                $user->repo->setSetting(
                    $rg_grs_frozen_setting,
                    Carbon::now()->addDays($rg_grs_risk_days)->toDateTimeString(),
                    false
                );
            }
        }

        if ($with_details) {
            $score_data['global'] = $score;

            if (!empty($rpr_log)) {
                $score_data['tag'] = $rpr_log->rating_tag;
            } else {
                $score_data['tag'] = static::getGRSRatingTag($this->app, $score, $jurisdiction, $section);
            }

            return $score_data;
        }

        return $only_score ? $score : [$score, $data];
    }

    /**
     * @param int    $user_id
     * @param string $section
     * @param string $jurisdiction
     *
     * @return GrsTrigger
     */
    private function checkTriggers(int $user_id, string $section, string $jurisdiction = 'ALL'): GrsTrigger
    {
        $rprl = RiskProfileRating::query()
            ->where('name', static::MEDIUM_RISK_TAG)
            ->where('category', RiskProfileRating::RATING_SCORE_PARENT_CATEGORY)
            ->where('section', $section)
            ->where('jurisdiction', $jurisdiction)
            ->first();
        $risk_groups = [
            'high_risk' => [
                'triggers' => GrsHelper::getHighRiskTriggers($this->app, $section, $jurisdiction),
                'score' => self::PROFILE_RATING_MAX_SCORE,
                'title' => static::HIGH_RISK_TAG,
            ],
            'medium_risk' => [
                'triggers' => GrsHelper::getMediumRiskTriggers($this->app, $section, $jurisdiction),
                'score' => $rprl->score ?? static::DEFAULT_PROFILE_RATING_MEDIUM_SCORE,
                'title' => static::MEDIUM_RISK_TAG,
            ]
        ];

        foreach ($risk_groups as $risk_group) {
            foreach ($risk_group['triggers'] as $trigger) {
                GrsHelper::validatePeriod($trigger['period']);
                $sub_period = "sub" . ucfirst($trigger['period']);
                $occurrence = $trigger['occurrence'] ?? 1;
                $before_past = $trigger['before_past'] ?? 0;
                // Check if flag triggered in specified 'past' period
                $past_trigger = TriggersLog::sh($user_id)
                    ->where('user_id', $user_id)
                    ->where('trigger_name', $trigger['trigger_name'])
                    ->where('created_at', '>=', Carbon::now()->$sub_period($trigger['past']));

                if ($past_trigger->exists()) {
                    if ($before_past === 0 && ($occurrence === 1 || $past_trigger->count() >= $occurrence)) {
                        return new ProfileGrsTrigger(
                            $risk_group['score'],
                            $trigger['trigger_name'],
                            $risk_group['title'],
                            (int)$trigger['past'],
                            $trigger['period']
                        );
                    }
                    $past_trigger_at = $past_trigger->get()[0]['created_at'];
                    $result = TriggersLog::sh($user_id)
                        ->where('user_id', $user_id)
                        ->where('trigger_name', $trigger['trigger_name'])
                        ->where('created_at', '>', Carbon::parse($past_trigger_at)->$sub_period($before_past))
                        ->where('created_at', '<=', Carbon::parse($past_trigger_at));

                    if ($result->count() >= $occurrence) {
                        return new ProfileGrsTrigger(
                            $risk_group['score'],
                            $trigger['trigger_name'],
                            $risk_group['title'],
                            (int)$trigger['past'],
                            $trigger['period']
                        );
                    }
                }
            }
        }
        return new ProfileGrsTrigger(0);
    }

    /**
     * @param string $section in ['RG', 'AML']
     * @param        $user_id
     *
     * @return mixed
     * @throws GuzzleException
     */
    public function getView($section, $user_id)
    {
        $remote = [];
        $data = [];
        $rating_score = [];
        $app = $this->app;
        $user = User::find($user_id);
        $has_completed_registration = $user->hasCompletedRegistration();
        $jurisdiction = (new UserRepository($user))->getJurisdiction();

        if ($has_completed_registration) {
            list($score, $data) = $this->getScore(
                $section,
                $user_id,
                $jurisdiction,
                null,
                false,
                true
            );
            $rating_score = $this->prepareUserRatingScore(
                $score,
                RiskProfileRating::RATING_SCORE_PARENT_CATEGORY,
                $section,
                $jurisdiction
            );
            $remote = Distributed::getRemoteScore($this->app, $user, $section, $jurisdiction);
        }

        return $this->app['blade']->view()->make(
            'admin.user.risk-profile-rating.index',
            compact('app', 'section', 'rating_score', 'data', 'remote', 'has_completed_registration', 'user',
                'jurisdiction')
        )->render();
    }

    /**
     * @param $child
     * @param $result
     *
     * @return bool
     */
    private function helperFindInterval($child, $result)
    {
        list($min, $max) = explode(',', $child->name);

        $result = round($result);

        return empty($max)
            ? ($result >= $min)
            : ($result >= $min && $result <= $max);
    }

    /**
     * AML
     *
     * @param RiskProfileRating $rpr
     * @param User              $user
     *
     * @return RiskProfileRating
     */
    protected function depositMethod($rpr, $user)
    {
        $deposits = "SELECT sum(amount) as sum, count(*) as count, 'replace_with_key' as method FROM deposits WHERE user_id={$user->id} AND status='approved' ";
        $deposits = PaymentsHelper::toUnionQuery($deposits, "deposits");
        $deposits = collect(DB::shSelect($user->id, 'deposits', $deposits))
            ->map(function ($el) {
                return (array)$el;
            })
            ->filter(function ($el) {
                return !is_null($el['sum']);
            });

        /** @var Collection $dep_types */
        $dep_types = $deposits->pluck('method');
        $arePaymentMethodsSupported = PaymentsHelper::arePaymentMethodsSupported('pending_withdrawals', $dep_types->toArray());

        if ($dep_types->isNotEmpty() && $arePaymentMethodsSupported) {
            $withdrawals = "SELECT sum(amount) as sum, count(*) as count, 'replace_with_key' as method FROM pending_withdrawals WHERE user_id = {$user->id} AND status = 'approved' ";
            $withdrawals = PaymentsHelper::toUnionQuery($withdrawals, "pending_withdrawals", $dep_types->toArray());
            $withdrawals = collect(DB::shSelect($user->id, 'pending_withdrawals', $withdrawals))
                ->map(function ($el) {
                    return (array)$el;
                });
        } else {
            $withdrawals = collect();
            $rpr->message = "No deposits found for this user.";
        }

        $deposits_sum = $deposits->sum('sum');
        $deposits = $deposits->map(function ($el) use ($deposits_sum) {
            $el['percent'] = round($el['sum'] * 100 / $deposits_sum, 2);
            return $el;
        })->keyBy('method');

        $withdrawals = $withdrawals->keyBy('method');

        $rpr->found_children = $rpr->children
            ->filter(function ($el) use ($dep_types) {
                return $dep_types->contains($el->name);
            })
            ->map(function ($el) use ($withdrawals, $deposits, $user) {
                $w = $withdrawals[$el->name];
                $d = $deposits[$el->name];
                $w['sum'] = DataFormatHelper::convertToEuro($user->currency, $w['sum'], $this->app);
                $d['sum'] = DataFormatHelper::convertToEuro($user->currency, $d['sum'], $this->app);
                $el->metadata = [$w, $d];
                return $el;
            });

        $rpr->view = "deposit-method-details";

        return $rpr;
    }

    /**
     * AML
     *
     * @param RiskProfileRating $rpr
     * @param User              $user
     *
     * @return RiskProfileRating
     */
    protected function amlCountry($rpr, $user)
    {
        $rpr->found_children = $rpr->children
            ->filter(function ($child) use ($user) {
                return strtolower($child->name) == strtolower($user->country);
            })
            ->map(function ($el) use ($user) {
                $el->result = $user->country;
                return $el;
            });
        return $rpr;
    }

    /**
     * AML/RG
     *
     * @param RiskProfileRating $rpr
     * @param User              $user
     *
     * @return RiskProfileRating
     */
    protected function nationalities($rpr, $user)
    {
        $nationality = $user->getSetting('nationality');
        $rpr->found_children = $rpr->children
            ->filter(function ($child) use ($nationality) {
                return strtolower($child->name) == strtolower($nationality);
            })
            ->map(function ($el) use ($nationality) {
                $el->result = $nationality;
                return $el;
            });
        return $rpr;
    }

    /**
     * AML
     *
     * @param RiskProfileRating $rpr
     * @param User              $user
     *
     * @return RiskProfileRating
     * @throws Exception
     */
    protected function occupations($rpr, $user)
    {
        $occupation = $user->getSetting('occupation');
        $high_risk_occupations_group = "high-risk-occupation-list";
        $high_risk_occupations = Config::getValue(
            $this->jurisdiction . "-" . $high_risk_occupations_group,
            'AML',
            [],
            false,
            true,
            true
        );

        $rpr->found_children = $rpr->children
            ->filter(function ($child) use ($high_risk_occupations_group) {
                return $child->name === $high_risk_occupations_group;
            })
            ->map(function ($el) use ($occupation, $high_risk_occupations) {
                $el->result = $occupation;
                if (!in_array($occupation, $high_risk_occupations, true)) {
                    $el->score = 0;
                }
                return $el;
            });
        return $rpr;
    }

    /**
     * AML
     *
     * @param RiskProfileRating $rpr
     * @param User              $user
     *
     * @return RiskProfileRating
     */
    protected function gameType($rpr, $user)
    {

        $sessions = collect(DB::shSelect($user->id, 'users_game_sessions', "
            SELECT
                IFNULL(mg.tag, 'videoslots') AS tag,
                SUM(ugs.bet_amount) AS total_bets,
                SUM(ugs.win_amount) AS total_wins
            FROM users_game_sessions AS ugs
                LEFT JOIN micro_games AS mg ON ugs.game_ref = mg.ext_game_name AND ugs.device_type_num = mg.device_type_num
            WHERE user_id = {$user->id}
            GROUP BY IFNULL(mg.tag, 'videoslots');
        "))->map(function ($el) {
            $el->tag = $el->tag ?? 'other';
            return (array)$el;
        });
        // Checking if Sportsbook is enabled for current User, and add it to the menu
        $hasSportsbook = SportsbookHelper::hasSportsbookEnabled($user);
        if ($hasSportsbook) {
            $total_bets_sportsbook = DB::shTable($user->id, 'sport_transactions')
                ->where('user_id', '=', $user->id)
                ->where('bet_type', '=', 'bet')
                ->sum('amount');
            $total_wins_sportsbook = DB::shTable($user->id, 'sport_transactions')
                ->where('user_id', '=', $user->id)
                ->where('bet_type', '!=', 'bet')
                ->sum('amount');
            $sessions->push(
                [
                    "tag" => "sportsbook",
                    "total_bets" => $total_bets_sportsbook,
                    "total_wins" => $total_wins_sportsbook
                ]
            );
        }

        $sessions_sum = $sessions->sum('total_bets');
        $sessions = $sessions
            ->map(function ($el) use ($sessions_sum) {
                $el['percent'] = round($el['total_bets'] * 100 / $sessions_sum, 2);
                return $el;
            })
            ->filter(function ($el) {
                return $el['percent'] > 0;
            })
            ->map(function ($el) use ($user) {
                $el['total_bets'] = DataFormatHelper::convertToEuro($user->currency, $el['total_bets'], $this->app);
                $el['total_wins'] = DataFormatHelper::convertToEuro($user->currency, $el['total_wins'], $this->app);
                return $el;
            })
            ->keyBy('tag');

        $rpr->found_children = $rpr->children->filter(function ($el) use ($sessions) {
            return $sessions->contains('tag', '=', $el->name);
        })->map(function ($el) use ($sessions) {
            $el->metadata = $sessions[$el->name];
            return $el;
        });

        $rpr->message = "This user never played.";

        $rpr->view = "game-type-details";

        return $rpr;
    }

    /**
     * RG
     * Gameplay time interval based on user local timezone
     *
     * @param RiskProfileRating $rpr
     * @param User              $user
     *
     * @return RiskProfileRating
     */
    protected function gameplayTimeInterval($rpr, $user)
    {
        $items_count = 0;
        $timezone = phive('DBUserHandler')->getUserLocalTimezone(cu($user->id));
        $intervals = $rpr->children->map(function ($item) {
            list ($start, $end) = explode(',', $item->name);

            $item->start = Carbon::createFromFormat('H:i:s', $start, 'UTC');
            $item->end = Carbon::createFromFormat('H:i:s', $end, 'UTC');

            // fix for interval 23:00 - 7:00
            if ($item->start->gt($item->end)) {
                $item->end = $item->end->addDay(1);
            }
            $item->count = 0;
            return $item;
        })->toArray();

        $sessions = DB::shSelect($user->id, 'users_game_sessions', "
            SELECT HOUR(start_time) AS hour, count(*) AS count
            FROM users_game_sessions AS ugs
            WHERE user_id = {$user->id}
                AND (bet_amount > 0 OR bet_cnt > 0)
            GROUP BY HOUR(start_time)
        ");

        foreach ($sessions as $session) {
            $items_count += $session->count;

            $time = Carbon::createFromFormat('H', $session->hour, 'UTC')->setTimezone($timezone);

            foreach ($intervals as $key => &$interval) {
                if (!$time->between($interval['start'], $interval['end'])) {
                    continue;
                }
                $interval['count'] += $session->count;
            }
        }

        $intervals = collect($intervals)
            ->map(function ($item) use ($items_count) {
                $item['percent'] = round($item['count'] * 100 / $items_count, 2);
                return $item;
            })
            ->filter(function ($item) {
                return $item['percent'] > 0;
            })
            ->keyBy('name');

        $rpr->found_children = $rpr->children->filter(function ($el) use ($intervals) {
            return !empty($intervals[$el->name]);
        })->map(function ($el) use ($intervals) {
            $el->metadata = $intervals[$el->name];
            return $el;
        });

        $rpr->message = "This user never played.";

        $rpr->view = "gameplay-time-interval";

        return $rpr;
    }

    /**
     * AML
     *
     * @param RiskProfileRating $rpr
     * @param User              $user
     *
     * @return RiskProfileRating
     */
    protected function depositVsWager($rpr, $user)
    {
        $data = collect(DB::shSelect($user->id, 'users_daily_stats', "
            SELECT
                sum(uds.ndeposits) as deposits_count,
                sum(uds.deposits) as deposits_sum,
                sum(uds.nwithdrawals) as withdrawals_count,
                sum(uds.withdrawals) as withdrawals_sum,
                sum(ugs.bet_cnt) as bets_count,
                sum(uds.bets) as bets_sum,
                sum(ugs.win_cnt) as wins_count,
                sum(uds.wins) as wins_sum
            FROM users_daily_stats AS uds
            LEFT JOIN (
                select sum(win_cnt) as win_cnt, sum(bet_cnt) as bet_cnt, user_id FROM users_game_sessions
                WHERE user_id = {$user->id}
            ) AS ugs ON uds.user_id = ugs.user_id
            WHERE uds.user_id = {$user->id}
            GROUP BY uds.user_id
        "))->map(function ($el) {
            return (array)$el;
        })->first();

        $data['bets_sum'] = $data['bets_sum'] ?? 0;
        $data['deposits_sum'] = $data['deposits_sum'] ?? 0;

        $data['withdrawals_sum'] = DataFormatHelper::convertToEuro($user->currency, $data['withdrawals_sum'], $this->app);
        $data['deposits_sum'] = DataFormatHelper::convertToEuro($user->currency, $data['deposits_sum'], $this->app);
        $data['bets_sum'] = DataFormatHelper::convertToEuro($user->currency, $data['bets_sum'], $this->app);
        $data['wins_sum'] = DataFormatHelper::convertToEuro($user->currency, $data['wins_sum'], $this->app);

        $times = (int)($data['bets_sum'] / $data['deposits_sum']);

        $rpr->found_children = $rpr->children
            ->filter(function ($el) use ($times) {
                return $this->helperFindInterval($el, $times);
            })
            ->map(function ($el) use ($data, $user) {
                $el->metadata = $data;
                return $el;
            });

        if ($times == 0 || $rpr->found_children->count() == 0) {
            $data['bets_sum'] = DataFormatHelper::nf($data['bets_sum'], 1);
            $data['deposits_sum'] = DataFormatHelper::nf($data['deposits_sum'], 1);
            $rpr->message = "User deposited {$data['deposits_sum']}EUR and wagered {$data['bets_sum']}EUR. No configuration found for {$times}X.";
        }

        $rpr->view = 'deposit-vs-wager-details';

        return $rpr;
    }

    /**
     * AML/RG
     *
     * @param RiskProfileRating $rpr
     * @param User              $user
     *
     * @return RiskProfileRating
     */
    protected function age($rpr, $user)
    {
        $age = (new Carbon($user->dob))->age;

        $rpr->found_children = $rpr->children
            ->filter(function ($el) use ($age) {
                return $this->helperFindInterval($el, $age);
            })
            ->map(function ($el) use ($age, $user) {
                $el->result = $age;
                return $el;
            });

        if ($rpr->found_children->count() == 0) {
            $rpr->message = "User {$user->id} dob is not configured.";
        }

        return $rpr;
    }

    /**
     * AML
     *
     * @param RiskProfileRating $rpr
     * @param User              $user
     *
     * @return RiskProfileRating
     */
    protected function depositedAmountLastXMonths($rpr, $user)
    {
        $total = DB::shSelect($user->id, 'users_daily_stats', "
            SELECT sum(deposits) AS total FROM users_daily_stats
            WHERE user_id = :user_id
            AND date between :start_date and :end_date
        ", [
            'user_id' => $user->id,
            'start_date' => Carbon::now()->subMonth($rpr->data['replacers']['_MONTHS'])->toDateString(),
            'end_date' => Carbon::now()->toDateString()
        ])[0]->total ?? 0;

        $total = DataFormatHelper::convertToEuro($user->currency, $total, $this->app);

        $rpr->found_children = $rpr->children
            ->filter(function ($child) use ($total) {
                return $this->helperFindInterval($child, $total);
            })
            ->map(function ($el) use ($total) {
                $el->result = $total;
                return $el;
            });

        $rpr->view = 'default-money';

        return $rpr;
    }

    /**
     * AML
     *
     * @param RiskProfileRating $rpr
     * @param User              $user
     *
     * @return RiskProfileRating
     */
    protected function ngrLastXMonths($rpr, $user)
    {
        if ($rpr->data['replacers']['_MONTHS'] == 1) {
            $total = (new UserDailyStatsRepository(true))->getNgrByDays($user, 31);
        } else {
            $total = (new UserDailyStatsRepository(true))->getNgrByMonth($user, $rpr->data['replacers']['_MONTHS']);
        }

        $total = array_sum(array_values($total));
        $total = DataFormatHelper::convertToEuro($user->currency, $total, $this->app);
        $rpr->found_children = $rpr->children
            ->filter(function ($child) use ($total) {
                return $this->helperFindInterval($child, $total);
            })
            ->map(function ($el) use ($total) {
                $el->result = $total;
                return $el;
            });

        $total = DataFormatHelper::nf($total, 1);

        if ($rpr->found_children->count() == 0) {
            $rpr->message = "No configuration for ngr value of $total EUR";
        }

        $rpr->view = 'default-money';

        return $rpr;
    }

    /**
     * AML
     *
     * @param RiskProfileRating $rpr
     * @param User              $user
     *
     * @return RiskProfileRating
     */
    protected function wageredLastXMonths($rpr, $user)
    {
        $total = (new UserDailyStatsRepository(true))->getBetsByMonth($user, $rpr->data['replacers']['_MONTHS']);

        $total = array_sum(array_values($total));

        $total = DataFormatHelper::convertToEuro($user->currency, $total, $this->app);

        $rpr->found_children = $rpr->children
            ->filter(function ($child) use ($total) {
                return $this->helperFindInterval($child, $total);
            })
            ->map(function ($el) use ($total) {
                $el->result = $total;
                return $el;
            });

        $rpr->view = 'default-money';

        return $rpr;
    }

    /**
     * AML
     *
     * @param RiskProfileRating $rpr
     * @param User              $user
     *
     * @return RiskProfileRating
     */
    protected function wageredLastXMonthsSportsbook($rpr, $user)
    {
        $start_date = Carbon::now()->subMonths($rpr->data['replacers']['_MONTHS']);
        $end_date = Carbon::now();
        $total = (int)(new UserRepository($user))->getSportsWagerData($start_date, $end_date, false);
        $total = DataFormatHelper::convertToEuro($user->currency, $total, $this->app);

        $rpr->found_children = $rpr->children
            ->filter(function ($child) use ($total) {
                return $this->helperFindInterval($child, $total);
            })
            ->map(function ($el) use ($total) {
                $el->result = $total;
                return $el;
            });

        $rpr->view = 'default-money';

        return $rpr;
    }

    /**
     * AML - Check is the PEP user
     *
     * @param RiskProfileRating $rpr
     * @param User              $user
     *
     * @return RiskProfileRating
     */
    protected function pep($rpr, $user)
    {
        $children = collect();
        $is_pep = $user->settings()
            ->where('setting', 'pep_failure')
            ->where('value', 1)
            ->exists();

        if ($is_pep) {
            $setting = $rpr->children->first();
            $setting->result = '-';
            $children->push($setting);
        } else {
            $rpr->message = "The PEP has not been triggered yet";
        }

        $rpr->found_children = $children;

        return $rpr;
    }

    /**
     * AML - Check is user under Sanction List
     * Both checks from the Acuris and GBG must be failed
     *
     * sanction_list_failure
     * - 1 - check failed
     * id3global_res
     * - -1 ALERT - Issues were identified that may indicate potential fraud or errors in the identity data provided.
     * - 0 REFER - The identity verification process was inconclusive.
     * - 2 ERROR - Incorrect or incomplete data was submitted. A system failure or API issue occurred. A misconfiguration in the request parameters.
     * - 3 NO MATCH - The submitted identity information could not be matched to any existing record in the databases checked.
     *
     * @param $rpr
     * @param $user
     *
     * @return RiskProfileRating
     */
    protected function sanctionList($rpr, $user)
    {
        $failure_checks = $user->settings()
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('setting', 'sanction_list_failure')
                        ->where('value', 1);
                })
                ->orWhere(function ($query) {
                    $query->where('setting', 'id3global_res')
                        ->whereIn('value', [-1, 0, 2, 3]);
                });

            })
            ->count();
        $children = collect();

        if ($failure_checks === 2) {
            $setting = $rpr->children->first();
            $setting->result = '-';
            $children->push($setting);
        } else {
            $rpr->message = "The Sanction List has not been triggered yet";
        }

        $rpr->found_children = $children;

        return $rpr;
    }

    /**
     * AML - Check if user has a criminal status
     *
     * @param $rpr
     * @param $user
     *
     * @return RiskProfileRating
     */
    protected function criminalRecord($rpr, $user)
    {
        $criminal_record = $user->settings()
            ->where('setting', 'criminal_record')
            ->first();
        $setting = $criminal_record ? $criminal_record->value : 'no_criminal_record';
        $rpr->view = 'criminal-record';
        $rpr->settings = $rpr->children;
        $rpr->found_children = $rpr->children->filter(function ($el) use ($setting) {
            return $el->data['slug'] == $setting;
        });

        return $rpr;
    }

    /**
     * RG
     *
     * @param RiskProfileRating $rpr
     * @param User              $user
     *
     * @return RiskProfileRating
     */
    protected function haveDepositAndLossLimits($rpr, $user)
    {
        $user->populateSettings();

        $rg_limits = $user->rgLimits()->groupBy('type')->get();
        $deposit_limit = $rg_limits->contains('type', '=', 'deposit');
        $loss_limit = $rg_limits->contains('type', '=', 'loss');

        $result = 'none';
        if ($deposit_limit && $loss_limit) {
            $result = 'both';
        } elseif ($deposit_limit) {
            $result = 'deposit';
        } elseif ($loss_limit) {
            $result = 'loss';
        }

        $rpr->found_children = $rpr->children
            ->filter(function ($el) use ($result) {
                return $el->name == $result;
            });

        $rpr->view = 'mini-default';

        return $rpr;
    }

    /**
     * RG
     *
     * @param RiskProfileRating $rpr
     * @param User              $user
     *
     * @return RiskProfileRating
     */
    protected function selfLockedExcluded($rpr, $user)
    {

        $excluded = $user->block_repo->isSelfExcluded();
        $locked = $user->block_repo->isSelfLocked();
        $external_self_excluded = $user->block_repo->isExternalSelfExcluded();

        if (!$excluded || !$locked || !$external_self_excluded) {
            $data = DB::shSelect($user->id, 'actions', "
                SELECT * FROM actions
                WHERE tag in ('profile-lock', 'profile-unlock', 'unexcluded-date', 'deleted_lock-hours')
                AND target = :target
                AND created_at BETWEEN :start_date AND :end_date
            ", [
                'target' => $user->id,
                'start_date' => $start_date = Carbon::now()->subDays($rpr->data['replacers']['_DAYS'])->toDateString(),
                'end_date' => Carbon::now()->addDay(1)->toDateString()
            ]);

            foreach ($data as $el) {
                if (str_contains($el->descr, "exclude") !== false) {
                    $excluded = true;
                } elseif (str_contains($el->descr, "locked") !== false || str_contains($el->descr,
                        "unlock") !== false || str_contains($el->descr, "lock-hours") !== false) {
                    $locked = true;
                }
            }
        }

        $result = 'none';
        if ($excluded && $locked) {
            $result = 'both';
        } elseif ($excluded) {
            $result = 'excluded';
        } elseif ($locked) {
            $result = 'locked';
        } elseif ($external_self_excluded){
            $result = 'externally-excluded';
        }

        $rpr->found_children = $rpr->children
            ->filter(function ($el) use ($result) {
                return $el->name == $result;
            });

        $rpr->view = 'mini-default';

        return $rpr;
    }

    /**
     * @param RiskProfileRating $rpr
     * @param Collection        $data
     * @param string            $column
     *
     * @return float|int|array
     */
    private function getIncreasedFromPreviousToLast($rpr, $data, $column)
    {
        $prev_days = $data;
        $last_days = $data->slice(0, $rpr->data['replacers']['_LAST_DAYS']);

        if ($last_days->count() === 0) {
            $last = $prev = 0;
        } else {
            $last = $last_days->sum($column) / $last_days->count();
            $prev = $prev_days->sum($column) / $prev_days->count();
        }

        $res = round(($last - $prev) / $prev * 100, 2);

        if (is_nan($res) || is_infinite($res)) {
            $res = 0;
        }

        $last = round($last, 2);
        $prev = round($prev, 2);

        return compact('last', 'prev', 'res');
    }

    /**
     * @param        $select_fields
     * @param        $limit
     * @param        $user_id
     * @param string $left_join_query
     *
     * @return Collection
     */
    private function getPerLoggedIn($select_fields, $limit, $user_id, $left_join_query = '')
    {
        $sql = "
            SELECT DATE(us.created_at) AS date, us.user_id, $select_fields FROM users_sessions AS us
            $left_join_query
            WHERE us.user_id = $user_id
            GROUP BY DATE(us.created_at), us.user_id
            ORDER BY DATE(us.created_at) DESC
            LIMIT $limit
        ";
        return collect(DB::shSelect($user_id, 'users_sessions', $sql));
    }

    /**
     * RG
     *
     * @param RiskProfileRating $rpr
     * @param User              $user
     *
     * @return RiskProfileRating
     */
    protected function avgDepositAmount($rpr, $user)
    {
        $select = "IFNULL(d.deposits, 0) as deposits";
        $left_join = "LEFT JOIN (
            SELECT SUM(amount) AS deposits, user_id, DATE(timestamp) AS date FROM deposits
            WHERE user_id = {$user->id}
            GROUP BY user_id, DATE(timestamp)
        ) AS d ON us.user_id = d.user_id AND d.date = DATE(us.created_at)";

        $data = $this->getPerLoggedIn($select, $rpr->data['replacers']['_PREVIOUS_DAYS'], $user->id, $left_join);
        $data = $this->getIncreasedFromPreviousToLast($rpr, $data, 'deposits');

        $data['last'] = DataFormatHelper::nf($data['last']) . ' EUR';
        $data['prev'] = DataFormatHelper::nf($data['prev']) . ' EUR';

        $rpr->found_children = $rpr->children
            ->filter(function ($child) use ($data) {
                // we don't have intervals for negative values. So we consider those being 0
                return $this->helperFindInterval($child, $data['res'] < 0 ? 0 : $data['res']);
            })
            ->map(function ($el) use ($data) {
                $el->result = $data;
                return $el;
            });

        $rpr->view = 'mini-default-average';

        return $rpr;
    }

    /**
     * RG
     *
     * @param RiskProfileRating $rpr
     * @param User              $user
     *
     * @return RiskProfileRating
     */
    protected function avgDepositCount($rpr, $user)
    {
        $select = "IFNULL(d.ndeposits, 0) as ndeposits";
        $left_join = "LEFT JOIN (
            SELECT COUNT(*) AS ndeposits, user_id, DATE(timestamp) AS date FROM deposits
            WHERE user_id = {$user->id}
            GROUP BY user_id, DATE(timestamp)
        ) AS d ON us.user_id = d.user_id AND d.date = DATE(us.created_at)";

        $data = $this->getPerLoggedIn($select, $rpr->data['replacers']['_PREVIOUS_DAYS'], $user->id, $left_join);
        $data = $this->getIncreasedFromPreviousToLast($rpr, $data, 'ndeposits');

        $rpr->found_children = $rpr->children
            ->filter(function ($child) use ($data) {
                // we don't have intervals for negative values. So we consider those being 0
                return $this->helperFindInterval($child, $data['res'] < 0 ? 0 : $data['res']);
            })
            ->map(function ($el) use ($data) {
                $el->result = $data;
                return $el;
            });

        $rpr->view = 'mini-default-average';

        return $rpr;
    }

    /**
     * RG
     *
     * @param RiskProfileRating $rpr
     * @param User              $user
     *
     * @return RiskProfileRating
     */
    protected function avgTimePerSession($rpr, $user)
    {
        $select = "IFNULL(SUM(TIMESTAMPDIFF(MINUTE, us.created_at, us.ended_at)), 0) AS minutes";

        $data = $this->getPerLoggedIn($select, $rpr->data['replacers']['_PREVIOUS_DAYS'], $user->id);
        $data = $this->getIncreasedFromPreviousToLast($rpr, $data, 'minutes');

        $rpr->found_children = $rpr->children
            ->filter(function ($child) use ($data) {
                // we don't have intervals for negative values. So we consider those being 0
                return $this->helperFindInterval($child, $data['res'] < 0 ? 0 : $data['res']);
            })
            ->map(function ($el) use ($data) {
                $el->result = $data;
                return $el;
            });

        $rpr->view = 'mini-default-average';

        return $rpr;
    }

    /**
     * RG
     *
     * @param RiskProfileRating $rpr
     * @param User              $user
     *
     * @return RiskProfileRating
     */
    protected function avgCountPerSession($rpr, $user)
    {
        $select = "COUNT(created_at) AS sessions_count";

        $data = $this->getPerLoggedIn($select, $rpr->data['replacers']['_PREVIOUS_DAYS'], $user->id);
        $data = $this->getIncreasedFromPreviousToLast($rpr, $data, 'sessions_count');

        $rpr->found_children = $rpr->children
            ->filter(function ($child) use ($data) {
                // we don't have intervals for negative values. So we consider those being 0
                return $this->helperFindInterval($child, $data['res'] < 0 ? 0 : $data['res']);
            })
            ->map(function ($el) use ($data) {
                $el->result = $data;
                return $el;
            });

        $rpr->view = 'mini-default-average';

        return $rpr;
    }

    /**
     * RG
     *
     * @param RiskProfileRating $rpr
     * @param User              $user
     *
     * @return RiskProfileRating
     */
    protected function canceledWithdrawals($rpr, $user)
    {
        $result = DB::shSelect($user->id, "pending_withdrawals", "
            SELECT count(*) AS count FROM pending_withdrawals
            WHERE status = 'disapproved'
            AND user_id = :user_id
            AND approved_at BETWEEN :start_date AND :end_date
        ", [
            'user_id' => $user->id,
            'start_date' => Carbon::now()->subDays($rpr->data['replacers']['_DAYS'])->toDateString(),
            'end_date' => Carbon::now()->toDateString()
        ])[0]->count;

        $rpr->found_children = $rpr->children
            ->filter(function ($child) use ($result) {
                return $this->helperFindInterval($child, $result);
            })
            ->map(function ($el) use ($result) {
                $el->result = $result;
                return $el;
            });
        return $rpr;
    }

    /**
     * RG
     *
     * @param RiskProfileRating $rpr
     * @param User              $user
     *
     * @return RiskProfileRating
     */
    protected function failedDeposits($rpr, $user)
    {
        try {
            $endpoint = (new GetFailedDeposits())
                ->forUser($user->id)
                ->withStartDate(Carbon::now()->subDays($rpr->data['replacers']['_DAYS'])->toDateString())
                ->withEndDate(Carbon::tomorrow()->toDateString());

            $result = $this->client->call($endpoint);
            $result = $result->count;
        } catch (Exception $e) {
            $result = 0;
        }

        $rpr->found_children = $rpr->children
            ->filter(function ($child) use ($result) {
                return $this->helperFindInterval($child, $result);
            })
            ->map(function ($el) use ($result) {
                $el->result = $result;
                return $el;
            });
        return $rpr;
    }

    /**
     * Check if the user has the RG65 flag. Based on user age applies relevant score
     *
     * @param RiskProfileRating $rpr
     * @param User $user
     * @return RiskProfileRating
     */
    protected function intensiveGambler($rpr, $user)
    {
        $age = Carbon::parse($user->dob)->age;
        $trigger = TriggersLog::sh($user->id)->where('trigger_name', 'RG65')->latest()->first();

        if($trigger === null) {
            $rpr->found_children = collect();
            return $rpr;
        }

        $rpr->found_children = $rpr->children
            ->filter(function ($child) use ($age) {
                $age_range = explode(",", $child->name);
                [$min, $max] = $age_range;

                if(count($age_range) === 1) {
                    return $age >= $min;
                }

                if(count($age_range) > 1) {
                    return $age >= $min && $age <= $max;
                }
                return false;
            })
            ->map(function ($el) use ($trigger) {
                $diffInDays = Carbon::parse($trigger->created_at)->diffInDays(Carbon::now());
                $el->result = "Trigger {$trigger->trigger_name} was activated {$trigger->created_at}.
                Days ago: {$diffInDays}";
                return $el;
            });
        return $rpr;
    }

    /**
     * Calculates how many RG flags have been triggered in the last _DAYS days
     *
     * @param RiskProfileRating $rpr
     * @param User $user
     * @return RiskProfileRating
     */
    protected function interactionProfileRiskFactor($rpr, $user)
    {
        $triggers_count = TriggersLog::sh($user->id)
            ->where('trigger_name', 'like', 'RG%')
            ->where('user_id', $user->id)
            ->whereDate('created_at', '>=', Carbon::now()->subDays($rpr->data['replacers']['_DAYS'])->toDate())
            ->whereDate('created_at', '<=', Carbon::now()->toDate())
            ->count();

        $rpr->found_children = $rpr->children
            ->filter(function ($child) use ($triggers_count) {
                $count_range = explode(",", $child->name);
                [$min, $max] = $count_range;

                if(count($count_range) === 1) {
                    return $triggers_count >= $min;
                }

                if(count($count_range) > 1) {
                    return $triggers_count >= $min && $triggers_count <= $max;
                }
                return false;
            })->map(function ($el) {
                $el->result = $el->score;
                return $el;
            });

        return $rpr;
    }

    /**
     * @param $rpr
     * @param $user
     *
     * @return mixed
     * @throws Exception
     */
    protected function popupsInteraction($rpr, $user)
    {
        $interactions = [
            'take-a-break' => 0,
            'edit-limits' => 0,
            'continue' => 0
        ];
        $actions_score = Config::getValue(
            'popups-interaction-buttons',
            'RG',
            [],
            false,
            true,
            true
        );
        foreach($interactions as $interaction_tag => $interaction_count) {
            $actions_count = Action::sh($user->id)
                ->where('descr', 'like', "%{$interaction_tag}%")
                ->where('target', $user->id)
                ->where('tag', 'automatic-flags')
                ->whereDate('created_at', '>=', Carbon::now()->subDays($rpr->data['replacers']['_DAYS'])->toDate())
                ->whereDate('created_at', '<=', Carbon::now()->toDate())
                ->count();
            $score = $actions_score[$interaction_tag] ?? 0;
            $interactions[$interaction_tag] = $actions_count * $score;
        }
        $interactions_total_score = array_sum($interactions);
        $rpr->found_children = $rpr->children
            ->filter(function ($child) use ($interactions_total_score) {
                $count_range = explode(",", $child->name);
                [$min, $max] = $count_range;

                if(count($count_range) === 1) {
                    return $interactions_total_score >= $min;
                }

                if(count($count_range) > 1) {
                    return $interactions_total_score >= $min && $interactions_total_score <= $max;
                }
                return false;
            })->map(function ($el) {
                $el->result = $el->score;
                return $el;
            });

        return $rpr;
    }

    /**
     * Return the maximum risk score that users have
     *
     * @param string $type RG, AML or ALL (which means both AML and RG)
     *
     * @return int
     */
    public static function getMaxRiskScore($type = 'ALL'): int
    {
        $bindings = [];
        $where_snippet = "";

        if ($type !== 'ALL') {
            $where_snippet = 'WHERE tl.trigger_name LIKE :type';
            $bindings = ['type' => $type . '%'];
        }

        $query = "
            SELECT SUM(t.score) AS score_sum
            FROM triggers_log tl
            JOIN triggers t ON tl.trigger_name = t.name
            $where_snippet
            GROUP BY user_id
            ORDER BY score_sum DESC
            LIMIT 1
        ";

        $result = phQget($query);
        if (empty($result)) {
            $result = phQset($query, DB::shsSelect('triggers_log', $query, $bindings), 60);
        }

        $max_risk_score = collect($result)->pluck('score_sum')->max();

        return (int)$max_risk_score;
    }

    /**
     * Return the list of the users that reviewed a user's AML / RG behavior
     *
     * @param string $type RG, AML or ALL (which means both AML and RG)
     *
     * @return array
     */
    public static function getAllReviewers($type = 'ALL'): array
    {

        $bindings = [];

        if ($type === 'ALL') {
            $where = " tag = :tag";
            $bindings['tag'] = strtolower($type . '-monitoring');
        } else {
            $where = " (tag = :tag1 OR tag = :tag2)";
            $bindings['tag1'] = strtolower(RiskProfileRating::RG_SECTION . '-monitoring');
            $bindings['tag2'] = strtolower(RiskProfileRating::AML_SECTION . '-monitoring');
        }

        $query = "SELECT DISTINCT actor FROM actions a WHERE {$where} ORDER BY actor";

        $result = phQget($query);
        if (empty($result)) {
            $actors = collect(DB::shsSelect('actions', $query, $bindings))->pluck('actor')->unique()->toArray();
            $result = phQset($query,
                User::query()->selectRaw("CONCAT(firstname,' ', lastname) as name, id")->whereIn('id',
                    $actors)->get()->keyBy('id')->toArray(), 300);
        }

        return $result;
    }

    /**
     * Report should show all accounts that have the selected filtered risk already.
     * The risk does not need to be triggered on the day selected to show.
     * For example, if searching for Medium/High Risk and TIS score of 21+,
     * all accounts that have Medium/High risk should show.
     * It means the latest available risk (within the risk filter)
     * below the end date of the date range will be obtained.
     *
     * @param string $trigger_type
     * @param array  $params
     * @param array  $bindings
     * @param bool   $cached
     * @param array  $exclude_countries
     *
     * @return array
     */
    public function getUsersWithRiskScores(
        string $trigger_type,
        array $params,
        array $bindings,
        bool $cached = true,
        array $exclude_countries = []
    ): array {
        $min_tis_score = 0;
        $max_tis_score = 100000;
        $country = $bindings['country'];
        $bindings['score_start'] = !empty($bindings['score_start']) ? $bindings['score_start'] : $min_tis_score;
        $bindings['score_end'] = !empty($bindings['score_end']) ? $bindings['score_end'] : $max_tis_score;
        $getCondition = function () use ($params, &$bindings, $exclude_countries, $trigger_type) {

            $where = " AND tl.created_at BETWEEN :start1 AND :end1";

            if (!empty($params['user_id'])) {
                $where .= " AND tl.user_id = :user_id";
                $bindings["user_id"] = $params['user_id'];
            }

            if ($bindings["country"] != 'all') {
                $where .= " AND u.country = :country";
            } elseif (!empty($exclude_countries)) {
                $where .= " AND u.country NOT IN " . DataFormatHelper::arrayToSql($exclude_countries, true);
                unset($bindings["country"]); // to prevent SQLSTATE[HY093]: Invalid parameter number
            }

            if ($trigger_type != 'ALL') {
                $where .= " AND tl.trigger_name LIKE :triggertype";
                $bindings["triggertype"] = $trigger_type . "%";
            }

            return $where;
        };

        $table = UserRepository::getRiskScoreQuery('score', 'sum(t.score)', '0', $getCondition());
        $where = "";

        if (!empty($bindings['profile_rating_tags'])) {
            $where = 'AND (rprl2.rating_tag IN (' . $bindings['profile_rating_tags'] . ') OR rprl2.rating_tag IS NULL)';
        }

        if (!empty($params['user_id'])) {
            $where .= " AND rprl2.user_id = {$params['user_id']}";
        }

        $and_where_country = "";
        if ($country != 'all') {
            $and_where_country = "AND u.country = '{$country}'";
        } elseif (!empty($exclude_countries)) {
            $and_where_country = "AND u.country NOT IN " . DataFormatHelper::arrayToSql($exclude_countries, true);
        }

        // REGEXP_SUBSTR to get prefix from trigger_name (e.g. AML19 -> AML, RG7 -> RG)
        $actions_tag = "CONCAT(
            REGEXP_SUBSTR(trigger_name, '[a-zA-Z]+'),
            '-monitoring'
          )
        ";

        $last_reviewer_join = "
          LEFT JOIN actions ON actions.target = rprl.user_id AND actions.id = (
            SELECT MAX(id)
            FROM actions
            WHERE target = rprl.user_id AND tag = {$actions_tag}
          )
        ";

        $and_where_last_reviewer = "";
        if ($params['selected_reviewer'] !== 'all') {
            $and_where_last_reviewer = "AND actions.actor = :actor_id";

            $bindings['actor_id'] = $params['selected_reviewer'];
        }

        $query = "
          SELECT rprl.user_id,
                 rprl.rating AS profile_rating,
                 rprl.rating_tag AS profile_rating_tag,
                 u.username,
                 u.country,
                 u.declaration_proof,
                 IFNULL(st.score, 0) AS score,
                 st.trigger_name,
                 actions.actor as actor_id,
                 actions.actor_username as actor_name,
                 actions.created_at as created_at,
                 NULL as last_comment_datetime
          FROM risk_profile_rating_log rprl
          JOIN (
              SELECT id,
                     user_id,
                     rating_tag,
                     ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY created_at desc) as row_number
                FROM risk_profile_rating_log
                WHERE created_at <= '{$bindings['end1']}' AND rating_type = '{$trigger_type}'
          ) AS rprl2 ON rprl2.user_id = rprl.user_id AND rprl2.id = rprl.id AND rprl2.row_number = 1 $where
          LEFT JOIN (
              SELECT user_id,
                     sum(first_score) AS score,
                     trigger_name
              FROM ($table) AS sub
              GROUP BY user_id
              HAVING score BETWEEN :score_start AND :score_end
          ) AS st ON (st.user_id = rprl.user_id)
          LEFT JOIN (
              SELECT u.id as user_id,
                     u.username,
                     u.country,
                     concat(
                        IFNULL(formus.value, 0),
                        IFNULL(proofus.value, 0),
                        IFNULL(forcedloss.value, 0),
                        IFNULL(forcedwager.value, 0),
                        IFNULL(forceddep.value, 0),
                        u.active
                     ) as declaration_proof
              FROM users u
              LEFT JOIN users_settings formus ON formus.user_id = u.id
              AND formus.setting = 'source_of_funds_activated'
              LEFT JOIN users_settings proofus ON proofus.user_id = u.id
              AND proofus.setting = 'proof_of_wealth_activated'
              LEFT JOIN users_settings forceddep ON forceddep.user_id = u.id
              AND forceddep.setting = 'force-dep-lim'
              LEFT JOIN users_settings forcedloss ON forcedloss.user_id = u.id
              AND forcedloss.setting = 'force-lgaloss-lim'
              LEFT JOIN users_settings forcedwager ON forcedwager.user_id = u.id
              AND forcedwager.setting = 'force-lgawager-lim'
          ) as u ON u.user_id = rprl.user_id
          {$last_reviewer_join}
          WHERE IFNULL(st.score, 0) BETWEEN {$bindings['score_start']} AND {$bindings['score_end']}
              {$and_where_country}
              {$and_where_last_reviewer}
        ";

        $data = phQget($query);
        if ($cached || empty($data)) {
            $data = DB::shsSelect('triggers_log', $query, $bindings);

            //the join on db is quite slow, as there are many joined tables
            $tmp_data = [];
            foreach($data as $row){
                $tmp_data[$row->user_id] = $row;
            }

            $user_ids = implode(',', array_keys($tmp_data));
            if ($user_ids !== '') {
                $comments = DB::shsSelect('users_comments', "SELECT MAX(uc.created_at) as created_at, uc.user_id
                  FROM users_comments uc
                  WHERE uc.tag IN ({$params['comment_tags']})
                  AND user_id IN ({$user_ids})
                  GROUP BY user_id");
                foreach($comments as $row){
                    $tmp_data[$row->user_id]->last_comment_datetime = $row->created_at;
                }
            }

            $data = phQset($query, $tmp_data, 120);
        }

        return $data;
    }

    public static function getLatestProfileRatingQuery(): string
    {
        return "
            SELECT * FROM risk_profile_rating_log
            WHERE id IN(
                SELECT max(id) FROM risk_profile_rating_log
                GROUP BY user_id, rating_type
            )
        ";
    }

    /**
     * @param string $section
     * @param int    $user_id
     *
     * @return int|null
     */
    public function getProfileScoreFromDb(string $section, int $user_id)
    {
        $profile_rating_log_subquery = self::getLatestProfileRatingQuery();
        $query = "
            SELECT prl.user_id, prl.rating
            FROM ({$profile_rating_log_subquery}) AS prl
            WHERE prl.user_id = :user_id AND prl.rating_type = :section
        ";

        $bindings = [
            'user_id' => $user_id,
            'section' => $section
        ];

        $data = phQget($query);
        if (empty($data)) {
            $data = phQset($query, DB::shSelect($user_id, 'risk_profile_rating_log', $query, $bindings), 60);
        }

        return $data[0]->rating ?? null;
    }

    public static function getProfileScoreRating(int $user_id, string $rating_type = RiskProfileRating::RG_SECTION): int
    {
        $query = "
            SELECT rating FROM risk_profile_rating_log
            WHERE id IN(
                SELECT max(id)
                FROM risk_profile_rating_log
                GROUP BY user_id, rating_type
            ) AND
            user_id = :user_id AND
            rating_type = :rating_type
        ";

        $bindings = [
            'user_id' => $user_id,
            'rating_type' => $rating_type,
        ];

        $res = DB::shSelect($user_id, 'risk_profile_rating_log', $query, $bindings);

        return $res[0]->rating ?? -1;
    }

    /**
     * Select data from risk_profile_rating_log
     *
     * @param           $request
     * @param           $date_range
     * @param array     $rating_tags
     * @param           $cols
     * @param string    $type
     * @param User|null $user
     *
     * @return array
     */
    public static function getGRSSCoreReport(
        $request,
        $date_range,
        array $rating_tags,
        $cols,
        $type = '',
        User $user = null
    ) {
        if ($user) {
            $query = RiskProfileRatingLog::sh($user->getKey(), true)
                ->select($cols)
                ->selectRaw('risk_profile_rating_log.rating_type as rating_type')
                ->selectRaw(DB::raw("MAX(`created_at`) as created_at"));
            $where = " user_id  = {$user->getKey()}";

            if (!empty($request->get('country')) && $request->get('country') != 'all') {
                $query->where('users.country', $request->get('country'));
            }

            if (!empty($type)) {
                $where .= " AND rprl.rating_type = '{$type}'";
            }
            if (!empty($rating_tags)) {
                $commaSeperatedVal = implode("', '" , $rating_tags);
                $where .= " AND ( rprl.rating_tag in ( '{$commaSeperatedVal}' )";
                $where .= " OR rprl.rating_tag is null)";
            }

            $where .= "AND rprl.created_at >= '{$date_range->getStart()}'";
            $where .= " AND rprl.created_at <= '{$date_range->getEnd()}'";

            $query->join(
                DB::raw("(SELECT rprl.user_id,rprl.rating_type,DATE(created_at) AS date, MAX(rating) AS max_rating
                  FROM risk_profile_rating_log rprl
                  WHERE {$where}
                  GROUP BY rating_type, Date(rprl.created_at)) as max_ratings"), function ($join) {
                $join->on('risk_profile_rating_log.rating_type', '=', 'max_ratings.rating_type');
                $join->on(DB::raw("DATE(risk_profile_rating_log.created_at)"), '=', 'max_ratings.date');
                $join->on('risk_profile_rating_log.rating', '=', 'max_ratings.max_rating');
                $join->on('risk_profile_rating_log.user_id', '=', 'max_ratings.user_id');
            })
                ->groupBy(DB::raw("DATE(risk_profile_rating_log.created_at)"), 'risk_profile_rating_log.rating_type')
                ->orderBy('risk_profile_rating_log.created_at', 'desc')
                ->orderBy("risk_profile_rating_log.rating_type", "asc");

        } else {
            $query = RiskProfileRatingLog::shs()
                ->select($cols)
                ->selectRaw('risk_profile_rating_log.rating_type as rating_type')
                ->selectRaw('risk_profile_rating_log.created_at as created_at')
                ->leftjoin('users', 'users.id', '=', 'risk_profile_rating_log.user_id');
            if (!empty($request->get('user_id'))) {
                $query->where('risk_profile_rating_log.user_id', $request->get('user_id'));
            }

            if (!empty($request->get('country')) && $request->get('country') != 'all') {
                $query->where('users.country', $request->get('country'));
            }

            if (!empty($type)) {
                $query->where('risk_profile_rating_log.rating_type', $type);
            }
            if (!empty($rating_tags)) {
                $query->where(function ($query) use ($rating_tags) {
                    $query->whereIn('risk_profile_rating_log.rating_tag', $rating_tags);
                    $query->orWhereNull('risk_profile_rating_log.rating_tag');
                });
            }
            $query->where('risk_profile_rating_log.created_at', '>=', $date_range->getStart())
                ->where('risk_profile_rating_log.created_at', '<=', $date_range->getEnd())
                ->orderBy('risk_profile_rating_log.id', 'desc');

        }

        $transformedData = collect($query->get())->map(function ($item) use ($type) {
            $influencedBy = collect(json_decode($item['influenced_by'], true));
            $item['influenced_by'] = $influencedBy->mapWithKeys(function ($value, $key) use ($type) {
                $rgMapping = self::rating_settings[strtoupper($type)] ?? [];
                $mappedKey = $rgMapping[$key] ?? $key;
                if ($key === 'ngr_last_6_months') {
                    $mappedKey = str_replace('X', '6', $mappedKey);
                } elseif ($key === 'ngr_last_12_months') {
                    $mappedKey = str_replace('X', '12', $mappedKey);
                }
                return [
                    $mappedKey => $value
                ];
            })->toArray();
            return $item;
        });
        return $transformedData->toArray();
    }

    /**
     * @param string $section
     * @param int    $user_id
     *
     * @return string|null
     */
    public static function getLatestGRSTag(string $section, int $user_id): ?string
    {
        $profile_rating_log_subquery = self::getLatestProfileRatingQuery();
        $query = "
            SELECT prl.user_id, prl.rating_tag
            FROM ({$profile_rating_log_subquery}) AS prl
            WHERE prl.user_id = :user_id AND prl.rating_type = :section
        ";

        $bindings = [
            'user_id' => $user_id,
            'section' => $section
        ];

        $data = phQget($query);
        if (empty($data)) {
            $data = phQset($query, DB::shSelect($user_id, 'risk_profile_rating_log', $query, $bindings), 60);
        }

        return $data[0]->rating_tag ?? null;
    }

    /**
     * If the GRS score is in the medium/high risk range, reduces it to the low risk maximum score
     * Returns the new score
     *
     * @param int $score
     * @param string $jurisdiction
     * @param string $section
     * @return int
     */
    protected function customerCannotTriggerMediumHighRisk($score, $jurisdiction, $section)
    {
        $category_settings = static::getCategorySettings(
            RiskProfileRating::RATING_SCORE_PARENT_CATEGORY,
            $jurisdiction,
            $section
        );
        $low_risk_max_score = array_column($category_settings, 'score', 'name')[self::LOW_RISK_TAG] ?? null;
        if ($low_risk_max_score !== null && $score > (int) $low_risk_max_score) {
            $score = (int) $low_risk_max_score;
        }

        return $score;
    }
}
