<?php

declare(strict_types=1);

namespace ES\ICS\Reports;

use Exception;
use Carbon\Carbon;

class Info
{
    public const VERSIONS = [
        2 => [
            'endDateTime' => '2025-03-21 23:59:59',
            'xmlVersion' => '2.15'
        ],
        3 => [
            'xmlVersion' => '3.3'
        ]
    ];

    /** @throws Exception */
    public static function getVersion(): int
    {
        $currentDate = Carbon::now()->toDateTimeString();

        foreach (self::VERSIONS as $version => $versionInfo)
        {
            if (empty($versionInfo['endDateTime']) || $currentDate <= $versionInfo['endDateTime']) {
                return $version;
            }
        }

        throw new Exception('Could not find a proper report version for current date: '.$currentDate);
    }

    /** @throws Exception */
    public static function getXmlVersion(): string
    {
        $currentDate = Carbon::now()->toDateTimeString();

        foreach (self::VERSIONS as $versionInfo)
        {
            if (empty($versionInfo['endDateTime']) || $currentDate <= $versionInfo['endDateTime']) {
                return $versionInfo['xmlVersion'];
            }
        }

        throw new Exception('Could not find a proper report version for current date: '.$currentDate);
    }

    public static function getDailyReportClasses(): array
    {
        $version = self::getVersion();
        if ($version === 2) {
            // v2
            return [RUD::class, RUT::class, CJD::class, CJT::class];
        } else {
            // v3
            return [RUD::class, CJD::class, CJT::class];
        }
    }

    public static function getMonthlyReportClasses(): array
    {
        return [RUD::class, RUT::class, CJD::class, CJT::class, OPT::class];
    }

    public static function getRealTimeReportClasses(): array
    {
        $version = self::getVersion();
        if ($version === 2) {
            // v2
            return [JUD::class, JUT::class];
        } else {
            // v3
            return [JUC::class];
        }
    }

    public static function getUsersSessionsDates(Carbon $date, string $country): array
    {
        $version = self::getVersion();

        if ($version === 2) {
            $className = v2\JUT::class;
        } else {
            $className = v3\JUC::class;
        }

        return $className::getUsersSessionsDates($date, $country);
    }
}
