<?php
require_once __DIR__ . '/../../api/PhModule.php';
require_once __DIR__ . '/../../vendor/autoload.php';

/**
 *
 */
class SportsbookBonuses extends PhModule {

  private const SPORTSBOOK_GAME_TAG = 'sportsbook';

  public function getBonusProgress($entry, $amount)
  {
    $tag_arr = explode(',', $entry['game_tags']);
    $per_arr = explode(',', $entry['game_percents']);

    $pos = null;

    foreach($tag_arr as $i => $tag) {
      if(str_contains($tag, self::SPORTSBOOK_GAME_TAG)) {
        $pos = $i;
        break;
      }
    }

    if($pos === null) {
      return 0;
    }

    $percent = $per_arr[$pos];

    return $amount * (!isset($percent) ? 1 : $percent);
  }

  /**
   * Gets all sportsbook bonus entries for a specific user
   *
   * @param int $user_id The user id.
   * @param string $status Status clause.
   *
   * @return array The entries.
   */
  function getBonusEntries($user_id, $status = "= 'active'") {
    $str           = "SELECT * FROM bonus_entries WHERE status $status AND bonus_type = 'casinowager' AND user_id = $user_id ORDER BY id ASC";
    $entries = array_filter(
      phive('SQL')->sh($user_id)->loadArray($str),
      function($entry) {
        $game_tags = explode(',', $entry['game_tags']);

        foreach ($game_tags as $game_tag) {
          if(str_contains($game_tag, self::SPORTSBOOK_GAME_TAG)) {
            return true;
          }
        }
        return false;
      }
    );
    return $entries;
  }

  /**
   * Main entrypoint for bonus progression during gameplay.
   * Wrapper function for sportsbook bonus progression
   *
   * @param array $udata User data.
   * @param int $amount The bet amount.
   * @param float $odd The bet odd
   * @param bool $bonus_bet True if bet was made with bonus money, false otherwise. Note that
   * we debit the normal balance first, only when the normal / real balance is down to zero
   * do we start debiting the bonus balance.
   *
   * @return void
   */
  function progressBonuses($udata, $amount, $odd, $bonus_bet) {
    $entries = $this->getBonusEntries($udata['id'], "= 'active'");

    $entries_filtered = array_filter($entries, function($entry) use ($odd) {
      $game_tags = explode(',', $entry['game_tags']);
      $min_bonus_odd = 0;
      foreach ($game_tags as $game_tag) {
        if(str_contains($game_tag, self::SPORTSBOOK_GAME_TAG)) {
          $min_bonus_odd = explode('_', $game_tag)[1] ?? 0;
          break;
        }
      }
      if($odd < $min_bonus_odd) {
        return false;
      }
      return true;
    });

    $bonuses_count = count($entries_filtered);
    if(empty($bonuses_count)) {
      return;
    }
    // We divide the amount by the amount of entries so we don't increase the aggregate total progress too much.
    $amount /= $bonuses_count;
    foreach($entries_filtered as $entry){
      Phive('Bonuses')->progressBonus($udata, $entry, $amount, self::SPORTSBOOK_GAME_TAG, $bonus_bet);
    }
  }
}
