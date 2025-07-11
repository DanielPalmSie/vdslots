<?php

class Arf {

    /**
     * @var array
     * This will contain the cached config values once we get them from the DB to avoid doing extra DB calls.
     */
    protected $config_cache = [];

    /**
     * @var array
     * This will contain the cached flags (RG/AML) for each user_id once we get the score, to avoid doing extra DB calls
     */
    protected $score_cache = [];

    /**
     * @var Config
     */
    protected $config = null;

    /**
     * @var SQL
     */
    protected $replica = null;

    /**
     * @var DBUserHandler
     */
    protected DBUserHandler $uh;

    const METHODS_THAT_REQUIRE_CALCULATION = ['onLogin', 'onDeposit'];

    /**
     * Default values used to check if the player score is in range for the flags that support
     * "$trigger-score-start/end", when the value is not specified on the DB.
     * - RG goes from 0 to 100
     * - AML goes from 0 to 10
     */
    const DEFAULT_SCORE_RANGE = [
        'RG' => [
            'start' => 80,
            'end' => 100,
        ],
        'AML' => [
            'start' => 8,
            'end' => 10,
        ]
    ];

    function __construct()
    {
        $this->classes = ['Aml', 'Rg', 'Fr'];

        $this->config = phive('Config');
        $this->def_cur = phive('Currencer')->getSetting('base_currency');
        $this->replica = phive('SQL')->readOnly();
        $this->uh = phive('UserHandler');
    }

    /**
     * Invokes a certain method with a variable number of arguments
     * for all classes specified in the constructor.
     *
     * The first argument must always be the method to invoke,
     * all other arguments will be the arguments for the specified method.
     *
     * Examples
     * Direct call:         phive('Cashier/Arf')->invoke('onDeposit', $arg1, $arg2 ... );
     * Asynchronous call:   phive->pexec('Cashier/Arf', 'invoke' [$arg1, $arg2 ... ]);
     *
     * @return mixed
     */
    function invoke()
    {
        if(phive('DBUserHandler')->getSetting('arf_on') !== true) {
            return;
        }

        // we grab all the configs once, and then we pass them to the class to reduce
        $configs = $this->getAllConfigs();
        $this->preloadConfigs($configs);

        $args = func_get_args();
        $method = array_shift($args);

        // TODO check if we need to add more methods that require a calculation beforehand (Ex. CRONS???)
        $preloadScore = in_array($method, self::METHODS_THAT_REQUIRE_CALCULATION);
        if($preloadScore) {
            // user_id is the first $args for those methods
            $this->updateRatingScore($args[0]);
        }

        foreach($this->classes as $class){
            $obj = phive("Cashier/$class");
            if (empty($obj)) {
                return false;
            }

            $obj->preloadConfigs($configs);
            if($preloadScore) {
                $obj->preloadScore($this->score_cache);
            }

            if (method_exists($obj, $method)) {
                call_user_func_array([$obj, $method], $args);
            }
        }
    }



    /**
     *
     * ARF HELPERS - START >>>
     *
     */
    /**
     * This method will preload all the configs from DB for RG/AML in 1 query, so we can avoid multiple single queries around the code.
     * We are keeping the calls to "getAndCacheConfig" that will used the cached value, if exist, and in case a value is not defined it will use the fallback default.
     *
     * This one is called once on "invoke" then the configs are passed in all the childs with "preloadConfigs"
     */
    private function getAllConfigs() {
        return $this->config->getByTags(['AML', 'RG'], true);
    }

    /**
     * Preload all the configs on the child classes
     *
     * @param $configs
     */
    protected function preloadConfigs($configs) {
        $this->config_cache = $configs;
    }

    /**
     * Get a config, using the same parameters as the commonly used Config->getValue()
     * but we keep a cached version in memory to avoid triggering the same query hundred of times inside some loops.
     *
     * @param $config_tag
     * @param $config_name
     * @param $default
     * @return mixed
     */
    public function getAndCacheConfig($config_tag, $config_name, $default = null)
    {
        if ($this->config_cache[$config_tag][$config_name]) {
            return $this->config_cache[$config_tag][$config_name];
        }

        $config = $this->config->getByNameAndTag($config_name, $config_tag);

        if(! empty($config)) {
            $this->config_cache[$config_tag][$config_name] = $default;
        }

        $config_value = $this->config->getValueFromTemplate($config) ?? $default;
        $this->config_cache[$config_tag][$config_name] = $config_value;

        return $config_value;
    }

    /**
     * Preload all the score on the child classes
     *
     * @param $score
     */
    protected function preloadScore($score) {
        $this->score_cache = $score;
    }

    /**
     * Update the risks score for the requested user, this will call the "BoApi" and return back RG/AML scores.
     * Scores are cached in memory during the class execution for each single user.
     *
     * When called with 'all' AML50 & RG37 are checked inside this method.
     *
     * @param $user
     * @param $update_type - Default 'all' | 'AML' | 'RG'
     * @param $specific_rpr - used for AML only in some specific scenario (Ex. AML28 & AML43)
     * @return array|mixed
     */
    protected function updateRatingScore($user, $update_type = 'all', $specific_rpr = null)
    {
        $user = cu($user);
        if (!empty($user)) {
            $checkAML = $update_type == 'all' || $update_type == 'AML';
            $checkRG = $update_type == 'all' || $update_type == 'RG';

            if ($checkAML) {
                $old_aml_score = $this->getLatestRatingScore($user->getId(), 'AML');
                $old_aml_score_tag = $this->getLatestRatingScore($user->getId(), 'AML', 'tag');
                $score_year_ago = $this->getLatestRatingScore($user->getId(), 'AML', 'global', " AND created_at < (NOW() - INTERVAL 12 MONTH)");
            }

            if($checkRG) {
                $old_rg_score = $this->getLatestRatingScore($user->getId(), 'RG');
            }

            $this->calculateScoreAPI($user->getId(), $update_type, $specific_rpr);

            if($checkAML) {
                $trigger_name = 'AML50';
                $new_aml_score = $this->score_cache[$user->getId()]['AML']['global'];
                $new_aml_score_tag = $this->score_cache[$user->getId()]['AML']['tag'];
                $grs_thold = phive('Config')->valAsArray('AML', 'AML50', ' ', ':', 'min:80 max:80');

                if ($old_aml_score < $grs_thold['min'] && $new_aml_score >= $grs_thold['min']) {
                    phive('UserHandler')->logTrigger($user, $trigger_name, "{$trigger_name} was triggered, score changes from {$old_aml_score_tag} to {$new_aml_score_tag}");
                }

                $trigger_name = 'AML49';
                $grs_thold = phive('Config')->valAsArray('AML', $trigger_name, ' ', ':', 'min:70 max:79');
                if (
                    !empty($score_year_ago) &&
                    (
                        $old_aml_score < $grs_thold['min'] &&
                        ($new_aml_score >= $grs_thold['min'] && $new_aml_score <= $grs_thold['max'])
                    )
                ) {
                    phive('UserHandler')->logTrigger($user, $trigger_name, "The global score changes from {$old_aml_score_tag} to {$new_aml_score_tag}");
                }
            }
            if($checkRG) {
                $trigger_name = 'RG37';

                if (!$this->uh->hasTriggeredLastPeriod($user->getId(), $trigger_name, 7)) {
                    $new_rg_score = $this->score_cache[$user->getId()]['RG']['global'];
                    $new_rg_score_tag = $this->score_cache[$user->getId()]['RG']['tag'];

                    if($this->didNewScoreBreakThreshold($old_rg_score, $new_rg_score, $trigger_name, 'RG')) {
                        phive('UserHandler')->logTrigger($user, $trigger_name, "{$trigger_name} triggered for user with score {$new_rg_score_tag}");
                    }
                }
            }
        }
    }

    /**
     * Wrapper for the call to "BoApi", we cache the results in memory into "score_cache" according to which API is being called
     * - When ALL is called all the flags (global + all the specific) are being updated
     * - When a TYPE is passed (RG/AML) ONLY the "global" value is being updated.
     * - When a TYPE and a SPECIFIC_RPR are being passed ONLY the requested "specific_rpr" value is being update.
     *
     * @param int $user_id
     * @param string $type
     * @param null $specific_rpr
     */
    public function calculateScoreAPI($user_id, $type = 'all', $specific_rpr = null) {
        $body = ['user_id' => $user_id, 'jurisdiction' => cu($user_id)->getJurisdiction()];
        if($type == 'all') {
            // will contain for each type (RG & AML) an array with all the specific_rpr + global
            // ['rg' => ['global'=>X1, 'some_rpr' => X2, ...], 'aml' => ['global'=>Y1, 'some_rpr' => Y2, ...]]
            $response = phive()->postToBoApi("/risk-profile-rating/calculate-score/all", $body);
        } else {
            $body['section'] = $type;
            if(!empty($specific_rpr)) {
                $body['specific_rpr'] = $specific_rpr;
            }
            // will return the score for the single requested specific_rpr
            // ['score' => X]
            $response = phive()->postToBoApi("/risk-profile-rating/calculate-score/", $body);
        }

        $updated_scores = json_decode($response, true);

        // failed api request
        if (empty($response)) {
            phive()->dumpTbl("BO-API-ERROR:risk-profile-rating", ['params' => json_encode($body), $response]);
            return ['rg' => 0, 'aml' => 0];
        }

        // we cache and return the score.
        if(!isset($this->score_cache[$user_id])) {
            $this->score_cache[$user_id] = [];
        }

        if($type == 'all') {
            $this->score_cache[$user_id]['RG'] = $updated_scores['rg'];
            $this->score_cache[$user_id]['AML'] = $updated_scores['aml'];
        } else {
            if(!isset($this->score_cache[$user_id][$type])) {
                $this->score_cache[$user_id][$type] = [];
            }

            // we update "global" key if a "specific_rpr" is not being passed
            $subtype = 'global';
            if (!empty($specific_rpr)) {
                $subtype = $specific_rpr;
            }
            $this->score_cache[$user_id][$type][$subtype] = $updated_scores['score'];
        }
    }

    /**
     * Check if a user score, after recalculation, had an increase greater than the config threshold
     * Ex. config with threshold = 5, the user before had 3, after recalculation he get a 6, we need to report it.
     *
     * @param $old_rating
     * @param $new_rating
     * @param $trigger_name
     * @param $rating_type - RG | AML
     * @return bool
     */
    public function didNewScoreBreakThreshold($old_rating, $new_rating, $trigger_name, $rating_type)
    {

        $threshold = $this->getAndCacheConfig($rating_type, $trigger_name);

        if($old_rating < $threshold && $new_rating >= $threshold) {

            return true;
        }

        return false;
    }

    /**
     * Return the latest "RG"/"AML" score for the player from the DB, if no score is found we return 0.
     * the player get his score calculated onLogin and onDeposit, so if there is no score for the current day it mean the user didn't play today
     *
     * @param integer $user_id
     * @param string $rating_type - RG | AML which rating we want back.
     * @param string $rating_subtype - a specific_rpr is requested:
     * global - to show user global score in INT eq
     * tag - to show the global score tag name e.g. Social Gambler, Low Risk, Medium Risk or High Risk
     *
     * @param string $extra_where - and extra in where clause
     * @return integer - User Score or 0.
     */
    public function getLatestRatingScore($user_id, $rating_type, $rating_subtype = 'global', $extra_where = '')
    {
        // we already got the score once for the player during calculation, we don't hit the DB again
        // Cached value works for all the $rating_subtype, cause we set all the flags on the initial call to updateRatingScore with type "all"
        if (
            $this->score_cache[$user_id] &&
            $this->score_cache[$user_id][$rating_type] &&
            $this->score_cache[$user_id][$rating_type][$rating_subtype]
        ) {
            return $this->score_cache[$user_id][$rating_type][$rating_subtype];
        }

        $score = 0;
        $rating_tag = null;
        // we only want the latest inserted score for the player
        $latest_rating = $this->replica
            ->sh($user_id)
            ->loadAssoc("
                    SELECT user_id, rating_type, rating_tag, rating, influenced_by
                    FROM risk_profile_rating_log
                    WHERE user_id = {$user_id}
                    AND rating_type = '{$rating_type}'
                    {$extra_where}
                    ORDER BY id DESC
                ");

        // we cache and return the score.
        if (! isset($this->score_cache[$user_id])) {
            $this->score_cache[$user_id] = [];
        }

        if (! isset($this->score_cache[$user_id][$rating_type])) {
            $this->score_cache[$user_id][$rating_type] = [];
        }

        // we only store the "global" rating on the DB for now
        if ($rating_subtype === 'global') {
            // If we have a score from the DB we use it
            if (! empty($latest_rating) && ! empty($latest_rating['rating'])) {
                $score = $latest_rating['rating'];
            }

            $this->score_cache[$user_id][$rating_type][$rating_subtype] = $score;
        } else if ($rating_subtype === 'tag') {
            if (! empty($latest_rating) && ! empty($latest_rating['rating_tag'])) {
                $rating_tag = $latest_rating['rating_tag'];
            }

            $this->score_cache[$user_id][$rating_type][$rating_subtype] = $rating_tag;
        } else { // a specific subtype is required that is not cached.

            if (! empty($latest_rating['influenced_by'])) {
                $categories = json_decode($latest_rating['influenced_by'], true);

                if (isset($categories[$rating_subtype])) {
                    return $categories[$rating_subtype];
                }
            }
            // TODO in this scenario shall we do a call to the BOAPI? just to play it safe if for some reason a flag is not defined.
            //  Ex. check if there are edge cases (Ex. on CRONS) where there is a direct call to (Aml,Rg,Fr) instead of passing by the "invoke" function that preload all the scores.
            $this->calculateScoreAPI($user_id); // this will update score_cache
        }

        return $this->score_cache[$user_id][$rating_type][$rating_subtype];
    }

    /**
     * Check if the user score falls inside the range for the trigger and
     * if we already have triggered the flag in the last X days/months
     * when we return "false" we can skip the check.
     *
     * @param string $rating_type - AML | RG
     * @param int $user_id
     * @param string $trigger - Ex. RG28
     * @param int|null $enforced_score - if we already know the score and want to avoid doing the query.
     * @param int $time - number of days/weeks/months (frequency) in the past to check if it has already triggered.
     * @param string $frequency - DAY | WEEK | MONTH
     * @return bool
     */
    protected function isUserScoreInTriggerRange(
        string $rating_type,
        int $user_id,
        string $trigger,
        $enforced_score = null,
        int $time = 1,
        string $frequency = 'DAY'
    ): bool
    {
        if ($this->uh->hasTriggeredLastPeriod($user_id, $trigger, $time, $frequency)) {
            return false;
        }

        // we can add "rating_subtype" as param to be passed to getLatestRatingScore() if we need to retrieve a specific_rpr instead of the global flag (not needed ATM)
        $score = empty($enforced_score) ? $this->getLatestRatingScore($user_id, $rating_type) : $enforced_score;

        if ($score === false) {
            return false;
        }

        $trigger_score_start = $this->getAndCacheConfig($rating_type, "$trigger-score-start", self::DEFAULT_SCORE_RANGE[$rating_type]['start']);
        $trigger_score_end = $this->getAndCacheConfig($rating_type, "$trigger-score-end", self::DEFAULT_SCORE_RANGE[$rating_type]['end']);
        if ($score >= $trigger_score_start && $score <= $trigger_score_end) {
            return true;
        }
        return false;
    }
    /**
     * Return how much it has changed before/after in percentage "xx%"
     *
     * @param $current
     * @param $previous
     * @return float|int
     */
    protected function getChangeInPercentage($current, $previous)
    {
        // we return 0 for users with no data on the previous days (Ex. new user) to avoid INF or NAN
        if ($previous === 0) {
            return 0;
        }
        return (($current / $previous) - 1) * 100; // -1 (so we end up with 20% instead of 120% if $current is 120 and $previous is 100)
    }

    /**
     * Sending emails works by providing just the flag name and the recipient, everything else is handled automatically under the hood
     *
     * @param DBUser|integer $user
     * @param $flag - example: AMLXX, RGYY
     * @return bool
     */
    public function sendArfEmail($user, $flag) {
        if (empty($user = cu($user))) {
            return false;
        }

        return phive("MailHandler2")->sendMail(strtolower($flag), $user);
    }

    /**
     * Method used to add comments related to triggers
     *
     * @param DBUser $user
     * @param string $flag - ex: AMLXX, RGYY
     * @param string $type - in [RG/AML/FR]
     * @param null|string $message
     * @param string $risk_group - none|green|yellow|orange|red|purple\black
     *                             if user already belong to a risk group we always use user setting as priority
     * @param null|int $grs - Global Risk Score with value in interval 0-10
     * @param string $follow_up - value in [no, weekly, monthly, quarterly, halfyearly, yearly]
     * @return bool
     */
    public function addArfComment($user, $flag, $type = 'RG', $message = null, $risk_group = 'green', $grs = null, $follow_up = 'no') {
        if (empty($user = cu($user))) {
            return false;
        }

        if (is_null($grs)) {
            $grs = $this->getLatestRatingScore($user->getId(), $type, 'tag');
        }

        if (is_null($message)) {
            $message = "Automated Message has been sent to the player";
        }

        $user_risk_group = $user->getSetting(strtolower($type).'-risk-group');
        if(!empty($user_risk_group)) {
            $risk_group = $user_risk_group;
        }

        $message = "$message | $type GRS: $grs | Flags: $flag | Risk Group: $risk_group | Follow up: $follow_up";

        return $user->addComment($message, false, $user->getCommentTagByType($type));
    }

    /**
     * This is a helper method to send an email and add comment for specific triggers
     *
     * @param DBUser $user
     * @param string $trigger - ex: AMLXX, RGYY
     * @param string $type - in [RG/AML/FR]
     * @return bool
     */
    public function sendEmailAndComment($user, $trigger, $type = 'RG') {
        if (empty($user = cu($user))) {
            return false;
        }

        $this->sendArfEmail($user, $trigger);
        $this->addArfComment($user, $trigger, $type);

        return true;
    }

    /**
     * Update the table risk_profile_rating_log to set the score daily
     *
     * @param $date
     * @param $db
     */
    public function riskProfileRatingLogDailyCron($date, $db)
    {
        $db->query("
            CREATE TEMPORARY table id_rating_log
            SELECT max(id) AS id_log FROM risk_profile_rating_log GROUP BY user_id, rating_type;
            ");
        $users = $db->loadArray("
            SELECT users.id, rating_type, rating, created_at
            FROM users
                     INNER JOIN risk_profile_rating_log on user_id = users.id
                     INNER JOIN id_rating_log on risk_profile_rating_log.id = id_rating_log.id_log
            WHERE users.active = 1 AND risk_profile_rating_log.created_at < '$date'");
        $db->query("DROP table id_rating_log");
        foreach ($users as $insert) {
            $rg = [
                'rating_type' => $insert['rating_type'],
                'rating' => $insert['rating'],
                'created_at' => phive()->hisNow(),
                'user_id' => $insert['id']
            ];
            $db->insertArray('risk_profile_rating_log', $rg);
        }
    }

    /**
     * Checks if a config value is set to ON
     *
     * @param string $config_tag
     * @param string $config_name
     * @param $default
     * @return bool
     */
    public function isChoiceConfigTurnedOn(string $config_tag, string $config_name, $default = null): bool
    {
        $config_value = $this->getAndCacheConfig($config_tag, $config_name, $default);

        return $config_value === 'on';
    }

    /**
     * Returns Net deposit (non cents)
     *
     * @param DbUser $user
     * @param int    $interval_num
     * @param string $interval_type MONTH | DAY | HOUR
     *
     * @return void
     */
    public function getNetDeposit(DbUser $user, int $interval_num = 1, string $interval_type = 'MONTH')
    {
        $user_id = $user->getId();
        $query = "SELECT
                    u.id,
                    deposits.sum as total_deposits,
                    withdrawals.sum as total_withdrawals,
                    (deposits.sum - withdrawals.sum) as net_deposit
                FROM users u
                LEFT JOIN (
                    SELECT IFNULL(SUM(amount) /  100, 0) as sum, user_id
                           FROM deposits
                           WHERE timestamp > CURRENT_TIMESTAMP() - INTERVAL {$interval_num} {$interval_type}
                           AND status != 'disapproved'
                           GROUP BY user_id
                ) as deposits ON deposits.user_id = u.id
                LEFT JOIN (
                    SELECT IFNULL(SUM(amount) / 100, 0) as sum, user_id
                           FROM pending_withdrawals
                           WHERE timestamp > CURRENT_TIMESTAMP() - INTERVAL {$interval_num} {$interval_type}
                           AND status != 'disapproved'
                           GROUP BY user_id
                ) as withdrawals ON withdrawals.user_id = u.id
                WHERE u.id = {$user_id};";
        $result = phive('SQL')->sh($user_id)->loadArray($query);

        return (int)($result[0]['net_deposit'] ?? 0);
    }

    /**
     *
     * ARF HELPERS - END <<<
     *
     */
}


