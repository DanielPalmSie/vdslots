<?php

use Videoslots\ContactUs\ContactUsService;

require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
require_once __DIR__.'/../../../../../phive/modules/Former/Validator.php';

class EmailFormBoxBase extends DiamondBox{

  function init(){
    $this->handlePost(array('to_email', 'lang_emails'));
    $this->lang_arr = phive()->fromDualStr($this->lang_emails);
    $this->saveEmail();
  }

  function saveEmail(){
    if(!empty($_POST['email_submit'])){

      $to_email = $this->lang_arr[cLang()];
      if(empty($to_email))
        $to_email = $this->to_email;
      $this->err = array();

      foreach(array('from', 'subject', 'message') as $f){
        if(empty($_POST[$f]))
          $this->err[$f] = 'empty.err';
      }

      if(PhiveValidator::captchaCode() != $_POST['captcha'])
        $this->err['captcha'] = 'captcha.err';

      if(empty($this->err)){
        $mh = phive('MailHandler2');
        $content = "From: {$_POST['from']} <br><br> Message:<br> {$_POST['message']}";
        $mh->saveRawMail($_POST['subject'], $content, "notifications@".$mh->getSetting('domain').".com", $to_email, $_POST['from'], 0);
      }
    }
  }

  function printErrZone(){ ?>
    <div id="errorZone" class="errors">
      <?php foreach($this->err as $field => $err): ?>
        <?php echo t('email.'.$field).' '.t($err); ?><br>
      <?php endforeach ?>
      <?php if(!empty($_POST['email_submit']) && empty($this->err)): ?>
        <?php et('email.successfully.sent') ?>
      <?php endif ?>
    </div>
    <?php
  }

  public function printHTML()
  {
      $service = new ContactUsService(false, $this);
      $data = $service->getContactUsData();
      ?>
    <div id="support-contact-info" class="frame-block generalSubBlock">
      <div class="frame-holder">
        <div class="pad-stuff">
          <table class="w-100-pc support-contact-info-table">
              <tr><td><?php $this->printErrZone() ?></td></tr>
              <tr>
              <td class="w-50-pc">
                <h1>
                  <?php et($data->getHeadline()) ?>
                </h1>
                <div>
                  <?php et($data->getDescription()) ?>
                </div>
                <br/>
                <?php basicMailForm($data->getFrom(), true, '', 'support_email_id', 'support_email_class' ) ?>
              </td>
              <?php if($GLOBALS['site_type'] != 'mobile'): ?>
              <td class="support-contact-info-right w-50-pc">
                  <div class="margin-twenty-left w-300 right">
                    <?php img($data->getContactInformation()->getMap(), ContactUsService::MAP_IMAGE_WIDTH, ContactUsService::MAP_IMAGE_HEIGHT) ?>
                    <br/>
                    <?php et($data->getContactInformation()->getDescription1()) ?>
                    <h3 class="support-contact-info-header-3 header-3"><?php et($data->getContactInformation()->getHeadline()) ?></h3>
                    <?php et($data->getContactInformation()->getDescription2()) ?>
                    <br/>
                    <br/>
                    <table class="support-contact-info-secondary-table v-align-top">
                      <tr>
                        <td>
                          <img class="support-contact-info-email-img" src="<?=$data->getContactInformation()->getEmailIcon()?>" />
                        </td>
                        <td>
                          <?php et($data->getContactInformation()->getEmail()) ?>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <img src="<?=$data->getContactInformation()->getAddressIcon()?>" />
                        </td>
                        <td>
                          <?php et($data->getContactInformation()->getAddress()) ?>
                        </td>
                      </tr>
                    </table>
                  </div>
              </td>
              <?php endif ?>
            </tr>
          </table>
        </div>
      </div>
    </div>
    <?php
  }

  public function printExtra(){ ?>
    <p>
      Default to email:
      <input type="text" name="to_email" value="<?php echo $this->to_email ?>"/>
    </p>
    <p>
      Language specific emails (if language is missing the default email will be used), ex (sv:se@dbet.com|fi:fi@dbet.com):
      <input type="text" name="lang_emails" value="<?php echo $this->lang_emails ?>"/>
    </p>
  <?php }

}
