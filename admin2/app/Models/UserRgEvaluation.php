<?php

namespace App\Models;

use App\Extensions\Database\FModel;
use App\Extensions\Database\Eloquent\Builder;

/**
 * @property User $user
 * @property int $user_id
 * @property string $step
 * @property string $trigger_name
 */
class UserRgEvaluation extends FModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users_rg_evaluation';
    const UPDATED_AT = null;

    public const STEP_STARTED = "started";
    public const STEP_SELF_ASSESSMENT = "self-assessment";
    public const STEP_MANUAL_REVIEW = "manual-review";

    public const FIRST_EVALUATION_INTERVAL_IN_DAYS = 3;
    public const SECOND_EVALUATION_INTERVAL_IN_DAYS = 6;

    protected $fillable = [
        'user_id',
        'trigger_name',
        'step',
        'processed',
        'result',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne('App\Models\User', 'id', 'user_id');
    }

    public function scopeNew(Builder $query): Builder
    {
        return $query->where('processed', false);
    }

    public function scopeByStep(Builder $query, string $step): Builder
    {
        return $query->where('step', $step);
    }
}
