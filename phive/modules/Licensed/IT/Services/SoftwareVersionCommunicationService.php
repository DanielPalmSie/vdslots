<?php
namespace IT\Services;

use IT;
use IT\Pgda\Codes\ReturnCode;
use Exception;
use IT\Services\Traits\InteractWithMail;

/**
 * Class SoftwareVersionCommunicationService
 * @package IT\Services
 */
class SoftwareVersionCommunicationService
{
    use InteractWithMail;
    
    /**
     * Config tag name
     */
    const SOFTWARE_VERSION_SETTING_TAG = 'software_version_history';

    /**
     * Game type
     */
    const GAME_TYPE = 2;

    /**
     * @var array
     */
    private array $history = [];

    /**
     * @var array
     */
    private array $history_changes = [];

    /**
     * @var IT
     */
    private IT $it;

    /**
     * SoftwareVersionComm
     * unicationService constructor.
     * @param IT $it
     */
    public function __construct(IT $it)
    {
        $this->it = $it;
    }

    /**
     * Get the software version history from license_configs
     * @return array
     */
    public function getSoftwareVersionHistory(): array
    {
        if (empty($this->history)) {
            $config_tag = self::SOFTWARE_VERSION_SETTING_TAG;
            $this->history = $this->it->getByTags($config_tag, true)[$config_tag] ?? [];
        }
        return $this->history;
    }

    /**
     * Get all italian games from db
     * @param array $where
     * @return array
     * @throws Exception
     */
    public function getAllItalianGameCountryVersions(array $where = []): array
    {
        $where['country'] = 'IT';

        $games = phive('SQL')->arrayWhere('game_country_versions', $where);

        // returning only the games that has valid data
        return array_filter($games, function ($game) {
            return !empty($game['game_version'])
                && !empty($game['rng_version'])
                && !empty($game['game_certificate_ref'])
                && !empty($game['game_regulatory_code']);
        });
    }

    /**
     * Check if the game data match
     * @param array $version
     * @param array $history
     * @return bool
     */
    private function match(array $version, array $history) {
        return $version['game_id'] == $history['game_id']
            && $version['game_certificate_ref'] == $history['game_certificate_ref'];
    }

    /**
     * Check if some game data has changes and return them
     * @return array
     */
    public function getChanges()
    {
        $game_country_versions = $this->getAllItalianGameCountryVersions();
        $game_country_versions_history = $this->getSoftwareVersionHistory();
        $changes = [];
        foreach ($game_country_versions as $game_country_version) {
            $found = false;
            if (
                $game_country_versions_history[$game_country_version['id']]
                && $this->match($game_country_version, $game_country_versions_history[$game_country_version['id']])
            ) {
                unset($game_country_versions_history[$game_country_version['id']]);
                continue;
            }
            foreach ($game_country_versions_history as $id => $history) {
                if ($this->match($game_country_version, $history)) {
                    unset($game_country_versions_history[$id]);
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $changes[] = $game_country_version;
            }
        }

        return $changes;
    }

    /**
     * Save installed software version history
     * @param $history_changes
     */
    private function saveNewHistory(array $history_changes)
    {
        foreach ($history_changes as $pk_game_id => $game) {
            $this->it->setConfigValue(self::SOFTWARE_VERSION_SETTING_TAG, $pk_game_id, json_encode($game));
        }
    }

    /**
     * Send new games and game changes to sogei
     * @throws \Exception
     */
    public function reportGameChanges()
    {
        $new_games = $this->getChanges();
        $error = [];
        foreach ($new_games as $game) {
            $result = $this->it->installedSoftwareVersionCommunication($this->getPayload($game));
            if ($result['code'] == ReturnCode::SUCCESS_CODE) {
                $this->history_changes[$game['id']] = $game;
                continue;
            }
            $error[$game['game_id']] = $result;
        }

        if (!empty($error)) {
            error_log("ERROR-". date('Y-m-d H:i:s') . print_r($error, true));
            $this->notify('ADM Error on message 831', compact('error'));
        }

        $this->saveNewHistory($this->history_changes);
    }

    /**
     * Generate game payload to send to sogei
     * @param array $game
     * @return array
     */
    private function getPayload(array $game): array
    {
        return [
            'game_code' => 0,
            'game_type' => 0,
            'cod_element_type' => self::GAME_TYPE,
            'cod_element' => $game['game_id'],
            'prog_cert_version' => $game['game_version'],
            'prog_sub_cert_version' => empty($game['rng_version']) ? 0 : $game['rng_version'],
            'software_modules'=> [
                [
                    'name_critical_module' => $game['game_id'] . $game['game_version'],
                    'hash_critical_module' => $game['game_certificate_ref']
                ]
            ]
        ];
    }
}