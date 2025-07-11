<div class="vs-mobile__bos-upcoming">
    <div class="vs-mobile__bos-upcoming-logo-container">
        <img
          id="link_to_mobile_bos__battle-strip"
          class="vs-mobile__bos-upcoming-logo"
          src="<?php fupUri('vs-battleofslots-logo.png'); ?>"
          width="42px"
          height="16px"
        >
    </div>

    <div class="vs-mobile__bos-upcoming-battles">
        <?php
            $tournaments = phive('Tournament')->getTournamentsForActivityFeedBox(2);
        ?>

        <?php foreach ($tournaments as $tournament): ?>

        <div class="vs-mobile__bos-upcoming-battle">
          <div class="vs-mobile__bos-upcoming-battle-thumbnail-container">
            <img
              class="vs-mobile__bos-upcoming-battle-thumbnail"
              src="<?php fupUri('thumbs/' . $tournament['game_id'] . '_c.jpg'); ?>"
            >
          </div>
          <div class="vs-mobile__bos-upcoming-battle-name">
              <?php echo $tournament['tournament_name']; ?>
          </div>
            <div class="vs-mobile__bos-upcoming-battle-button-container"
                 onclick="goToMobileBattleOfSlots('<?php echo phive('Tournament')->getSetting('mobile_bos_url') . '/tournament/' . $tournament['id']; ?>')">
                <div class="vs-mobile__bos-upcoming-battle-button">
                    <div class="button-arrow">
                </div>
            </div>
          </div>
        </div>

        <?php endforeach; ?>

    </div>
  </div>
