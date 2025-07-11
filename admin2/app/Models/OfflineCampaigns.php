<?php
/**
 * Created by PhpStorm.
 * User: iondum
 * Date: 15/01/18
 * Time: 10:53
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class OfflineCampaigns extends FModel
{
    protected $table = 'offline_campaigns';

    public $timestamps = false;

    protected $guarded = ['id'];

    public function namedSearch()
    {
        return $this->belongsTo(NamedSearch::class, 'named_search', 'id');
    }

    public function bonusTemplate() {
        return $this->belongsTo(BonusTypeTemplate::class, 'template_id', 'id');
    }

    public function voucherTemplate()
    {
        return $this->belongsTo(VoucherTemplate::class, 'template_id', 'id');
    }

    public function hasPromotion() {
        return $this->type !== 'no_promotion';
    }

    public function showTemplateName() {
        if (!$this->hasPromotion()) {
            return 'No promotion';
        }

        $promotion = $this->{"{$this->type}Template"};

        return empty($promotion->template_name)
            ? "Template #{$promotion->id}"
            : $promotion->template_name;
    }
}
