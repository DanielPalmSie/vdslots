<?php
require_once __DIR__.'/../../phive/modules/BoxHandler/boxes/diamondbet/MgGameChooseBoxBase.php';
require_once __DIR__.'/NewsListBox.php';
class MgGameChooseBox extends MgGameChooseBoxBase{

  function init(){
    $this->nlist = new NewsListBox();
    parent::init();
  }

  function gameHover($g){ ?>
    <div class="game-over">
      <?php btnSmall(t('play.now'), '', "playGameDepositCheckBonus('{$g['game_id']}')") ?>
    </div>
    <?php
  }
  public function getFavouriteGames() {
      if(!isLogged()) {
          echo json_encode([]);
      }
      $gameIds = $this->mg->favIds($_SESSION['mg_id']);
      echo json_encode($gameIds);
  }

}
