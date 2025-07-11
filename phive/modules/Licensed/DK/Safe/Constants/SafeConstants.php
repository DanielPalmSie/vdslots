<?php


class SafeConstants
{
    /**
     * Xml service type for making a close token request
     */
    const TOKEN_REQUEST_URL_CONFIG_NAME = 'TamperTokenAnvendService';

    /**
     * Every day report key
     */
    const END_OF_DAY = 'EndOfDay';

    /**
     * Normal every 5 min report key
     */
    const KASINO_SPIL = 'KasinoSpil';

    /**
     * Rollback (Bet or Win cancel) key
     */
    const KASINO_SPIL_CANCEL = 'KasinoSpil_Cancel';

    /**
     * Correct data after rollback key.
     * After a rollback we have to create a file with the left amount of the game session.
     */
    const KASINO_SPIL_AFTER_CANCEL = 'KasinoSpil_Cancel_After';

    /**
     * Key when we get a new token.
     */
    const NEW_TOKEN = 'NEW_TOKEN';

    /**
     * Evolution game provider KasinoSpil report key
     */
    const EVOLUTION_KASINO_SPIL = 'KasinoSpil_evolution';

    /**
     * Evolution game provider EndOfDay report key
     */
    const EVOLUTION_END_OF_DAY = 'EndOfDay_evolution';

    /**
     * The ServiceId value stored in log_info column for test tamper tokens.
     */
    const TEST_TAMPER_TOKEN_SERVICE = 'RandomTamperTokenTestService';

    const KASINO_SPIL_TYPES = [
        self::KASINO_SPIL_CANCEL,
        self::KASINO_SPIL_AFTER_CANCEL,
        self::EVOLUTION_KASINO_SPIL,
    ];

    /**
     * Reports that have no files should be configured here
     */
    const NO_FILE_REPORTS = [
        self::NEW_TOKEN,
        self::EVOLUTION_END_OF_DAY
    ];

    /**
     * If report is KasinoSpil, used for cancellations or custom reports.
     * @param $type
     * @return bool
     */
    public static function isKasinoSpil($type)
    {
        if (in_array($type, self::KASINO_SPIL_TYPES)) {
            return true;
        }

        return false;
    }

    /**
     * New and close token key to be inserted on misc_cache
     */
    const NEW_TOKEN_KEY = 'SAFE_NEW_TOKEN_REQUEST';
    const CLOSE_TOKEN_KEY = 'SAFE_CLOSE_TOKEN_REQUEST';
    const UNUSED_TOKEN_KEY = 'SAFE_CLOSE_UNUSED_TOKEN_REQUEST';

    /**
     * @param $type
     * @param array $providers
     * @return array
     */
    public static function generateProvidersReportType($type, $providers = [])
    {
        if (! in_array($type, [self::KASINO_SPIL, self::END_OF_DAY])) {
            return [];
        }

        $keys = [];
        foreach ($providers as $provider) {
            $keys[] = $type . "_" . $provider;
        }

        return $keys;
    }
}
