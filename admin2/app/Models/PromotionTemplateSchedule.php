<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2016.10.07.
 * Time: 10:50
 */

namespace App\Models;

use App\Models\NamedSearch;
use App\Models\PromotionTemplate;
use App\Models\BonusTypeTemplate;
use App\Models\VoucherTemplate;
use App\Extensions\Database\FModel;
use DateTime;

class PromotionTemplateSchedule extends FModel
{
    protected $table = 'promotion_templates_schedules';

    protected $guarded = ['id'];

    public $fillable = [
        'promotion_template_id',
        'promotion_template_id',
        'voucher_template_id',
        'bonus_template_id',
        'type',
        'send_date',
        'named_search_id'
    ];

    protected function rules()
    {
        return [
            'default' => [
                'required' => [['promotion_template_id'],['send_date'],['named_search_id'], ['type']],
                'dateAfter' => [['send_date', new DateTime()]]
            ]
        ];
    }

    public function namedSearch()
    {
        return $this->hasOne(NamedSearch::class, 'id', 'named_search_id');
    }

    public function promotionTemplate()
    {
        return $this->hasOne(PromotionTemplate::class, 'id', 'promotion_template_id');
    }

    public function bonusTypeTemplate()
    {
        return $this->hasOne(BonusTypeTemplate::class, 'id', 'bonus_template_id');
    }

    public function voucherTemplate()
    {
        return $this->hasOne(VoucherTemplate::class, 'id', 'voucher_template_id');
    }
}