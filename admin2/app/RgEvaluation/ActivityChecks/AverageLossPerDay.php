<?php

namespace App\RgEvaluation\ActivityChecks;

use App\Extensions\Database\FManager as DB;
use Carbon\Carbon;

class AverageLossPerDay extends BaseActivityCheck
{
    public function evaluate(): EvaluationResult
    {
        $now = Carbon::now();
        $state = $this->getTrigger()->getCurrentState();
        $evaluationInterval = $state->currentEvaluationInterval();
        $initialActivityStartOn = $now->copy()->subDays(
            $evaluationInterval + $this->activitiesIntervalInDaysBeforeEvaluationStarted
        )->toDateString();
        $initialActivityEndOn = $postEvaluationStartOn = $now->copy()->subDays($evaluationInterval)->toDateString();
        $postEvaluationEndOn = $now->toDateString();
        $initialAverageLoss = $this->getAverageLossPerDay(
            $this->getUser()->id,
            $initialActivityStartOn,
            $initialActivityEndOn
        );
        $currentAverageLoss = $this->getAverageLossPerDay(
            $this->getUser()->id,
            $postEvaluationStartOn,
            $postEvaluationEndOn
        );
        $evaluationResult = $this->getEvaluationResult();
        $evaluationResult->setResult($currentAverageLoss < $initialAverageLoss);
        $evaluationResult->setEvaluationVariables([
            'evaluation_interval' => $evaluationInterval,
            'nex_evaluation_in_days' => $state->nexEvaluationInterval(),
            'initial_average_loss' => $initialAverageLoss,
            'current_average_loss' => $currentAverageLoss,
            'percentage_diff' => $this->getChangeInPercentage($currentAverageLoss, $initialAverageLoss),
        ]);

        return $evaluationResult;
    }

    private function getAverageLossPerDay(int $userId, string $startDate, string $endDate): int
    {
        $result = DB::shSelect(
            $userId,
            'users_realtime_stats',
            "SELECT AVG(realtime_stats.total_loss) / 100 as average_loss FROM (
                SELECT
                IFNULL(SUM(bets - wins - frb_wins - jp_contrib - rewards + fails), 0) as total_loss
                FROM
                    users_realtime_stats
                WHERE
                    user_id = :user_id
                AND date BETWEEN :start_date AND :end_date
                GROUP BY `date`
            ) as realtime_stats",
            [
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        return $result[0]->average_loss ?? 0;
    }
}