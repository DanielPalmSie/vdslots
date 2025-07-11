<?php

namespace App\RgEvaluation\ActivityChecks;

use App\Extensions\Database\FManager as DB;
use Carbon\Carbon;

class AverageDepositAmountPerTransactionPerDay extends BaseActivityCheck
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
        $onEvaluationStartedDepositsCount = (int)$onEvaluationStartedDeposits['num_of_deposits'];
        $onEvaluationStartedDepositsAmount = $onEvaluationStartedDeposits['amount'];
        $avgDepositAmountOnEvaluationStarted = round($onEvaluationStartedDepositsAmount / $onEvaluationStartedDepositsCount, 2);

        $postEvaluationLifeTimeDepositsCount = array_sum(array_column($lifeTimeDeposits, 'num_of_deposits'));
        $postEvaluationLifeTimeDepositsAmount = array_sum(array_column($lifeTimeDeposits, 'amount'));
        $avgDepositAmountOnEvaluationEnded = empty($postEvaluationLifeTimeDepositsCount) ? 0 : round($postEvaluationLifeTimeDepositsAmount / $postEvaluationLifeTimeDepositsCount, 2);

        $evaluationResult = $this->getEvaluationResult();
        $evaluationResult->setResult($avgDepositAmountOnEvaluationEnded < $avgDepositAmountOnEvaluationStarted);
        $evaluationResult->setEvaluationVariables(
            [
                "evaluation_interval" => $evaluationInterval,
                "nex_evaluation_in_days" => $state->nexEvaluationInterval(),
                "avg_deposit_amount_per_transaction_before" => $avgDepositAmountOnEvaluationStarted . ' ' . $this->getUser()->currency,
                "avg_deposit_amount_per_transaction_after" => $avgDepositAmountOnEvaluationEnded . ' ' . $this->getUser()->currency,
                "percentage_diff" => round($this->getChangeInPercentage($avgDepositAmountOnEvaluationEnded, $avgDepositAmountOnEvaluationStarted), 2)
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
                COUNT(id) as num_of_deposits,
                SUM(amount) / 100 as amount
            FROM
                deposits
            WHERE
                user_id = {$userId}
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