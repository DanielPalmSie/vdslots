<?php
require_once __DIR__.'/MgGameChooseBoxBase.php';
class MgPlayBoxBase extends MgGameChooseBoxBase{

    function init(){
        $this->aSubtopMenuItems = phive('Menuer')->forRender('sub-top');
        $this->game = phive('MicroGames')->getByGameUrl($_GET['arg0']);

        $this->handlePost(array('alink', 'email_id', 'email_page'));
        if(!empty($this->game)){
            $this->real_game = phive('MicroGames')->overrideGame(null, $this->game);
            $this->real_game['rtp'] = $this->real_game['payout_percent'] * 100;

            lightRedir("/play/".$this->game['game_url']);
            //phive('Pager')->setMetaDescription( empty($this->game['meta_descr']) ? phive()->decHtml($this->game['game_name']).' - '.phive()->getSetting('domain') : $this->game['meta_descr']);
            phive('Pager')->setMetaDescription(rep(tAssoc('game.description', $this->game)));
            phive('Pager')->setTitle(rep(tAssoc('game.title', $this->game)));

            $this->games = phive('MicroGames')->getTaggedBy($this->game['tag']);

            $this->progressive = $this->game['jackpot_contrib'] == 0 ? false : true;
        }

        $this->mg = phive('MicroGames');

        $this->ellipsis_len = 20;

        $this->base_localized_alias = 'gameinfo.'.str_replace('@', '', $this->game['game_id']);
    }

    function is404($args){
        if(empty($this->game))
            return true;
        if(count($args) > 1)
            return true;
        return false;
    }

    function printJsAndIncludes($carousel_skin = 'videoslots', $change_bkg = true){ ?>

    <script>
     var curGame = <?php echo json_encode($this->game) ?>;
    </script>

    <?php $this->includes($carousel_skin) ?>
    <script>

     $(document).ready(function() {

         <?php if(!empty($this->game['bkg_pic']) && $change_bkg): ?>
         $('#wrapper').css('background-image', "url('<?php fupUri('backgrounds/'.$this->game['bkg_pic']) ?>')");
         <?php if(!empty($this->game['stretch_bkg'])): ?>
         $('#wrapper').addClass('stretch');
         <?php endif; ?>
         $('body').css('background', "#000000");
         <?php endif ?>

         $(".launchGame").click(function(){ playGameDepositCheckBonus(curGame.game_id) });

         $("#freeplay").click(function(){ playGameDepositCheckBonus(curGame.game_id, undefined, undefined, true) });

     });
    </script>
<?php }

function printStyle(){
    $this->fancyPlaycss();
}

function printHTML(){
?>
    <div id="game_page" class="frame-block2">
        <div class="frame-holder2">
            <?php if(!empty($this->game)): ?>
                <?php $this->printJsAndIncludes() ?>
                <?php //$this->printStyle() ?>
                <?php $this->searchJs() ?>
                <div class="game-bkg<?php echo (($this->game['stretch_bkg'] != 0) ? ' stretch': '' ); ?>">
                    <div class="gch-left">
                        <?php if(isLogged()): ?>
                        <a id="big-deposit-btn" class="medium-bigbutton" onclick="<?php echo depGo() ?>">
                            <?php echo t('deposit') ?>
                        </a>
                        <?php else: ?>
                        <a id="signup-button1" class="medium-bigbutton" href="<?php echo phive('Localizer')->langLink('', '/?signup=true') ?>">
                            <?php echo t('register') ?>
                        </a>
                        <?php endif; ?>

                        <?php if (!isLogged() && (float)$this->game['jackpot_contrib'] > 0 && $this->game['network'] == 'microgaming'): ?>
                        <div id="progplay-info" style="margin-left: 5px;"><?php et('no.progplayinfo.html') ?></div>
                        <?php else: ?>
                        <div id="playframe" class="iframe medium-bigbutton launchGame" href="">
                            <?php echo t('play.game') ?>
                        </div>
                        <?php endif; ?>

                        <?php lic('freePlayButton'); ?>

                        <br/>
                        <?php $this->search() ?>
                    </div>

                    <div class="mg-game-info">
                        <div class="iframe launchGame" href="">
                            <img src="<?php fupUri("screenshots/{$this->game['game_id']}_big.jpg") ?>" />
                        </div>
                    </div>

                    <div class="game-description">
                        <h1><?php echo tAssoc($this->base_localized_alias.'.header', $this->real_game) ?></h1>
                        <?php echo tAssoc($this->base_localized_alias.'.html', $this->real_game, null, true) ?>
                    </div>


                </div>
                
                <ul id="search-default" style="display: none;">
                    <?php $this->printMainCats() ?>
                </ul>
            <?php else: ?>
                <?php echo t('no.game.html') ?>
            <?php endif ?>
        </div>
        <?php //topPlayBar($this->game) ?>
        <?php noCashBox() ?>
    </div>
<?php
  }

  function printExtra(){ ?>
    <p>
      <label for="alink">Archive link (ex: news/archive or archive ): </label>
      <input type="text" name="alink" value="<?= $this->alink ?>" />
    </p>
    <p>
      <label for="email_id">Id of email form box to use: </label>
      <input type="text" name="email_id" value="<?= $this->email_id ?>" />
    </p>
    <p>
      <label for="email_page">Name of page the form is on: </label>
      <input type="text" name="email_page" value="<?= $this->email_page ?>" />
    </p>
  <?php }
}
