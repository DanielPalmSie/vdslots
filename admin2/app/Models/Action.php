<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.16.
 * Time: 16:36
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class Action extends FModel
{
    const TAG_CALL_TO_USER = "call-to-user";
    const TAG_EMAIL_TO_USER = "email-to-user";
    const TAG_FORCE_DEPOSIT_LIMIT = "force_deposit_limit";
    const TAG_FORCE_SELF_ASSESSMENT_TEST = "force_self_assessment_test";
    const TAG_ASK_PLAY_TOO_LONG = "ask_play_too_long";
    const TAG_FORCE_LOGIN_LIMIT = "force_login_limit";
    const TAG_ASK_BET_TOO_HIGH = "ask_bet_too_high";
    const TAG_FORCE_MAX_BET_PROTECTION = "force_max_bet_protection";
    const TAG_ASK_GAMBLE_TOO_MUCH = "ask_gamble_too_much";
    const TAG_REVIEWED = "user-profile-reviewed";
    const TAG_FORCE_SELF_EXCLUSION = "force_self_exclusion";

    const TAG_DAILY = "daily";

    const TAG_FOLLOW_UP = "follow_up";

    const TAG_ESCALATION = "escalation";

    // TODO move this NAME_MAP into DataFormatHelper
    const NAME_MAP = [
        self::TAG_CALL_TO_USER => 'Phoned',
        self::TAG_EMAIL_TO_USER => 'Emailed',

        self::TAG_FORCE_SELF_EXCLUSION => 'Forced Self-exclusion',
        self::TAG_FORCE_SELF_ASSESSMENT_TEST => 'Forced Self-assessment',
        self::TAG_FORCE_DEPOSIT_LIMIT => 'Forced Deposit limit',

        self::TAG_ASK_PLAY_TOO_LONG => 'Asked Play too long',
        self::TAG_FORCE_LOGIN_LIMIT => 'Forced Login limit',

        self::TAG_ASK_BET_TOO_HIGH => 'Asked bet too high',
        self::TAG_FORCE_MAX_BET_PROTECTION => 'Forced Max bet protection',

        self::TAG_ASK_GAMBLE_TOO_MUCH => 'Asked gamble too much',
    ];

    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $timestamps = false;

    protected $guarded = ['id'];

    public function actorUser()
    {
        return $this->belongsTo('App\Models\User', 'actor', 'id');
    }

    public function targetUser()
    {
        return $this->belongsTo('App\Models\User', 'actor', 'id');
    }

}
