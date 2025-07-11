<?php
require_once __DIR__ . '/WheelBoxBase.php';

class WheelInfoBoxBase extends WheelBoxBase
{

    public function init()
    {
        $this->wh = phive('DBUserHandler/JpWheel');
        $this->cu = cuPl();
        $this->th = phive('Trophy');
        $this->userid = $this->getId();
    }

    function printCSS()
    {
        // loadCss('/diamondbet/css/wheel.css');
        loadCss("/phive/js/nanoScroller/nanoscroller.css");
    }


    function printHTML()
    {
        loadJs("/phive/js/nanoScroller/jquery.nanoscroller.js");
        $this->printJackpotInfoHTML();
    }

    function eJpAmount($amount, $return = false){
        $chg = chg(phive("Currencer")->baseCur(), $this->cu, $amount, 1);
    
            if($return) {   
                return efIso($chg, $return);
            }
            efIso($chg);        
        }
    

    function printJackpotInformation()
    {
        $jackpots = $this->wh->getCache();
     ?>

    <div id="jackpotvalues">
        <table class="jackpot-prize" border="0" cellspacing="0" cellpadding="0">
       	    <tbody>
        	<?php foreach ($jackpots as $alias => $jackpot): ?>
                    <tr>
                        <td class="jpimage" style="text-align:center;padding-top:15px;padding-bottom:5px;">
                            <?php img('jpimage' . $alias, 275, 97, 'jpimage' . $alias, false, null, '', fupUri("wheel/{$alias}_info.png", true)); ?>
                        </td>
                    </tr>
                    <tr>
          		<td class="jpinfo">
			    <div class="<?php echo($alias) ?>_Val woj-info-jp-amount">
                                <?php $this->eJpAmount($jackpot['prev_amount']) ?>
                            </div>
              		</td>
              	    </tr>
              	<?php endforeach ?>
            </tbody>
        </table>
    </div>

<?php
    }


function printJackpotInformationOnly()
{
    $jackpots = $this->wh->getWheelJackpots();
?>

    <div id="jackpotvalues">
        <?php foreach($jackpots as $jp): ?>
        <table class="jackpot-prize" border="0" cellspacing="0" cellpadding="0">
       	    <tbody>
                <tr>
                    <td style="width:25%;">&nbsp;</td>
            	    <td class="jpimage" style="text-align:center;padding-top:15px;padding-bottom:5px;">
            		<img src="<?php fupUri("wheel/". $jp['jpalias']. "_info.png") ?>" width="275px" loading="lazy"/>
                    </td>
                    <td style="width:25%;">&nbsp;</td>
                </tr>

                <tr>
                    <td>&nbsp;</td>
      		    <td class="jpinfo">
			<div class="<?php echo($jp['jpalias'])?>_Val wheel-info-jackpot-number"></div>
          	    </td>
          	    <td>&nbsp;</td>
          	</tr>
            </tbody>
        </table>
        <?php endforeach ?>
    </div>
<?php
}


function printLatestWinners($latestWinners) { ?>
    <div class="jackpot-prize"><b><?php et("latest.jackpots.winners"); ?></b></div>

    <table border="0" cellspacing="0" cellpadding="0" width="100%" style="padding-top:20px;font-size:1.2em;" id="jackpot-prize__header">
        <tbody>
            <tr>
                <td style="width:20%;text-align:left;padding-top:10px;padding-bottom:10px;">&nbsp;</td>
                <td style="width:20%;"><?php echo ucfirst(t('name')) ?></td>
                <td style="width:20%;"><?php echo ucfirst(t('date')) ?></td>
                <td style="width:40%;"><?php echo ucfirst(t('prize')) ?></td>
            </tr>
        </tbody>
    </table>

    <div id="jackpotwinners" class="nano has-scrollbar woj-info-jp-winners">
        <div class="nano-content">
            <table border="0" cellspacing="0" cellpadding="0" width="100%" style="font-size:1.2em;">
                <tbody>
                    <!-- Print Awards Only -->
		    <?php foreach ($latestWinners as $key => $winner) {
            	        $color     = ($key % 2) ? '#333333' : '#141414';
                        $imagename = $winner['alias'];
                        $date__string = phive()->lcDate($winner['created_at'], '%x');
                        $date_font_size = '';
                        if (strlen($date__string) > 10) {
                            $date_font_size = phive()->isMobile() ? 'font-size: 9px': 'font-size: 11px';
                        }
            	    ?>
			<tr style="background-color:<?php echo($color); ?>">
                    	    <td style="width:20%;text-align:center;padding-top:10px;padding-bottom:10px;">
                    		<img src="<?php fupUri("wheel/".$imagename . "_reward.png") ?>" width="60px" />
                            </td>
                  	    <td style="width:20%;">
              			<b><?php echo ($winner['firstname']);?></b>
                  	    </td>
                  	    <td style="width:20%; <?php echo $date_font_size ?>">
                  		<b><?php echo $date__string ?></b>
                  	    </td>
                  	    <td style="width:40%; padding-left: 5px;">
          			<b><?php echo $winner['user_currency'] . " " . nfCents($winner['win_jp_amount'], true)?></b>
                  	    </td>
                  	</tr>
               	    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
<?php
}


function printJackpotInfoHTML(){  ?>
    <div id='wheel-info-content'>
        <div class="wheel-info-top-image">
            <?php img('wheel.info.top.image', 961, 307, 'wheel.info.top.image', false, null, '', '/file_uploads/wheel/WOJ_Info.png'); ?>
        </div>
        <div style="padding:20px;">
            <div class="jp-info-content">
                <div class="jp-info-title">
                    <b><?php et('the.wheel.of.jackpots') ?></b>
                </div>

                <div class="jp-info-desc">
                    <?php et('wheel.info.page.top.header.html') ?>
                </div>

                <div>
                    <?php
                    $this->wh = new JpWheel();
                    $this->cu = cuPl();
                    $this->th = phive('Trophy');

                    $latestWinners = $this->wh->getLatestJackpotWinners();

                    // if there are no latest winners then display nothing
                    if (!empty($latestWinners)): ?>

                        <div class="woj-info-jp-info-container">
            		    <?php $this->printJackpotInformation(); ?>
                        </div>

                        <div class="woj-info-latest-winners">
                    	    <?php $this->printLatestWinners($latestWinners); ?>

                	    <script>
                             $("#jackpotwinners").nanoScroller();
                             $('#jackpotwinners .nano-content').prop('style').setProperty('right', '-15px');
                        </script>

                        </div>
                        <div style="clear:both;"></div>

          	    <?php else: ?>

            	        <div style="width:100%; padding-top:30px;padding-right:2%;padding-bottom:2%;">
            		    <?php $this->printJackpotInformationOnly(); ?>
                        </div>

             	    <?php endif ?>

                </div>

                <div class="jp-info-desc">
                    <?php et('wheel.info.page.header.html') ?>
                </div>
                <div class="jp-info-t-and-c">
                    <?php et('wheel.info.page.content.html') ?>
                </div>
            </div>



        </div>
    </div>

    <script>

     var currAmounts = {};

     function updateValues(name, amount, bounce) {
         // If the current amount is larger or the same as the new amount we do nothing, we can't
         // suddenly start from a lower level.
         if(!empty(currAmounts[name]) && currAmounts[name] >= amount){
             return;
         }

         currAmounts[name] = amount;

         var currentlyDisplayed = $(name).html();

         /*
          * Finding the length of amount and checking if it is a mobile device or not
          * Appliying changes only if it is desktop and jackpot amount digit count is greater than 8 to display currency and amount readable.
          */
         var amountLength = Math.floor(amount).toString().length;
         var jackpotAmountDigitCount = 8; // 8 is the digit count of the jackpot amount used in condition to apply style.
         var toBeDisplayed = (!isMobile() && amountLength > jackpotAmountDigitCount )
                            ? "<?php ciso(true) ?>  <div class='jackpot-amount'>" + fmtMoney(amount / 100, 2) + "</div>"
                            : "<?php ciso(true) ?> " + fmtMoney(amount / 100, 2);
         $(name).html(toBeDisplayed);

         if(bounce && currentlyDisplayed !== toBeDisplayed){
             $(name).effect('bounce','fast');
         }
     }


     var minorIntValIds = [];

     function updateData(){

         // We reset all minor intervals (the ones happening every 10 seconds) to avoid event buildup.
         _.each(minorIntValIds, function(id){
             clearInterval(id);
         });

         minorIntValIds = [];

         // The ajax call to get the cached data.
	 mgJson({action: "get-woj-jackpots"}, function(ret){

             // We loop each jackpot and its alias.
             _.each(ret, function(el, alias){

                 var name          = "." + alias  + "_Val";
                 var startAmount   = parseInt(el.prev_amount);
                 var endAmount     = parseInt(el.curr_amount);
                 var incAmount     = (endAmount - startAmount) / 6;
                 var currAmount    = startAmount;

                 // Initial update.
                 updateValues(name, currAmount, false);

                 // We intiate the minor intervals that will increase the amounts every 10 seconds.
                 minorIntValIds.push(setInterval(function(){
                     currAmount += incAmount;
                     updateValues(name, currAmount, true);
                 }, 10000));
             });
	 });
     }

     updateData();
     setInterval(updateData, 65000);

    </script>

<?php }

    function printExtra(){}
}
