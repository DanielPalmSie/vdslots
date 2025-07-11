<?php
/**
 * Created by PhpStorm.
 * User: iondum
 * Date: 19/06/18
 * Time: 18:28
 */

namespace App\Traits;

trait TemplatesConsentTrait {
    public static $consent_map = [
        'NONE' => 'none',
        'PROMO' => 'promo',
        'STATUS' => 'status',
        'NEW' => 'new',
    ];

    public function requiresConsent() {
        return !in_array($this->consent, ['', self::$consent_map['NONE']]);
    }

    public function getConsent() {
        return $this->consent == '' ? 'none' : $this->consent;
    }

    public function getRequiredConsent() {
        return $this->consent;
    }

    public static function getConsentList() {
        return self::$consent_map;
    }

    public static function getConsentName($k) {
        $consent_map = self::$consent_map;
        $consent_map = array_flip($consent_map);

        return $consent_map[$k == '' ? 'none' : $k];
    }

}