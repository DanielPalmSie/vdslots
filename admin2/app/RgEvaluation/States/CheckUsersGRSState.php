<?php

namespace App\RgEvaluation\States;

use App\Repositories\RiskProfileRatingRepository;
use App\RgEvaluation\ActivityChecks\EvaluationResultInterface;

/**
 * Mostly used in the beginning of each step of RG evaluation process.
 * If user's RG GRS between 'Social Gambler' and 'Low Risk' we quit the RG evaluation process
 * and add a comment to user's profile.
 * If user's RG GRS between 'Medium Risk' and 'High Risk' we continue the RG evaluation process.
 */
class CheckUsersGRSState extends State
{
    protected function check(): EvaluationResultInterface
    {
        $ratingTag = RiskProfileRatingRepository::getLatestGRSTag(
            'RG',
            $this->trigger->getRgEvaluation()->user->id
        );
        $result = $this->getEvaluationResult();
        $result->setEvaluationVariables(['rating_tag' => $ratingTag]);

        return $result->setResult(!$ratingTag || $this->hasReducedGrsRisk($ratingTag));
    }

    protected function onSuccess(): void
    {
        parent::onSuccess();
        $this->onSuccessComment(
            $this->getEvaluationResult()->getEvaluationVariables()
        );
    }

    private function hasReducedGrsRisk(string $ratingTag): bool
    {
        return in_array($ratingTag, [
            RiskProfileRatingRepository::SOCIAL_GAMBLER_RISK_TAG,
            RiskProfileRatingRepository::LOW_RISK_TAG
        ], true);
    }
}