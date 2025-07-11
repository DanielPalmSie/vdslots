<?php
require_once __DIR__ . '/MgMobileGameChooseBoxBase.php';

class MgMobilePlayBoxBase extends MgMobileGameChooseBoxBase
{

    function init()
    {
        $this->mg = phive('MicroGames');

        $this->aSubtopMenuItems = phive('Menuer')->forRender('sub-top');
        $this->game = $this->mg->getByGameUrl($_GET['arg0'], 1);

        $this->mobile_game = $this->mg->getMobileGame($this->game);
        $this->desktop_game = $this->mg->getDesktopGame($this->game);

        $this->real_game = $this->mg->overrideGame(null, $this->game);
        $this->real_game['rtp'] = $this->real_game['payout_percent'] * 100;

        $this->handlePost(array('alink', 'email_id', 'email_page'));

        if (!empty($this->mobile_game)) {

            $game_url = trim($this->mobile_game['game_url']);
            if (empty($game_url)) {
                $game_url = trim($this->desktop_game['game_url']);
            }

            lightRedir("/mobile/play/" . $game_url);
            phive('Pager')->setMetaDescription(rep(tAssoc('game.description', $this->mobile_game)));
            phive('Pager')->setTitle(rep(tAssoc('game.title', $this->mobile_game)));
        }
    }

    function is404($args)
    {
        if (empty($this->mobile_game)) {
            return true;
        }

        if (count($args) > 1) {
            return true;
        }

        return false;
    }

    function printStyle()
    {
        $this->fancyPlaycss();
    }

    function printHTML()
    {
        ?>
        <div id="game_page" class="">
            <div class="">
                <?php if (!empty($this->mobile_game)): ?>
                    <div class="">
                        <div class="" href="">
                            <img class="mobile-game-review-logo" src="<?php fupUri("backgrounds/{$this->mobile_game['bkg_pic']}") ?>"/>
                        </div>


                        <div class="mobile-game-review-buttons">
                            <?php $demoButton = lic('freePlayButtonMobile');  ?>
                            <?php if ((isLogged())): ?>
                                <button id="big-deposit-btn"
                                        class="btn btn-m btn-default-xl btn-mobile-game-review deposit"
                                        onclick="<?php echo depGo(); ?>">
                                    <div class="icon icon-vs-casino-coin">
                                        <?php echo t('deposit'); ?>
                                    </div>
                                </button>

                            <?php else: ?>
                                <button id="signup-button1"
                                        class="btn btn-m btn-default-xl btn-mobile-game-review register"
                                        onclick="gotoLang('/mobile/register/');">
                                    <div class="icon icon-vs-person-add">
                                        <?php echo t('register'); ?>
                                    </div>
                                </button>
                                <?php if ((float)$this->game['jackpot_contrib'] > 0 && $this->game['network'] == 'microgaming'): ?>
                                    <div style="flex: 1">
                                        <?php et('no.progplayinfo.html') ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif ?>

                            <?php if(empty($demoButton) || isLogged()): ?>

                            <button class="btn btn-m btn-default-xl btn-mobile-game-review play"
                                    onclick="playMobileGame('<?php echo $this->mobile_game['ext_game_name']; ?>')">
                                <div class="icon icon-vs-slot-machine">
                                    <?php echo t('play.game'); ?>
                                </div>
                            </button>

                            <?php endif; ?>

                             <?= $demoButton ?>

                            <br/>
                        </div>

                        <div class="mobile-game-review-description">
                            <h1><?php echo $this->desktop_game['game_name'] ?></h1>
                            <?php
                            if(empty($this->desktop_game)){
                                $alias = 'gameinfo.' . str_replace('@', '', $this->mobile_game['game_id']) . '.html';
                            }else{
                                $alias = 'gameinfo.' . str_replace('@', '', $this->desktop_game['game_id']) . '.html';
                            }
                            $description = tAssoc($alias, $this->real_game, null, true);
                            echo $description;
                            ?>
                        </div>
                    </div>
                <?php else: ?>
                    <?php echo t('no.game.html') ?>
                <?php endif ?>
            </div>
            <?php noCashBox() ?>
        </div>
        <script>
            $(document).ready(function () {
                var topHeight = $('.mobile-top').outerHeight();
                $('.container-holder').css('padding-top', topHeight+"px"); // Remove gap from top navigation to logo.
                $('.footer-holder').find('div').first().css('width', ""); // Override the hardcoded width 380px to strech the footer to fill the width of screen.
                $('.footer-holder').find('div').first().find('div').css('margin-left', "10px").css('margin-right', "10px"); // Setting margin left and right on divs with content inside the footer-holder.
            });
        </script>
        <?php
    }

    function printExtra()
    { ?>

    <?php }
}
