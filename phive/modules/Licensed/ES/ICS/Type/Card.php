<?php
declare(strict_types=1);

namespace ES\ICS\Type;

use ES\ICS\Constants\ICSConstants;

class Card
{
    private array $cards_in_cache = [];
    private string $frequency;
    private bool $enable_cache;
    private $mts;

    private const BLOCK_SIZE = [
        ICSConstants::DAILY_FREQUENCY => 20,
        ICSConstants::MONTHLY_FREQUENCY => 50
    ];
    private const MAX_RETRY = 2;
    private const RETRY_INTERVAL_SECONDS = 3;
    private const CACHE_TIMEOUT_SECONDS = 172800; // 2 days
    private const CARD_TYPES = [
      'P' => 2,
      'D' => 5,
      'C' => 4,
      'X' => 13
    ];
    private const UNKNOWN_CARD_TYPE = 'X';
    private const CACHE_KEY_PREFIX = 'card_type_cache:';

    public function __construct(string $frequency)
    {
        $this->frequency = $frequency;
        $this->mts = phive('Cashier/Mts');
        $this->enable_cache = Phive('Licensed/ES/ES')->getLicSetting('ICS')['enable_card_cache'];
    }

    /**
     * Get type of card from card hashes
     *
     * @param array $card_hashes
     * @return array Card hashes with type based on TipoMedioPago
     */
    public function getCardType(array $card_hashes): array
    {
        $deduplicated_cards = $this->deDuplicateCards($card_hashes);

        // check if cards already available in cache
        if($this->enable_cache) {
            $cards = array_keys(array_diff_key($deduplicated_cards, $this->cards_in_cache));
            foreach ($cards as $card) {
                if($value = phMget(static::CACHE_KEY_PREFIX.$card)) {
                    $this->cards_in_cache[$card] = $value;
                }
            }
        }

        $new_cards = array_keys(array_diff_key($deduplicated_cards, $this->cards_in_cache));

        if(!empty($new_cards)) {
            $result = $this->getTypeFromAPI($new_cards);

            // map the card types to TipoMedioPago
            $mapped_data = $this->mapToTipoMedioPago($result);

            foreach ($mapped_data as $card_hash => $card_type) {
                // save data for each card on redis
                if($this->enable_cache) {
                    phMset(static::CACHE_KEY_PREFIX.$card_hash, $card_type, static::CACHE_TIMEOUT_SECONDS);
                }
                $this->cards_in_cache[$card_hash] = $card_type;
            }
        }

        // return all the cards
        return $this->cards_in_cache;
    }

    /**
     * Returns only valid card hashes
     *
     * @param array $card_data Card hashes
     * @return array Valid card hashes
     */
    private function deDuplicateCards(array $card_data): array
    {
        // Only pick ones that include card hashes /^\d+/
        $cards = preg_grep("/^\d+/", $card_data);

        return array_flip(array_unique($cards));
    }

    /**
     * Get the type of the cards from API
     *
     * @param array $cards Card hashes
     * @return array Card hashes with their type
     */
    private function getTypeFromAPI(array $cards): array
    {
        // divide cards into blocks based on frequency
        $block_size = static::BLOCK_SIZE[$this->frequency];

        $card_chunks = array_chunk($cards, $block_size);

        $result = [];
        // for each block of cards, call mts api
        try {
            foreach ($card_chunks as $card_chunk) {
                $response = $this->mts->getCardType($card_chunk);
                // if the request times out, retry again
                $retries = 1;
                while($response === null && $retries <= static::MAX_RETRY) {
                    sleep(static::RETRY_INTERVAL_SECONDS);
                    $retries++;
                    $response = $this->mts->getCardType($card_chunk);
                }

                if($response === null || (isset($response['success']) && $response['success'] === false)) {
                    continue;
                }

                // if there are unknown cards, retry again with just the unknown cards
                if(in_array(static::UNKNOWN_CARD_TYPE, $response)) {
                    $unknown_card_hashes = array_keys($response, static::UNKNOWN_CARD_TYPE);
                    $retries = 1;

                    while(!empty($unknown_card_hashes) && $retries <= static::MAX_RETRY) {
                        sleep(static::RETRY_INTERVAL_SECONDS);
                        $retries++;
                        $unknown_card_response = $this->mts->getCardType($unknown_card_hashes);
                        if($unknown_card_response === null || (isset($unknown_card_response['success']) && $unknown_card_response['success'] === false)) {
                            continue;
                        }
                        $unknown_card_hashes = array_keys($unknown_card_response, static::UNKNOWN_CARD_TYPE);
                        $response = array_merge($response, $unknown_card_response);
                    }
                }

                $result = array_merge($result, $response);
            }
        } catch (Exception $e) {
            phive('Licensed/ES/ES')->reportLog('Get card type from API',
                $e->getMessage());
        }

        return $result;
    }

    /**
     * Maps card types to TipoMedioPago
     *
     * @param array $cards
     * @return array
     */
    private function mapToTipoMedioPago(array $cards): array
    {
        array_walk($cards, function(&$card_type){
            $card_type = static::CARD_TYPES[$card_type];
        });

        return $cards;
    }
}
