<?php

namespace App\RgEvaluation\ActivityChecks;

use App\Extensions\Database\FManager as DB;
use Carbon\Carbon;

class AverageTotalBetsPerDay extends BaseActivityCheck
{

    public function evaluate(): EvaluationResult
    {
        $now = Carbon::now();
        $state = $this->getTrigger()->getCurrentState();
        $evaluationInterval = $state->currentEvaluationInterval();
        $initialActivityStartOn = $now->copy()->subDays(
            $evaluationInterval + $this->activitiesIntervalInDaysBeforeEvaluationStarted
        )->toDateString();
        $initialActivityEndOn = $postActivityStartOn = $now->copy()->subDays($evaluationInterval)->toDateString();
        $postActivityEndOn = $now->toDateString();
        $initialAverageBets = $this->getAverageBetsPerDay($this->getUser()->id, $initialActivityStartOn, $initialActivityEndOn);
        $currentAverageBets = $this->getAverageBetsPerDay($this->getUser()->id, $postActivityStartOn, $postActivityEndOn);
        $evaluationResult = $this->getEvaluationResult();
        $evaluationResult->setResult($currentAverageBets < $initialAverageBets);
        $evaluationResult->setEvaluationVariables([
            'evaluation_interval' => $evaluationInterval,
            'nex_evaluation_in_days' => $state->nexEvaluationInterval(),
            'initial_average_bets' => round($initialAverageBets, 2),
            'current_average_bets' => round($currentAverageBets, 2),
            'percentage_diff' => round($this->getChangeInPercentage($currentAverageBets, $initialAverageBets), 2),
        ]);

        return $evaluationResult;
    }

    /**
     * @param int    $userId
     * @param string $startDate
     * @param string $endDate
     *
     * @return int|float
     */
    private function getAverageBetsPerDay(int $userId, string $startDate, string $endDate)
    {
        $result = DB::shSelect(
            $userId,
            'users_daily_game_stats',
            "SELECT
                    AVG(bets_per_day.bets_sum) as average_bets_per_day
                FROM (
                    SELECT
                        SUM(bets) / 100 AS bets_sum
                    FROM
                        users_daily_game_stats
                    WHERE
                        date BETWEEN :start_date AND :end_date
                        AND user_id = :user_id
                    GROUP BY date
                    ORDER BY date DESC
                ) as bets_per_day;",
            [
                'user_id' => $userId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

        return $result[0]->average_bets_per_day ?? 0;
    }
}