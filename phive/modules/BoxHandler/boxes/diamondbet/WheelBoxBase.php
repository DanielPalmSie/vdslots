<?php
require_once __DIR__ . '/../../../../../diamondbet/boxes/DiamondBox.php';
require_once __DIR__ . '/../../../DBUserHandler/JpWheel.php';
require_once __DIR__ . '/../../../../../phive/html/common.php';

class WheelBoxBase extends DiamondBox
{

    public function init()
    {
        $this->handlePost(array(
            'color1',
            'color2',
        ), array(
            'color1' => '#fde19b',
            'color2' => '#bf9c46',
        ));
        //'#fde19b' : '#bf9c46';

        $this->brand = phive('BrandedConfig')->getBrand();

        switch ($this->brand) {
            case 'kungaslottet':
                $this->terms_condition_link = "/kungahjulet-info";
                break;
            case 'mrvegas':
                $this->terms_condition_link = "/the-wheel-of-vegas-info";
                break;
            default:
                $this->terms_condition_link = "/the-wheel-of-jackpots-info";
        }

        $this->cu = cuPl();

        if(empty($this->cu)){
            $this->printLoggedOut();
            exit;
        }

        $this->wh         = new JpWheel();
        $this->th         = phive('Trophy');
        $this->userid     = $this->cu->getId();
        $wheel_data       = $this->wh->displayWheel($this->cu, $this->color1, $this->color2);
        $this->slices     = $wheel_data['slices'];
        $this->style_name = $wheel_data['style']['name'];
        $this->rim_name   = phive()->isMobile() ? $this->style_name.'_mobile' : $this->style_name;
        $this->seg        = json_encode(empty($this->slices) ? ['error' => 'no.wheel'] : $this->slices);
        $this->available_promotional_styles = array_column($this->th->getSetting('promotional_wheel'), 'style');
        $this->promotional_style = in_array($this->style_name, $this->available_promotional_styles) ? $this->style_name : null;
    }

    function onAjax(){
        $this->init();
    }

    function printCSS()
    {
        loadCss("/diamondbet/css/" . brandedCss() . "wheel.css");
        loadCss("/diamondbet/css/" . brandedCss() . "fancybox.css");
        loadCss("/phive/js/nanoScroller/nanoscroller.css");
        if(phive()->isMobile()){
            loadCss('/diamondbet/css/wheel_mobile.css');
        }else{
            loadCss('/diamondbet/css/wheel_desktop.css');
        }
    }

    function eJpAmount($amount){
        efIso(chg(phive("Currencer")->baseCur(), $this->cu, $amount, 1));
    }

    function bgStyle($image_type = '') {
        $bg_image = "bg_ui_{$this->style_name}{$image_type}.jpg";
        ?>
        <style>
         body {
             font-family: Trebuchet MS, Arial;
             background-image: url("<?php echo $this->wh->img($bg_image) ?>") !important;
         }

         body::backdrop {
             background-image: url("<?php echo $this->wh->img($bg_image) ?>");
         }
        </style>
        <?php
    }

    function printDesktopHTML(){
        loadJs("/phive/js/multibox.js");
        loadCss("/diamondbet/css/" . brandedCss() . "top-play-bar.css");
        loadCss("/diamondbet/css/" . brandedCss() . "playbox4.css");
        if ( hasMp() ) {
            loadCss( "/diamondbet/css/" . brandedCss() . "tournament.css" );
            loadJs( "/phive/modules/DBUserHandler/js/tournaments.js" );
        }
        require_once phive()->getSetting('site_loc').'diamondbet/html/top-play-bar.php';

        ?>
            <script>

                 function getPlayDims(w,h){
                     var gW = $(window).width();
                     var gH = $(window).height();
                     var dynamic = true;
                     var w = 1500;
                     var h = 750;
                     var asp = w / h
                     var res = calcDims(asp, gW, gH, dynamic, w, h);
                     var rW = Math.round(res[0]);
                     var rH = Math.round(res[1]);
                     return {width: rW, height: rH};
                 }

                 function resizePlayBox(smaller){
                     var dims;
                     var w = undefined;
                     var h = undefined;
                     var dims = getPlayDims(w, h);

                     if(typeof(gamesFooterStatus) != 'undefined' && gamesFooterStatus == 'up'){
                         $(".games-footer").css({"bottom": footerMovement.down.fdist + 'px'});
                         gamesFooterStatus = 'down';
                         $("#games-footer-down").hide();
                         $(".games-footer").find('li').removeClass('gfooter-selected');
                         clearSlider();
                     }

                     $.multibox('resize', 'play-box', dims.width, dims.height);
                     var offsX = -25;
                     var pbHeight = $('#play-box').height();
                     $.multibox('offset', 'play-box', offsX);
                 }

                 $(document).ready(function(){
                     $('.right-fixed').hide();
                     $('.chgrd').hide();
                     $(window).on("resize", resizePlayBox);
                     resizePlayBox();
                 });

                 parent.$.multibox({
                     url: '<?php echo llink()."/?is_mobile=true"; ?>',
                     id: "play-box",
                     type: 'iframe',
                     width: '1500px',
                     height: '750px',
                     globalStyle: {overflow: 'hidden'},
                     cls: 'play-box',
                     hideOverlay: true,
                 });
            </script>

    <?php
       phive('BoxHandler')->boxHtml(967);
    }

    function printHTML(){
        loadJs("/phive/js/fullscreen.js");

        if (phive()->isMobile() || $_GET['is_mobile'] == 'true') {
            $this->printBaseHTML();
            $this->printMobileHTML();
            $image_type = phive()->isMobile() ? '_mobile' : '';
        } else {
            $this->printDesktopHTML();
            $image_type = '_withoutlogo';
        }
        $this->bgStyle($image_type);

    }

    function printMobileHTML(){
        loadCss('/diamondbet/css/fontawesome-free-5.7.0-web/css/fontawesome.css');
        loadCss('/diamondbet/css/fontawesome-free-5.7.0-web/css/solid.css');
        loadCss('/diamondbet/css/fontawesome-free-5.7.0-web/css/regular.css');
    }

    function printModal($id, $msg){ ?>
        <div id="<?php echo $id ?>" class="modal" style="display:none;">
            <!-- Modal content -->
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-title"><?php et('woj') ?></div>
                </div>

                <div class="modal-body" style="text-align:center">
    		    <div class="winning-text-middle">
                        <?php et($msg) ?>
    		    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn-profile" onclick="goToProfile()"><?php et('my.profile') ?></button>
                    <button class="btn-homepage" onclick="goToHome()"><?php et('go.to.homepage') ?></button>
                </div>
            </div>
        </div>

    <?php
    }

    function printBaseHTML()
    {
        loadJs("/phive/js/winwheel.js");
        loadJs("/phive/js/TweenMax.min.js");
        loadJs("/phive/js/wheel.js");

        loadJs("/phive/js/multibox.js");
        loadJs("/phive/js/mg_casino.js");
        loadJs("/phive/js/nanoScroller/jquery.nanoscroller.js");


        $missingAudio = [];
        foreach ($this->wh->wheelAudio as $audio) {
            if (!file_exists(phive('Filer')->getSetting('UPLOAD_PATH') .'/..'. $audio['src']['ogg']['path'])) {
                $missingAudio[] = $audio['src']['ogg']['path'] ?? '';
            }

            if (!file_exists(phive('Filer')->getSetting('UPLOAD_PATH') .'/..'. $audio['src']['mp3']['path'])) {
                $missingAudio[] = $audio['src']['mp3']['path'] ?? '';
            }
            ?>
            <audio id="<?= $audio['id'] ?>" <?= $audio['autoplay'] ?? '' ?> <?= $audio['loop'] ?? '' ?>>
                <source id="<?= $audio['src']['mp3']['id'] ?? 'mp3'.$audio['id'] ?>" src="<?= $audio['src']['mp3']['path'] ?? '' ?>">
                <source id="<?= $audio['src']['ogg']['id'] ?? 'ogg'.$audio['id'] ?>" src="<?= $audio['src']['ogg']['path'] ?? '' ?>">
            </audio>
            <?php
        }

        if ($missingAudio) {
            phive('Logger')
                ->getLogger('web_logs')
                ->error(
                    "Missing audio files in wheel_of_jackpot",
                    $missingAudio
                );
        }
        ?>
    <div id="overlay" style="display:none;"></div>
    <div id="miniJackpot" class="jackpotModal minor-jackpot" style="display:none;"></div>
    <div id="majorJackpot" class="jackpotModal major-jackpot" style="display:none;"></div>
    <div id="megaJackpot" class="jackpotModal mega-jackpot" style="display:none;"></div>

    <div id="wheel">
        <div id="completeWheel">
            <div class="wheel-container">
                <div class="wheel_offset"></div>
                <div class="canvas_box" id="canvasContainer" style="background: url(<?php echo $this->wh->img("wheelfortune_outsiderim_$this->rim_name.png")  ?>) center no-repeat;background-size: cover;">
                <canvas id="canvas" width="901" height="836">
                    <p style="color: grey" align="center"><?php et('no.canvas.support.error') ?> </p>
                    </canvas>
                <img id="prizePointer" src="/file_uploads/wheel/win_pointer_<?php echo $this->style_name ?>.png" alt="Prize Pointer" width="70" height="116" />
                </div>

                <div class="left wheel" style="margin-bottom: -3px;">
                    <script>
                     // Here displays wheel
                     var context = document.getElementById('canvas').getContext("2d");
                     $(document).ready(function(){

                         // We can't have single quotes when we're outputting the JSON as a string using single quotes.
                         displayWheel('<?php echo str_replace("'", '', $this->seg) ?>','<?php echo phive('Config')->getValue('spin-time', 'wheel-spin-time', 9); ?>');

                         // We show the loader until all the audio is ready to play.
                         showLoader(undefined, true);

                         wheelAudioIsLoadedIntvl = setInterval(wheelAudioIsLoaded, 1000);

                         if(isFirefox()){
                           var fileSource = $("#intro-source").attr('src');
                           playSound(fileSource);
                         }
                     });
                    </script>
                </div>

                <div class="spin_box" id="spin_button" onClick="startSpin();">
                <img id="spinButton2" class="spinButton" src="/file_uploads/wheel/Spin-Button_pressed_<?php echo $this->style_name?>.png" alt="Spin Button" />
                <img id="spinButton1" class="spinButton" src="/file_uploads/wheel/Spin-Button_<?php echo $this->style_name?>.png" alt="Spin Button" />
                </div>
            </div>
        </div>

        <?php

            if(!phive()->isMobile()){
                $this->printJackpotLegend();
                $this->printWheelInformationBox();
            }

            // Check if the user has the award
            $jp_spin_award_id = phive('Trophy')->getCurAward($this->cu);
            $jp_spin_award = phive('Trophy')->getAward($jp_spin_award_id);

            // If we have the wrong current type or no current award at all we can't execute the spin.
            if($jp_spin_award['type'] != 'wheel-of-jackpots'):
        ?>
            <script>
                 okToPlay = false;
            </script>
        <?php endif ?>
    </div>
    <?php

        if(phive()->isMobile()){
            $this->printMobileJpInfo();
            $this->printBottomMenuBar();
            $this->printMobileOverlay();
        }
    ?>

    <!-- The Play Sound Modal -->
    <div id="playSound" class="modal" style="display:none;">
        <!-- Modal content -->
        <div class="fs-modal-content">
            <div class="fs-modal-header">
                <div class="fs-modal-title"><?php et('woj') ?></div>
            </div>

            <div class="fs-modal-body" style="text-align:center">
    		<div class="winning-text-middle">
		    <?php et('play.with.sound') ?>
    		</div>
            </div>
            <div class="fs-modal-footer">
                <button class="btn-affirmative" id="btn-soundYes"><?php et('yes') ?></button>
                <button class="btn-homepage" id="btn-soundNo"><?php et('no') ?></button>
            </div>
        </div>
    </div>


    <!-- The Full Screen Modal -->
    <div id="fullScreen" class="modal" style="display:none;">
        <!-- Modal content -->
        <div class="fs-modal-content">
            <div class="fs-modal-header">
                <div class="fs-modal-title"><?php et('woj') ?></div>
            </div>
            <div class="fs-modal-body" style="text-align:center">
    		<div class="winning-text-middle">
		    <?php et('woj.in.fullscreen') ?>
    		</div>
            </div>
            <div class="fs-modal-footer">
                <button class="btn-affirmative" id="btn-fullScreenYes"><?php et('yes') ?></button>
                <button class="btn-homepage" id="btn-fullScreenNo"><?php et('no') ?></button>
            </div>
        </div>
    </div>

    <!-- The No Play Restriction Modal -->
    <?php $this->printModal('noPlay', 'woj.no.award.error.html') ?>

    <!-- The No Win Modal -->
    <?php $this->printModal('noWin', 'woj.no.win.html') ?>

    <!-- The Modal -->
    <div id="congratModal" class="modal" style="display:none;">
        <!-- Modal content -->
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title"><?php et('woj') ?></div>
            </div>

            <div class="modal-body">
                <div class="modal-image"><img src='/file_uploads/wheel/WheelLogo.png'/></div>
        	<div class="winning-text">
        	    <div class="win-body-header">
            		<div class="win-text-header-top"><?php et('congratulations') ?></div>
            		<div class="won-reward winText"></div>
        	    </div>
        	    <div class="win-container-body">
                        <img id="won-reward-img" src="" />
                        <div>
        		    <?php et('woj.whoo.hoo') ?> <span class="won-reward"></span>, <?php et('in.sitename') ?> <?php et('woj') ?>
                        </div>
        	    </div>
        	</div>
            </div>
            <div class="modal-footer">
                <button class="btn-profile" id="goToProfileBtn" onclick="goToProfile()"><?php et('my.profile') ?></button>
                <button class="btn-homepage" id="goToHomeBtn" onclick="goToHome()"><?php et('go.to.homepage') ?></button>
            </div>
        </div>
    </div>

    <?php
    }

    function printBottomMenuBar(){
        ?>
        <div id="woj-bottom-bar" class="woj-bottom-bar" style="<?= phive('Pager')->isDisplayModeIos() ? 'display:none' : '' ?>">
            <div id="woj-bottom-bar-icon-container">
                <i id="bottom-home-btn" class="fas fa-home" onclick="gotoAccUrl()"></i>
                <i id="bottom-info-btn" class="fas fa-info-circle" onclick="toggleOverlayInfo()"></i>
                <i id="sound-toggle-btn" class="fas fa-volume-down" onclick="toggleSound()"></i>
            </div>
        </div>
    <?php
    }

    function printOverlayLegend(){
        $legend_awards = $this->wh->getLegendAwards($this->slices);
    ?>
        <div class="woj-overlay-legends">
            <?php foreach($legend_awards as $legend): ?>
                <div class="woj-overlay-legend-container">
                    <div class="woj-overlay-legend-image">
                        <img src="<?php echo $legend['legend_image'] ?>" />
                    </div>
                    <div class="woj-overlay-legend-text">
                        <?php et($legend['legend_alias']) ?>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php
    }

    function printJackpotLegend()
    {
        $legend_awards = $this->wh->getLegendAwards($this->slices);

        $jackpot_class = 'jackpot-prize' . (!empty($this->promotional_style) ? ' jackpot-prize_trophy--' . $this->promotional_style : '');
        $legend_class = 'jackpot-prize__legend' . (!empty($this->promotional_style) ? ' jackpot-prize__legend--' . $this->promotional_style : '');
        ?>
        <div style="position:absolute; left:3%; top:3%;">
            <table id="jackpot-legend-table" class="<?php echo $jackpot_class; ?>"  border="0" cellspacing="0" cellpadding="0">
                <tbody>
		    <!-- Print Legend Only -->
        	    <?php
        	    foreach ($legend_awards as $key => $legend):
        	    ?>
                    	<tr class="<?php echo $legend_class ?>">
                    	    <td style="min-width:35px;text-align:center;"><img src="<?php echo $legend['legend_image'] ?>" style="max-width:40px;" /></td>
              		    <td style="padding-left:10px;font-size:1.3em">
              			<?php et($legend['legend_alias']) ?>
              		    </td>
                	</tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php
    }

   function printMobileJpInfo(){
       $jackpots = $this->wh->getWheelJackpots();
       $jackpot_class = 'mobile-jp-list' . (!empty($this->promotional_style) ? ' mobile-jp-list--' . $this->promotional_style : '');
       ?>
       <div class="mobile-jp-list-container">
           <div class="<?php echo $jackpot_class ?>">
               <?php foreach($jackpots as $alias => $jackpot):  ?>
                   <div class="mobile-jp-container-box">
                       <img src="<?php $this->wh->legendImg($jackpot['jpalias'].'_info.png') ?>" />
                       <div class="jp-info-text-container">
                           <div class="jp-info-text-color mobile-jp-amount-txt">
                               <?php $this->eJpAmount($jackpot['amount']) ?>
                           </div>
                           <div class="mobile-jp-name-txt">
                               <?php et($jackpot['jpalias'].'.legend') ?>
                           </div>
                       </div>
                   </div>
               <?php endforeach ?>
           </div>
       </div>
   <?php
   }

   function printMobileOverlay(){ ?>
       <div id="woj-info-overlay" style="display: none;">
           <i class="far fa-times-circle woj-info-overlay-close-btn" onclick="toggleOverlayInfo()"></i>
           <div class="woj-info-overlay-content">
               <div id="woj-info-jp-info"></div>
               <div id="woj-info-jp-legend"></div>
           </div>
       </div>
   <?php
   }

   function printJackpotInformation()
   {
       $jackpots = $this->wh->getWheelJackpots();
       $jackpot_class = 'jackpot-prize' . (!empty($this->promotional_style) ? ' jackpot-prize_jackpotvalues--' . $this->promotional_style : '');
       ?>

       <div id="jackpotvalues">
           <table class="<?php echo $jackpot_class ?>" border="0" cellspacing="0" cellpadding="0">
               <tbody>
                   <tr style="width:100px;background-color:#171111;">
                       <td colspan="2" style="line-height: 2px;">&nbsp;</td>
                   </tr>

                   <!-- Print Jackpot Information Only -->
        	   <?php foreach ($jackpots as $key => $jackpot): ?>
                       <tr style="width:100px;background-color:#171111">
                	   <td class="jpimage" style="min-width:35px;text-align:center;">
                	       <img src="<?php $this->wh->legendImg($jackpot['jpalias']. "_reward.png") ?>" width="100px"/>
                	   </td>
          		   <td class="jpinfo">
          		       <div class="jp-info-text"><?php efIso(chg(phive("Currencer")->baseCur(), $this->cu, $jackpot['amount'], 1)) ?></div>
          		       <?php echo $jackpot['name']?>
      		    	   </td>
          	       </tr>
              	   <?php endforeach ?>

              	   <tr style="width:100px;background-color:#171111;">
                       <td class="jackpot-term-condition" colspan=2>
                    	   <a href="<?php echo llink($this->terms_condition_link) ?>" style="text-decoration: none;" target="_blank" rel="noopener noreferrer"><?php et("wheel.termsandconditions"); ?></a>
                       </td>
                   </tr>
               </tbody>
           </table>
       </div>
    <?php
    }

    function printWheelEvents()
    {
        // seems that ajaxGetBoxHtml does not init class
        $this->wh = new JpWheel();
        $this->cu = cuPl();
        $this->th = phive('Trophy');

        $latestWinners = $this->wh->getLatestJackpotWinners(3);
        $jackpot_class = 'jackpot-prize' . (!empty($this->promotional_style) ? ' jackpot-prize_jackpotswinners--' . $this->promotional_style : '');

        // if there are no latest winners then display nothing
        if (! empty($latestWinners)){?>

        	<div id="jackpotwinners" class="nano has-scrollbar">
            	<div class="nano-content">
                    <table class="<?php echo $jackpot_class ?>" border="0" cellspacing="0" cellpadding="0">
                        <tbody>
                            <tr>
                                <td class="latest-winners-row center-stuff" colspan="2">
                                    <?php et("latest.jackpots.winners") ?>
                                </td>
                            </tr>
                        <!-- Print Awards Only -->
                	    <?php foreach ($latestWinners as $key => $winner):
                	        $imagename = $winner['alias'];
                	    ?>
                    	        <tr class="latest-winners-row">
                    	            <td class="" style="min-width:35px;text-align:center;">
                    		        <img src="<?php fupUri("wheel/".$imagename . "_reward.png") ?>" width="100px" />
                    	            </td>
              		    	    <td style="padding:10px;padding-left:0px;font-size:1.4em;line-height:1.3em;text-align:left; min-width: 200px;">
                      		        <b><?php echo $winner['firstname'] ?></b> <?php et('won') ?> <br/>
                      		        <b><?php echo $winner['user_currency'] . " " . nf2($winner['win_jp_amount'], true, 100) ?></b>
                      			<div style="color:#da9023;">
                      			    <b><?php echo $winner['description'] ?></b>
                      			</div>
                  		    </td>
                  		</tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
   	   <?php
        }
    }



    function printWheelInformationBox()
    {
        ?>
        <div style="position:absolute; right: 4%; top: 5%;">
    	    <?php $this->printJackpotInformation(); ?>
    	    <?php $this->printWheelEvents(); ?>
        </div>

        <script>
             $("#jackpotwinners").height( $("#wheel").height() - $("#jackpotvalues").height() - 200 );
             $("#jackpotwinners").nanoScroller();
        </script>

   	   <?php
    }


    function printWheelInformationContent()
    {
        // seems that ajaxGetBoxHtml does not init class
        $this->wh = new JpWheel();
        $this->cu = cuPl();
        $this->th = phive('Trophy');

        ?>
        <div class="game-mode">
    	    <?php $this->printJackpotInformation(); ?>
    	    <?php $this->printWheelEvents(); ?>
        </div>

	<?php
    }


    function printExtra()
    {
    ?>
        <p>Color 1 in hex with the hash too please:</p>
        <p>
            <?php dbInput('color1', $this->color1)?>
        </p>
        <p>Color 2 in hex with the hash too please:</p>
        <p>
            <?php dbInput('color2', $this->color2)?>
        </p>
        <p>Terms and Conditions link:</p>
        <p>
            <?php dbInput('terms_condition_link', $this->terms_condition_link)?>
        </p>
	<?php
    }

    function printLoggedOut()
    {
        exit;
    }

}
