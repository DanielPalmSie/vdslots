<?php

namespace App\Models;

use App\Extensions\Database\FModel;
use App\Traits\TemplatesConsentTrait;

class EmailTemplate extends FModel
{
    use TemplatesConsentTrait;

    protected $table = 'email_templates';

    protected $guarded = ['id'];

    public function schedules()
    {
        return $this->hasMany(MessagingCampaignTemplates::class, 'template_id')->where('template_type', MessagingCampaignTemplates::TYPE_EMAIL);
    }

    public function getTemplateName()
    {
        return $this->subject;
    }
}