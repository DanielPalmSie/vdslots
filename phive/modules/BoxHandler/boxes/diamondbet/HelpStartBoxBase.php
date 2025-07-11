<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
class HelpStartBoxBase extends DiamondBox{

    function init()
    {
        $this->handlePost(array('faq_url', 'email_url', 'chat_url', 'rglink_url'));
    }

    /**
     * Output a -right aligned- column in the Deposit box
     *
     * @param string $locstr    The column identifier. Fetches help.start.$locstr.* localized strings
     * @param string $url       The url to go when clicking on that column
     * @param string $img       Display image file $img.png using class .help-$img
     * @param boolean $dolang   If true, use $func for the $url. If false, just use the $url
     * @param string $func      Name of JS function to use for opening the $url. Default = 'goTo'
     * @param string $target    HTML target for the $func. May be also '_blank', '_parent', '_top'. Default = ''
     *
     * @return void
     */
    function rightCol($locstr, $url, $img, $dolang = true, $func = 'goTo', $target = '')
    {
        if ($target != '') {
            $target = ", '{$target}'";
        }

        $link = $dolang ? "{$func}('".llink($url)."'{$target});" : $url;
?>
<td class="faq-right-image <?php echo "help-image-".$img ?>">
  <img onclick="<?php echo $link ?>" src="/diamondbet/images/<?= brandedCss() ?>support/<?php echo $img ?>.png" />
</td>
<td class="faq-right-text pointer <?php echo "help-".$img ?>" onclick="<?php echo $link ?>">
  <h3><?php et("help.start.$locstr.headline") ?></h3>
  <?php et("help.start.$locstr.descr") ?>
</td>
<?php
    }

    /**
     * Output the RG link column in Deposit box
     *
     * @param string $url The $url to open
     *
     * @return void
     */
    public function rgLink($url)
    {
        $this->rightCol('rglink', $url, 'rg-link', true, 'goTo', '_top'); // boToBlank
    }
function privacyAdjustment($locstr, $url, $img){
  $this->rightCol($locstr, $url, $img, false);
}

function liveChat($url = ''){
  $this->rightCol('live.chat', $url ?? phive('Localizer')->getChatUrl(), 'live-chat', false);
}

function sendEmail($func = 'goTo'){
  $this->rightCol('email', $this->email_url, 'send-us-an-email', true, $func);
}

function talkWithUs(){
  $this->rightCol('talk', 'parentGetPhoneUsForm();', 'talk-with-us', false);
}

function readFaq($func = 'goTo'){
  if ($this->faq_url) {
      $this->rightCol('faq', $this->faq_url, 'FAQ-2', true, $func);
  }
}

function printHTML(){?>
<div class="frame-block generalSubBlock">
  <div class="frame-holder">
    <div>
      <table class="v-align-top">
	<tr>
	  <td>
	    <div class="faq-left">
	      <ul>
		    <?php $this->printHelpMenu() ?>
	      </ul>
	    </div>
	  </td>
	  <td>
	    <div class="faq-right">
	      <h1><?php et("contact.us") ?></h1>
	      <?php et('contact.us.html') ?>
	      <?php
              if ($this->faq_url) {
                  $this->faqSearch(llink($this->faq_url));
              }
          ?>
	      <table class="v-align-top">
		<tr>
		  <?php $this->liveChat('parent.'.phive('Localizer')->getChatUrl()) ?>
		  <?php $this->sendEmail() ?>
		</tr>
		<tr>
		  <?php $this->talkWithUs() ?>
		  <?php $this->readFaq() ?>
		</tr>
		<tr>
		  <td class="faq-right-image">
		    <img src="/diamondbet/images/<?= brandedCss() ?>support/office-address.png" />
		  </td>
		  <td class="faq-right-text">
		    <h3><?php et("help.start.address.headline") ?></h3>
		    <?php et("help.start.address.descr") ?>
		  </td>
		  <td>&nbsp;</td>
		  <td>&nbsp;</td>
		</tr>
	      </table>
	      <br/>
	      <?php img('customer-care-bottom', 630, 90) ?>
	    </div>
	  </td>
	</tr>
      </table>
    </div>
  </div>
</div>
<?php }

function printExtra(){ ?>
<p>
  <label for="alink">FAQ url: </label>
  <input type="text" name="faq_url" value="<?= $this->faq_url ?>" />
</p>
<p>
  <label for="alink">Email url: </label>
  <input type="text" name="email_url" value="<?= $this->email_url ?>" />
</p>
<p>
  <label for="alink">Chat url: </label>
  <input type="text" name="chat_url" value="<?= $this->chat_url ?>" />
</p>
<?php }
}
