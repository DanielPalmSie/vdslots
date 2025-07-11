<?php

namespace App\Helpers;

use App\Models\MessagingCampaignTemplates as MCT;

class CampaignHelper
{
    protected $type;

    public function __construct($type)
    {
        if (in_array($type, MCT::$supported_types)) {
            $this->type = $type;
        } else {
            throw new \Exception("Campaign type not supported.", 404);
        }
    }

    public static function getNameFromType($type)
    {
        $instance = new self($type);
        return $instance->getName(true);
    }

    public static function validateType($type)
    {
        new self($type);
    }

    public function getRawType()
    {
        return $this->type;
    }

    public function isSMS()
    {
        return $this->type == MCT::TYPE_SMS;
    }

    public function isEmail()
    {
        return $this->type == MCT::TYPE_EMAIL;
    }

    public function getName($lower_case = false)
    {
        $map = [
            MCT::TYPE_SMS => 'SMS',
            MCT::TYPE_EMAIL => 'Email',
        ];

        return isset($map[$this->type]) ? $lower_case ? strtolower($map[$this->type]) : $map[$this->type] : '';
    }

}
