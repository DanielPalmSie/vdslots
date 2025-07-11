<?php

namespace App\RgEvaluation\ActivityChecks;

use App\Extensions\Database\FManager as DB;
use Carbon\Carbon;

class AverageDepositTransactionPerDay extends BaseActivityCheck
{
    public function evaluate(): EvaluationResult
    {
        $now = Carbon::now();
        $state = $this->getTrigger()->getCurrentState();
        $evaluationInterval = $state->currentEvaluationInterval(false);
        $evaluationStartOn = $now->copy()->subDays($evaluationInterval)->toDateString();
        $evaluationEndOn = $now->toDateString();

        $lifeTimeDeposits = $this->getLifeTimeDeposits(
            $this->getUser()->id,
            $evaluationStartOn,
            $evaluationEndOn
        );
        $lifeTimeDeposits = array_map(function ($item) {return (array)$item;}, $lifeTimeDeposits);
        $onEvaluationStartedDeposits = array_shift($lifeTimeDeposits);
        $onEvaluationStartedDepositsTransactionsCount = (int)$onEvaluationStartedDeposits['num_of_deposits'];
        $postEvaluationLifeTimeDepositsCount = array_sum(array_column($lifeTimeDeposits, 'num_of_deposits'));
        $postEvaluationLifeTimeActiveDaysCount = count($lifeTimeDeposits);
        $afterEvaluationAvgDepositTransactionsCount = empty($postEvaluationLifeTimeDepositsCount) ? 0 : round($postEvaluationLifeTimeDepositsCount / $postEvaluationLifeTimeActiveDaysCount, 2);
        $evaluationResult = $this->getEvaluationResult();
        $evaluationResult->setResult($afterEvaluationAvgDepositTransactionsCount < $onEvaluationStartedDepositsTransactionsCount);
        $evaluationResult->setEvaluationVariables(
            [
                "evaluation_interval" => $evaluationInterval,
                "nex_evaluation_in_days" => $state->nexEvaluationInterval(),
                "number_of_deposit_transactions_before_evaluation" => $onEvaluationStartedDepositsTransactionsCount,
                "number_of_deposit_transactions_after_evaluation" => $afterEvaluationAvgDepositTransactionsCount,
                "percentage_diff" => round($this->getChangeInPercentage($afterEvaluationAvgDepositTransactionsCount, $onEvaluationStartedDepositsTransactionsCount), 2)
            ]
        );

        return $evaluationResult;
    }

    /**
     * @param int    $userId
     * @param string $fromDate
     * @param string $toDate
     *
     * @return array
     */
    private function getLifeTimeDeposits(int $userId, string $fromDate, string $toDate): array
    {
        $sql = "
            SELECT
                DATE(timestamp) as date,
                COUNT(id) as num_of_deposits
            FROM
                deposits
            WHERE
                user_id= {$userId}
                AND date(timestamp) BETWEEN '{$fromDate}' AND '{$toDate}'
            GROUP BY date(timestamp)
            ORDER BY timestamp ASC;";

        return DB::shSelect(
            $userId,
            'deposits',
            $sql
        );
    }
}