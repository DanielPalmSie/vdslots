<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.16.
 * Time: 16:36
 */

namespace App\Models;

use App\Extensions\Database\FModel;
use App\Helpers\DataFormatHelper;

class RgLimits extends FModel
{
    protected $table ='rg_limits';

    public $timestamps = true;

    protected $guarded = ['id'];

    private $remaining_value = false;

    public function getRemainingAttribute() {
        if ($this->remaining_value === false) {
            $this->remaining_value = phive('DBUserHandler/RgLimits')->getRemaining($this->toArray());
        }
        return $this->remaining_value;
    }

    public function getCurLimAttribute($el) {
        return $el === -1 ? 0 : $el;
    }

    public function getTitleAttribute() {
        return DataFormatHelper::getLimitsNames($this->getAttribute('type'));
    }

    public function getRemainingUserFriendly() {
        return DataFormatHelper::nf($this->remaining);
    }

    public function getForcedUntilAttribute($date) {
        if (!empty($date) && $date != '0000-00-00 00:00:00') {
            return $date;
        }
        return null;
    }

    public function getChangesAtAttribute($date) {
        if (!empty($date) && $date != '0000-00-00 00:00:00') {
            return $date;
        }
        return null;
    }

    public function getResetsAtAttribute($date) {
        if (!empty($date) && $date != '0000-00-00 00:00:00') {
            return $date;
        }
        return null;
    }

}
