<?php
/**
 * Created by PhpStorm.
 * User: iondum
 * Date: 15/11/18
 * Time: 14:08
 */

namespace App\Controllers\Api;

use App\Models\RiskProfileRatingLog;
use App\Repositories\RiskProfileRatingRepository;
use Carbon\Carbon;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class RiskProfileRatingController
{
    protected RiskProfileRatingRepository $riskProfileRatingRepository;

    public function __construct(RiskProfileRatingRepository $riskProfileRatingRepository)
    {
        $this->riskProfileRatingRepository = $riskProfileRatingRepository;
    }

    public function getScore(Application $app, Request $request)
    {
        $score = $this->riskProfileRatingRepository
            ->enableCache(boolval($request->get('cached')))
            ->getScore(
                $request->get('section'),
                $request->get('user_id'),
                $request->get('jurisdiction'),
                $request->get('specific_rpr'),
                true,
                true
            );

        return $app->json(compact('score'));
    }

    public function calculateAll(Application $app, Request $request)
    {
        $score = $this->riskProfileRatingRepository->enableCache(boolval($request->get('cached')));

        return $app->json([
            'aml' => $score->getScore('AML', $request->get('user_id'), $request->get('jurisdiction'), null, true, true,
                true),
            'rg' => $score->getScore('RG', $request->get('user_id'), $request->get('jurisdiction'), null, true, true,
                true),
        ]);
    }

    /**
     * We get the last score stored in the database or we calculate it in case is not present.
     * We could refactor the get score function but since we needed the data to not to change all invocations we do the
     * query here.
     *
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getLastScore(Application $app, Request $request)
    {
        /** @var \Distributed $phive_distributed */
        $phive_distributed = phive('Distributed');

        $score = RiskProfileRatingLog::query()
            ->where('user_id', $request->get('user_id'))
            ->where('rating_type', $request->get('section'))
            ->orderBy('created_at', 'DESC')
            ->limit(1)
            ->first();

        if (!empty($score)) {
            $res = [
                'found' => true,
                'score' => $score->rating,
                'tag' => $score->rating_tag,
                'last_updated' => $score->created_at,
                'brand_name' => $phive_distributed->getSetting('local_brand'),
                'base_uri' => getenv('WEB_BASE_URL'),
                'remote_user_id' => (int)$request->get('user_id'),
            ];
        } else {
            $rating = $this->riskProfileRatingRepository->getScore(
                $request->get('section'),
                $request->get('user_id'),
                $request->get('jurisdiction'),
                null,
                false,
                true,
                true
            );
            $res = [
                'found' => true,
                'score' => $rating['global'],
                'tag' => $rating['tag'],
                'last_updated' => Carbon::now()->toDateTimeString(),
                'brand_name' => $phive_distributed->getSetting('local_brand'),
                'base_uri' => getenv('WEB_BASE_URL'),
                'remote_user_id' => (int)$request->get('user_id'),
            ];
        }

        return $app->json([$res]);
    }
}
