<?php

namespace App\Models;

use App\Extensions\Database\FModel;
use App\Helpers\Common;

class MessagingCampaign extends FModel
{
    protected $table = 'messaging_campaigns';

    protected $guarded = ['id'];

    public $virtual = false;

    const STATUS_PLACED = 0;

    const STATUS_SENT = 1;

    const STATUS_INVALID = 2;

    /**
     * Checking here if it is a virtual campaign so it does not go to the database by mistake so
     *
     * @return bool
     */
    public function validate()
    {
        if ($this->virtual === true) {
            return false;
        }
        return parent::validate();
    }

    /**
     * @param null $error
     */
    public function invalidate($error = null)
    {
        Common::dumpTbl('messaging-failed-campaign', "Campaign #{$this->id} error: " . $error);
        $this->stats = $error;
        $this->status = self::STATUS_INVALID;
        $this->save();
    }

    public function getStatusName()
    {
        $status_map = [
            self::STATUS_PLACED => 'Placed',
            self::STATUS_SENT => 'Sent',
            self::STATUS_INVALID => 'Invalid'
        ];

        return isset($status_map[$this->status]) ? $status_map[$this->status] : $this->status;
    }

    public function bonusType()
    {
        return $this->hasOne(BonusType::class, 'id', 'bonus_id');
    }

    public function campaignTemplate()
    {
        return $this->hasOne(MessagingCampaignTemplates::class, 'id', 'campaign_template_id');
    }

}