<?php

namespace App\RgEvaluation\ActivityChecks;

use App\Extensions\Database\FManager as DB;
use App\Models\Config;

class SpendMoreTimePlayingCheck extends BaseActivityCheck
{
    protected const DAYS_THRESHOLD = 3;
    protected const HOURS_THRESHOLD = 4;
    public function evaluate(): EvaluationResult
    {
        $state = $this->getTrigger()->getCurrentState();
        $evaluationInterval = $state->currentEvaluationInterval();
        $hoursThreshold = (int)Config::getValue('RG8', 'RG', static::HOURS_THRESHOLD);
        $highActivityDays = $this->getHighActivityDays(
            $this->getUser()->id,
            $evaluationInterval,
            $hoursThreshold
        );
        $hasDecreasedPlayingTime = count($highActivityDays) < static::DAYS_THRESHOLD;
        $evaluationResult = $this->getEvaluationResult();
        $evaluationResult->setResult($hasDecreasedPlayingTime);
        $evaluationResult->setEvaluationVariables([
            'evaluation_interval' => $this->getTrigger()->getCurrentState()->currentEvaluationInterval(),
            'nex_evaluation_in_days' => $state->nexEvaluationInterval(),
            'hours_threshold' => $hoursThreshold,
        ]);

        return $evaluationResult;
    }

    /**
     * @param int $userId
     * @param int $intervalInDays
     * @param int $hoursThreshold
     *
     * @return array
     */
    private function getHighActivityDays(int $userId, int $intervalInDays, int $hoursThreshold): array
    {
        $sql = "SELECT
                    user_sessions_timing.user_id,
                    user_sessions_timing.game_day,
                    ROUND(SUM(user_sessions_timing.session_in_secs) / 3600, 2) AS hours_diff,
                    IF(ROUND(SUM(user_sessions_timing.session_in_secs) / 3600, 2) > 4,1,0) AS warning_date
                FROM
                    (
                    SELECT
                        ugs.user_id,
                        DATE(ugs.start_time) AS game_day,
                        ugs.start_time,
                        ugs.end_time,
                        TIME_TO_SEC(TIMEDIFF(ugs.end_time, ugs.start_time)) AS session_in_secs
                    FROM
                        users_game_sessions ugs
                    WHERE
                        ugs.user_id = '{$userId}'
                        AND ugs.bet_cnt > 0
                        AND ugs.end_time > NOW() - INTERVAL {$intervalInDays} DAY) AS user_sessions_timing
                GROUP BY
                    DATE(game_day)
                HAVING
                    hours_diff > {$hoursThreshold};";

        return DB::shSelect(
            $userId,
            'users_game_sessions',
            $sql
        );
    }
}