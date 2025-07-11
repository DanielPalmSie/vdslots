<?php

namespace App\Models;

use App\Extensions\Database\FModel;
use App\Traits\GlobalRatingScoreTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Videoslots\HistoryMessages\UserRiskScoreUpdateHistoryMessage;

class RiskProfileRatingLog extends FModel
{
    use GlobalRatingScoreTrait;

    const TYPE_AML = "AML";
    const TYPE_RG = "RG";

    public $timestamps = false;
    protected $table = 'risk_profile_rating_log';
    protected $guarded = ['id'];
    protected $fillable = ['user_id', 'created_at', 'rating_type', 'rating_tag', 'rating', 'influenced_by'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setAttribute('created_at', Carbon::now());
    }

    /**
     * @param $app
     * @param $user_id
     * @param $jurisdiction
     * @param $rating_type
     * @param $rating
     * @return RiskProfileRatingLog|Builder|Model
     * @throws \Exception
     */
    public static function logRating($app, $user_id, $jurisdiction, $rating_type, $rating, $influenced_by)
    {
        $rating_type = strtoupper($rating_type);
        $rating = intval($rating);

        $user_score = self::sh($user_id)
            ->where('user_id', '=',  $user_id)
            ->where('rating_type', '=', $rating_type)
            ->orderByDesc('created_at')
            ->first();

        if (!empty($user_score) && $user_score->getAttribute('rating') === $rating) {
            return $user_score;
        }

        /** @uses Licensed::addRecordToHistory() */
        lic('addRecordToHistory', [
            'user_risk_score_update',
            new UserRiskScoreUpdateHistoryMessage(
                [
                    'user_id'     => (int) $user_id,
                    'rating_type' => $rating_type,
                    'old_score'   => !empty($user_score) ? (int) $user_score->getAttribute('rating') : 0,
                    'new_score'   => $rating,
                    'event_timestamp' => Carbon::now()->timestamp,
                ]
            ),
        ], $user_id);

        $rating_tag = static::getGRSRatingTag($app, $rating, $jurisdiction, $rating_type, true);
        $user_score = new self;
        $user_score->fill(compact('user_id', 'rating_type', 'rating_tag', 'rating', 'influenced_by'));
        $user_score->save();

        return $user_score;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
