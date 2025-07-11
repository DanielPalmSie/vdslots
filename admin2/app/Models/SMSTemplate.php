<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2016.10.05.
 * Time: 12:17
 */

namespace App\Models;
use App\Traits\TemplatesConsentTrait;
use App\Extensions\Database\FModel;

class SMSTemplate extends FModel
{
    use TemplatesConsentTrait;

    protected $table = 'sms_templates';

    protected $guarded = ['id'];

    public function schedules()
    {
        return $this->hasMany(MessagingCampaignTemplates::class, 'template_id')->where('template_type', MessagingCampaignTemplates::TYPE_SMS);
    }

    public function getTemplateName()
    {
        return !empty($this->template_name) ? $this->template_name : $this->template;
    }
}