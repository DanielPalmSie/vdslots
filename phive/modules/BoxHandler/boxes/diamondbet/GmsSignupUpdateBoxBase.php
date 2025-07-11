<?php

use Laraphive\Domain\User\DataTransferObjects\AccountHistoryData;
use Laraphive\Domain\User\DataTransferObjects\EditProfile\ContactInfoData;
use Laraphive\Domain\User\DataTransferObjects\EditProfile\FormElements\CheckboxData;
use Laraphive\Domain\User\DataTransferObjects\EditProfile\FormElements\InputData;
use Laraphive\Domain\User\DataTransferObjects\EditProfile\FormElements\LabelData;
use Laraphive\Domain\User\DataTransferObjects\EditProfile\FormElements\SelectBoxData;
use Laraphive\Domain\User\DataTransferObjects\GameHistoryData;
use Laraphive\Domain\User\DataTransferObjects\ProfileData;
use Laraphive\Domain\User\DataTransferObjects\Responses\GetProfileContactValidatePopupResponseData;
use Laraphive\Domain\User\DataTransferObjects\UserContactData;
use Laraphive\Domain\User\Factories\UpdateProfileAccountRequestFactory;
use Laraphive\Domain\User\Factories\UserContactDataFactory;
use Laraphive\Support\DataTransferObjects\ErrorsOrEmptyResponse;
use Videoslots\User\Factories\UpdateProfileAccountServiceFactory;
use Videoslots\User\WeekendBooster\WeekendBoosterService;

require_once __DIR__ . '/../../../../../diamondbet/boxes/DiamondBox.php';
require_once __DIR__ . '/../../../Former/FormerCommon.php';
require_once __DIR__ . '/../../../Cashier/Mts.php';

class GmsSignupUpdateBoxBase extends DiamondBox{

    /**
     * This is used to track if the DMAPI is available.
     * It is also used when sending documents to DMAPI is not enabled,
     * in that case it makes sure the upload forms are visible.
     *
     * @var bool
     */
    public $dmapi_available = true;
    public $spy_js_loaded = false;

    /** @var DBUser $cur_user */
    public $cur_user;

    function shouldBlockTAC($user): bool
    {
        return (
            !$user->hasCurTc() ||
            (lic('hasBonusTermsConditions') && !$user->hasCurBtc()) ||
            (lic('isSportsbookEnabled') && !$user->hasCurTcSports()) ||
            $user->hasSetting('tac_block') ||
            $user->hasSetting('tac_block_sports') ||
            $user->hasSetting('bonus_tac_block')
        );
    }
  function t($field_name){
    return empty($_POST[$field_name]) ? t('register.'.$field_name) : $_POST[$field_name];
  }

  function is404($args){
    return count($args) > 3;
  }

  function prShowHideSubmit($p, $setting, $label, $shown_msg){ ?>
  <?php if(p($p)): ?>
    <?php if($this->cur_user->hasSetting($setting)): ?>
      <?php echo $shown_msg ?>.
    <?php else: ?>
      <form method="post">
        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
        <input type="submit" onclick="return confirm('Are you sure?');" name="<?php echo $setting ?>" value="<?php echo $label ?>" />
      </form>
    <?php endif ?>
  <?php endif ?>
<?php }

    /**
     * @param Laraphive\Domain\User\DataTransferObjects\EditProfile\ContactInfoData $contactInfoData
     *
     * @return void
     */
    function printAccountBtn(ContactInfoData $contactInfoData) { ?>
        <?php foreach ($contactInfoData->getButtons() as $button): ?>
            <input
                id="<?= $button->getName(); ?>"
                type="<?= $button->getType(); ?>"
                name="<?= $button->getName(); ?>"
                value="<?php echo t($button->getValue()) ?>"
                class="btn btn-l btn-default-l edit-profile-submit-btn"
            />
        <?php endforeach; ?>
        <input type="hidden" name="token" value="<? echo $_SESSION['token']; ?>">

        <script>
            $(function() {
                const accountButton = $('#validate_contact_info');
                const emailInput = $('#email');
                const addressInput = $('#address');

                const isEmailDisabled = emailInput?.is('[disabled]');
                const isEmailVisible = emailInput?.is(':visible');
                const isAddressDisabled = addressInput?.is('[disabled]');
                const isAddressVisible = addressInput?.is(':visible');

                if (isEmailVisible && isEmailDisabled) {
                    if (isAddressVisible && !isAddressDisabled) {
                        return;
                    }

                    accountButton.prop('disabled', true);
                }
            });
        </script>
    <?php
    }

    /**
     * @param $err
     * @param \Laraphive\Domain\User\DataTransferObjects\EditProfile\ContactInfoData $contactInfo
     *
     * @return void
     */
    function printEditContactDetails($err, ContactInfoData $contactInfo){?>
  	<?php loadCss("/diamondbet/css/cashier.css") ?>
    <?php moduleHtml('DBUserHandler', 'edit_profile/contact_info_saved_popup') ?>

    <?php
        $email_and_mobile_disabled = !phive()->isEmpty($this->cur_user->getSetting('change-cinfo-unlock-date'));
        $address_details_disabled = !phive()->isEmpty($this->cur_user->getSetting('change-address-unlock-date'));
        $none_disabled = !$email_and_mobile_disabled && !$address_details_disabled;
    ?>

    <form id="edit-profile-form" name="registerform" method="post" action="">
        <input type="hidden" name="token" value="<? echo $_SESSION['token']; ?>">
        <div class="registerform contact-info-section">
            <?php foreach ($contactInfo->getFormElements() as $element): ?>
                <?php if ($element->isShow() === true): ?>
                    <?php if ($element instanceof InputData): ?>
                        <div class="contact-info-item">
                            <div><strong><?php echo t($element->getAlias()) ?></strong></div>
                            <div>
                                <?php
                                $elementName = $element->getName();
                                $extraAttributes = $elementName === 'email' ? lic('getMaxLengthAttribute', ['email']) : '';

                                if ($element->isDisabled()) {
                                    $extraAttributes .= ' disabled';
                                }

                                dbInput(
                                    $element->getName(),
                                    phive()->html_entity_decode_wq($element->getValue()),
                                    $element->getInputType(),
                                    '',
                                    $extraAttributes
                                ) ?>
                            </div>
                        </div>

                        <?php if ($element->getName() === 'mobile' && ($email_and_mobile_disabled || $address_details_disabled)): ?>
                            <?php foreach ($contactInfo->getDescriptions() as $description): ?>
                                <div class="contact-info-description-item">
                                    <div><?php echo t($description) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <? endif; ?>

                    <?php endif; ?>
                    <?php if ($element instanceof SelectBoxData && $element->getName() === 'main_province'): ?>
                        <div class="contact-info-item">
                            <div><strong><?php echo t($element->getAlias()) ?></strong></div>
                            <div>
                                <?php dbSelect(
                                    $element->getName(),
                                    $element->getOptions()->getItems(),
                                    $element->getValue(),
                                    [],
                                    'regform-field',
                                    false,
                                    $element->isDisabled() ? 'disabled' : ''
                                ) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if ($this->cur_user->getAttr('dob') == '0000-00-00'): ?>
                <div class="contact-info-item">
                    <div><strong><?php echo t('account.dob') ?></strong></div>
                    <div>
                        <div id="birthdate-container">
                            <?php foreach ($contactInfo->getFormElements() as $element): ?>
                                <?php if ($element instanceof SelectBoxData && $element->isShow() === true &&
                                    $element->getAlias() === 'account.dob'): ?>
                                    <?php if ($element->getName() === 'birthyear'): ?>
                                        <span class="styled-select" id="birthyear-cont">
                                            <?php dbSelect(
                                                $element->getName(),
                                                $element->getOptions()->getItems(),
                                                $element->getValue()
                                            ) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="styled-select">
                                            <?php dbSelect(
                                                $element->getName(),
                                                $element->getOptions()->getItems(),
                                                $element->getValue(),
                                                array('', t($element->getPlaceholder()))
                                            ) ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($none_disabled): ?>
                <?php foreach ($contactInfo->getDescriptions() as $description): ?>
                    <div class="contact-info-description-item">
                        <div><?php echo t($description) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <div class="update-button">
                <div>
                    <?php dbInput('user_id', $this->cur_user->getId(), 'hidden') ?>
                    <?php $this->printAccountBtn($contactInfo) ?>
                </div>
            </div>
        </div>
    </form>

    <script>
        let isIdScanDataSubmitted = false;

        <?php if(!empty($_SESSION['toupdate'])): ?>
            isIdScanDataSubmitted = true;
            mgAjax({action: 'update-cinfo'}, function(res){
                const ajaxResultMessage = `<div id="errorZone" class="errors">${res}</div>`;
                $('#contact-info-box').append(ajaxResultMessage);
            });
        <?php endif ?>

        const isSubmittedContactInfoValidation = !!'<?= !empty($_POST['validate_contact_info']) ?>';
        const isSubmittedContactInfo = !!'<?= !empty($_POST['submit_contact_info']) ?>';
        const hasErrors = !!'<?= !empty($err) ?>';

        if (isSubmittedContactInfoValidation || isSubmittedContactInfo) {
            // prevent form resubmit on page reload
            window.history.replaceState(null, null, window.location.href);
        }

        const onSuccessPopupClosed = function() {
            if (isIdScanDataSubmitted) {
                jsReloadBase();
            }
            // TODO: show documents verify popup (CANV-7155)
        };

        const isContactInfoSavedMessageShown = isIdScanDataSubmitted || (isSubmittedContactInfo && !hasErrors);
        if (isContactInfoSavedMessageShown) {
            mboxMsg(
                $('#edit-profile-success-popup').html(),
                true,
                onSuccessPopupClosed,
                400,
                ...Array(7),
                'edit-profile-success-popup'
            )
        }

        const isValidationSuccessful = isSubmittedContactInfoValidation && !hasErrors;
        if (isValidationSuccessful) {
            const isAdmin = !!'<?= p('admin_top') ?>';
            const originalEmail = '<?= $this->cur_user->getAttr('email') ?>'.trim();
            const originalMobile = '<?= $this->cur_user->getAttr('mobile') ?>'.trim();
            const email = $('[name="email"]').val().trim();
            const mobile = $('[name="mobile"]').val().trim();

            const changedMobileOrEmail = originalEmail !== email || originalMobile !== mobile;

            if (changedMobileOrEmail && !isAdmin) {
                const extraOptions = isMobile() ? { width: '100%' } : { width: '420px' };
                const params = {
                    module: 'DBUserHandler',
                    file: 'edit_profile/validation_code_popup',
                    boxid: 'edit_profile_validation_code_popup',
                    closebtn: 'yes',
                    email: email,
                    mobile: mobile
                };

                mgSecureAjax({ action: 'send-sms-code', regenerate: 1 });
                mgAjax({ action: 'send-email-code', regenerate: 1 });

                extBoxAjax('get_html_popup', 'edit_profile_validation_code_popup', params, extraOptions);
            } else {
                $('[name="submit_contact_info"]').click();
            }
        }
    </script>
    <?php }

  function laterPrev($page){ ?>
    <strong>
      <?php if($page > 1): ?>
        <a href="?page=<?php echo $page - 1 ?>">&#xAB; <?php et('later') ?></a>
      <?php endif ?>
      &nbsp;
      <a href="?page=<?php echo $page + 1 ?>"> <?php et('earlier') ?> &#xBB;</a>
    </strong>
  <?php }

  function errorZone($err, $update = true){
    if(empty($err) && $update)
        $err = array('profile' => 'profile.update.success');
    ?>
    <div id="errorZone" class="errors">
    <?php
    if(is_array($err)) {
        foreach($err as $field => $e) {
          echo t('register.'.$field).': '; $this->prErr( $e ); echo '<br>';
        }
    } elseif(is_string($err)) {
        echo $err;
    }
    ?>
    </div>
  <?php }

    /**
     * This is only used for the document page
     *
     * @param array $err
     * @param bool $update
     */
    function errorZone2($err, $update = true)
    {
        if(empty($err) && $update && !empty($_POST['submit']))
            $err = array('profile' => 'profile.update.success');
        ?>
        <div id="errorZone" class="errors">
            <?php
            foreach($err as $field => $e) {
                echo t($field).': ';
                $this->prErr($e, '');
                echo '<br>';
            }
            ?>
        </div>
        <?php
    }

  function usrSessGet($key){
    return $_SESSION['user_to_update'][$key];
  }

    public function drawProfileInfo(int $i, ProfileData $data)
    {
        ?>
        <tr class="<?php echo $i % 2 == 0 ? 'even' : 'odd' ?> obfuscated">
            <td></td>
            <td><?php echo t($data->getTitle()) ?></td>
            <td data-key="<?php echo $data->getField() ?>"><?php echo $data->getValue() ?></td>
            <td><?php echo $this->spyObfuscatedData($data->getField(), $data->getUserId()) ?></td>
        </tr>
    <?php }

  function obfuscateData($key, $object) {
    return p('admin_top')
      ? "***********"
      : $object[$key];
  }

  function spyObfuscatedData($key, $user_id, $text = false) {
    $image = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAyCAYAAAAeP4ixAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAZjSURBVGhD7ZhZbBtFGMc3vaAgEKDyADwhXpDoU9WnPiAhRCsh3iiiHKLQU/QACqJQJFqEKiH1iO0AoRc0je22SWwndd20aZLaOUjSJo7tJvHa3o2bw0mc24kTN46P4Zv1uO7uTtK0SUsf9id9iuWd+b7/NzPft+MwCgoKCgoKCgozoyktfSJba1ylyjd+ptKavlfrjAdVOtOvap3hO7XeuEGdb1x5wGpdQoY/XhwtKHghW2fcqdIZy0D0bbXOhGYzSDAMYw1qrWnj8ePmp4ib/48cveF1EJQHgiI0wXMyrSkEf1VH8wpeIW4fHTgoCNCBgIRI1DwM7yTs1BHV6eLnSJiHB0IoS6M17sZHgyYmbRp9MTpWZInmmctGdZbKAe2l8sG8krJQbsHFGG28xHqhxtaTkAvPEb15hUprvEgJLFgOiNdfvhZsbPPwt6eiU5A0lfBkZKLW0cadMZcPq3XFVF/EjkHtLSfhFwaNzvAWOA5IAgmWc7YkabbVd4QjkTDRKjCVSCDHaBhZ+obRleAIah2bRPFkkjxNMRwKDxaWVfVoKH6xwXFryT5b9AaRMT+ENqozTUuD4ONzrvRacGQ8PEp0CWDxG6670TMltYgxVIlsxcU6tNXuQx2Tt8noFIHgUM8ZOIbSGNigmYzhhSRyHgxIYB8cp6TUee55c5z1d/uJDoEYrPZXTh4tMorF02yZqRod8naTmRkabrK+32GHpfGgsURVOsOHRNb9AS+yQzKHYHjl4JyPkdgC0UQSrau5SRU9m22D3ZEyMBwKnjRdlrVzvKDQBLYQeXMDJv0idYStxFrXkQRIzDtgQTShc7HDlJ2ZjsWm9JcqBqTxoVvGYXc+JTJnB7dXqQM11EPFdQdH4oioGQxRBd4xfRlizpXTn4E9WVyDuiLyJocXzFBe3S3VIiSjM71H5NLRaE1ryUBREv86W+RngLB2piP1hxYxu75FzOdbUvbND4g5UUQdu4O+RgJmW90tkR5s+DYAdzYiW4zmnOk1cl0QTbI2umZMYjgaQ4uN1XJxOWcQ88XWTBJp27QdMScNsvEvWeoRlNmMGCtquqS6oIa5XL3leSI/xYHCwmXw0C4dbLHVizqTFPyOkIpiimyI2b5bnkTa9uyTzwHjwhHiVQ4+Znq4IUj1QVc1kRRSwJcq6SCtpWIQOyC+qJy61ScX9U8JPYG04Z0qqJTNsw6IXkcyoAFET5lK5d1Mb/qSpAFdCi5qeJuu1tpX32j1TjS2eRHXGbARHzNywt8rE3TPRLBREqm8RyJ4UVv4jgasrbHV032hrOFVrFmjLX2WpCHG7uU3NLNcEltwaLSW+KFi7h2SCWKKrIjZtpOeALav98rngHlnOVoYf0/Q6vDwqNnDR0HbGiJ3dhws91t60shY2E58yeifmqa/ybP/pieBj9Vf52XjX4Sri/QedjeB/qEqvLCCJje/kci8NzB3UbOHM+KJDg83OTYx6Uq5lPOmzSkTJhhOZvuuTBI79iDmTz117OYmL/Emp29opBaSSAhaYIGJxLnT2tq6DCZeSiczPDp+g/gWcbV/hCpOMNzB8i4gJt+S+kwZsxTuXTN1rEBwEO9EKgkPn0Ok3T91XV3LM8nwMbw6JIaIj66zVJFzsf1tHcRLBlzY/kCvUBOCsVwufJ1FZD0YTU1NS+GYnU479XUGqhKJhOhOMRlPoDVWB1XobPZBgxtJKyMWj4daoTul40Gd7idS5g/4zwKHB9MF5/K1u6ei0a5U6BQRSOaTG3PbGdwgfmzxy97mUIstTg8fEBJg+SmHl9tKJCwszV7+XQfLDwirxXKRdtj+eDw+QXQI4Bfb29UutIRydVkOF8T369uQKySagl92w56O7upMPXA+O8uvJmEfDnaWfRkCVqYCCtaHaweO2zTRJYDvYWXwEzevI4i0nf1CghOxOHmaAi9CZ1+/DRYldMcfy+c3+Hz0l9xCAxqycD+HlevLJMQF8Q7Bjy53SiadRDIZD42HnXBzqALRY3fN9zlZbh0J8WhxOp1Pg4i9IKI3IwgXKDfSwvvt+Ljw3T02bJ5b3dUun98FtRa+eywY1+zxbbY+Dv9GFTob61sPoopA6LhEqNyEOuPymt3t78Amza+tPixwUi43t8rJtm+CpH6G7nMUxB+GuvoJPn/sdPMrCxFaTIYrKCgoKCgoKMhgmP8A8hlz7X12SVkAAAAASUVORK5CYII=";
    $content = "<img style='width: 20px; cursor: pointer;' src='{$image}' alt='Show data'>";
    if ($text) {
      $content = '<h4 style="cursor: pointer;text-decoration: underline">Show data</h4>';
    }
    $content = "<span class='spy-obfuscated' data-target='{$key}' data-user='{$user_id}'>{$content}</span>";

    if (p('admin_top') and !cu($user_id)->getSetting('forgotten')) {
      echo $content;
      if (!$this->spy_js_loaded) {
        loadJs('/phive/js/spy_obfuscated_data.js');
        $this->spy_js_loaded = true;
      }
    }
  }

  function switchAction(){
    if($this->canView() === false){
      $this->jsRedirect('/');
      return;
    }

      //phive("Logger")->insertLoad("account_".$this->page);

    // FILTER POST VARIABLES (XSS filter rules taken from phive.php)
    // Sanitize $_POST variables to avoid XSS injection via $_POST
    // TODO There is already some checks on the user information made by Validator.php, improve those by using filter_var with the FILTER_VALIDATE_xxxx and FILTER_SANITIZE_xxxx flags ???
    // Make sure POST's on $_REQUEST are filtered aswell ($_REQUEST contains $_POST aswell)
    foreach ($_POST as $key => $value){
      if(strpos($key, 'password') !== false){
        continue;
      }
      $_POST[$key] = str_replace('"', "&quot;", $value);

      if (isset($_REQUEST[$key])){
          $_REQUEST[$key] = filter_input(INPUT_POST,$key,FILTER_SANITIZE_STRING);
          $_REQUEST[$key] = str_replace('"', "&quot;", $_REQUEST[$key]);
      }
    }

    switch($this->page){
      case 'my-bonuses':
        if(isset($_GET['activate'])){
            phive("Cashier/Aml")->checkBonusToWagerRatio($this->cur_user);
        }
        $this->handleDeleteBonusEntry();
        $this->printMyBonuses();
        break;
        case 'rtp':
            $this->rtp();
            break;
      case 'game-history':
        $gameHistoryData = new GameHistoryData(
                $_GET['provider'] ?? null,
                $_GET['game_id'] ? intval($_GET['game_id']) : null,
                $_GET['page'] ?? null,
                12,
                $_GET['start_date'] ?? null,
                $_GET['end_date'] ?? null,
                $_GET['game_category'] ?? null
        );
        $this->printGameHistory($gameHistoryData);
        break;
      case 'responsible-gambling':
        $this->responsibleGaming();
        break;
      case 'account-history':
        $accountHistoryData = new AccountHistoryData(
            $_GET['provider'] ?? null,
            $_GET['start_date'] ?? null,
            $_GET['end_date'] ?? null,
            $_GET['page'] ?? null
        );
        $this->printAccountHistory($accountHistoryData);
        break;
      case 'sports-betting-history':
        $this->sportsBettingHistory();
        break;
      case 'sptip-betting-history':
        $this->supertipsetBettingHistory();
        break;
      case 'admin':
        if(p('account.admin'))
          $this->printAdmin('start');
        else
          $this->jsRedirect('/');
        break;
      case 'vouchers':
        $this->printVouchers();
        break;
      case 'update-account':
        $this->printSignupUpdateForm(true);
        break;
      case 'transfer':
        $this->drawTransfer();
        break;
      case 'documents':
          $this->printDocuments2();
        break;
      case 'profile':
        $this->printDetails();
        break;
      case 'my-messages':
        $this->printMessages();
        break;
      case 'notifications':
        if(phive()->isMobile()) {
            $this->notificationHistoryMobile();
        } else {
            $this->notificationHistory();
        }
        break;
      case 'weekend-booster':
      case 'my-rainbow-treasure':
      case 'my-winbooster':
        $this->printCashback();
        break;
      case 'my-mobile-achievements' :
        $trophy_box = phive('BoxHandler')->getRawBox('MobileTrophyListBox');
        $trophy_box->init($this->cur_user);
        $trophy_box->printHTML($this->cur_user);
        break;
      case 'my-prizes':
        $trophy_box = phive('BoxHandler')->getRawBox('TrophyListBox');
        $trophy_box->init($this->cur_user);
        $trophy_box->myRewards($this->cur_user);
        break;
      case 'my-achievements':
        $trophy_box = phive('BoxHandler')->getRawBox('TrophyListBox');
        $trophy_box->init($this->cur_user);
        $trophy_box->printHTML($this->cur_user);
        break;
      case 'prize-history':
        $trophy_box = phive('BoxHandler')->getRawBox('TrophyListBox');
        $trophy_box->init($this->cur_user);
        if(phive()->isMobile()) {
            loadCss("/diamondbet/css/" .brandedCss() . "mobile-prize-history.css");
        }
        $trophy_box->rewardHistory($this->cur_user);
        break;
      case 'session-history':
        $session_history_box = phive('BoxHandler')->getRawBox('SessionHistoryBox');
        $session_history_box->init($this->cur_user);
        $session_history_box->printHTML($this->cur_user);
        break;
      case 'xp-progress':
        phive('BoxHandler')->getRawBox('XpProgressBox')->init($this->cur_user)->printHTML();
        break;
      case 'privacy-dashboard':
        $privacy_box = phive('BoxHandler')->getRawBox('PrivacyDashboardBox');
        $privacy_box->printHTML($this->cur_user);
        break;
      case 'wheel-of-jackpots-history' :
        $trophy_box = phive('BoxHandler')->getRawBox('WheelHistoryBox');
        $trophy_box->init($this->cur_user);
        $trophy_box->printHTML($this->cur_user);
        break;
      default:
        $this->printProfile();
        break;
    }
  }

function printCashback($weekly = false)
{
	$params = (new WeekendBoosterService())->getPrintCashbackParams($weekly);
	$this->printCashbackHTML($params);
}

  function printCashbackHTML($params) {
    extract(handleDatesSubmit());
    extract($params);
    $vaultBalance = phive('DBUserHandler/Booster')->getVaultBalance($this->cur_user) / 100;
    $vaultEnabled = phive('DBUserHandler/Booster')->hasBoosterVault($this->cur_user);
    ?>
      <div class="general-account-holder">
          <div class="simple-box pad-stuff-ten">
              <?php if (!$show_special_booster_info): // normal  ?>
                  <h3><?php et('weekend.booster.title') ?></h3>
                  <p><?php et('weekend.booster.info') ?></p>
              <?php else: // "Vault" weekend booster ?>
                  <h3><?php et('weekend.booster.title.vault') ?></h3>
                  <p><?php et('weekend.booster.info.vault') ?></p>
                  <div class="booster-special-box">
                      <div class="booster-special-box__child booster__total-box">
                          <p class="booster__total-amount">
                              <?php echo number_format($vaultBalance, 2) . ' ' . cs() ?>
                          </p>
                          <p class="booster__total-label">
                              <?php et('my.booster.vault.total') ?>
                          </p>
                      </div>
                      <div class="booster-special-box__child booster__actions-box">
                          <div class="booster__actions-opt-out">
                              <label for="booster-optout">
                                  <input type="checkbox" name="booster-optout"
                                         id="booster-optout" <?php echo phive('DBUserHandler/Booster')->optedOutToVault($this->cur_user) ? 'checked="checked"' : '' ?>>
                                  <?php et('my.booster.vault.opt.out.msg') ?>
                              </label>
                          </div>
                          <div>
                              <?php if ($vaultEnabled && $vaultBalance > 0): ?>
                              <button name="booster-getnow" id="booster-getnow" class="btn btn-xl btn-default-xl">
                                  <?php et("my.booster.vault.get.funds.now") ?>
                              </button>
                              <?php endif?>
                          </div>
                      </div>
                  </div>
        <?php endif; ?>
        <?php yearDateForm() ?>
        <?php if($show_special_booster_info && $show_daily_drilldown): ?>
        <h3 style="margin-top:10px"><?php et('my.booster.daily.history') ?> <a class="booster-drilldown booster-drilldown--exit icon icon-vs-chevron-right" href="<?php $this->getDrilldownUrl() ?>"></a></h3>
        <?php endif; ?>
        <?php if (phive('DBUserHandler/Booster')->canUseBooster()): ?>
            <?php
                $interval = 'monthly';
                if (!empty($_GET['year'])) {
                    if (!empty($_GET['month'])) {
                        $start      = new DateTime($_GET['year'] . '-' . $_GET['month'] . '-01');
                        $end        = new DateTime($start->format('Y-m-t'));
                        $interval   = 'daily';
                    } else {
                        $start = new DateTime($_GET['year'] . '-01-01');
                        $end   = new DateTime($_GET['year'] . '-12-31');
                    }
                }
                $stats = phive('DBUserHandler/Booster')->getStats($interval, $start, $end);
            ?>
            <table class="account-tbl zebra-tbl">
                <tr class="zebra-header">
                    <td width="<?php echo $cols[0]; ?>"><?php et('trans.time') ?></td>
                    <td width="<?php echo $cols[1]; ?>"><?php echo t('amount').' '.cs(); ?></td>
                </tr>
                <?php foreach($stats as $i => $row): ?>
                    <tr class="<?php echo $i % 2 == 0 ? 'even' : 'odd' ?>">
                        <?php if ($interval == 'monthly'): ?>
                        <td><?php echo t("month.{$row['month']}") . ' ' . $row['year'] ?></td>
                        <? else: ?>
                        <td><?php
                            $date = new DateTime($row['year'] . '-' . $row['month'] . '-' . $row['day']);
                            echo $date->format('m/d/Y');
                        ?></td>
                        <?php endif; ?>
                        <td><?php echo nfCents($row['earned']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="zebra-header">
                    <td>&nbsp;</td>
                    <td><?php echo nfCents(array_sum(array_column($stats, 'earned'))); ?></td>
                </tr>
            </table>
        <?php /** @todo: Deprecated: Booster Migration */ else: ?>
            <table class="account-tbl">
            <tr>
              <td style="vertical-align: top;">
                <?php if(!$show_daily_drilldown): // normal table ?>
                <table class="zebra-tbl">
                  <col width="<?php echo $cols[0] ?>"/>
                  <col width="<?php echo $cols[1] ?>"/>
                  <tr class="zebra-header">
                    <td><?php et('trans.time') ?></td>
                    <td><?php echo t('amount').' '.cs() ?></td>
                  </tr>

                  <?php $i = 0; foreach($stats as $iso_date => $stat): ?>
                    <tr class="<?php echo $i % 2 == 0 ? 'even' : 'odd' ?>">
                      <td>
                          <?php if($type == 'month') {
                              list($year_number, $month_number) = explode('-', $iso_date);
                              $month_number = strpos($month_number,'0') === 0 ?  substr($month_number,1) : $month_number; // localized strings don't want the leading 0.
                              echo t("month.$month_number")." $year_number";
                          } else {
                              echo phive()->lcDate($iso_date, "%x");
                          } ?>
                      </td>
                      <td>
                        <?php nfCents($stat['gen_loyalty']) ?>
                        <?php if($type == 'day' && $show_special_booster_info && !$show_daily_drilldown): ?> <a class="booster-drilldown icon icon-vs-chevron-right" href="<?php $this->getDrilldownUrl($iso_date) ?>"></a> <?php endif; ?>
                      </td>
                    </tr>
                  <?php $i++; endforeach ?>

                  <tr class="zebra-header">
                    <td>&nbsp;</td>
                    <td><?php nfCents(phive()->sum2d($stats, "gen_loyalty")) ?></td>
                  </tr>
                </table>
                <?php else: // special daily table ?>
                <table class="zebra-tbl">
                    <col width="<?php echo $cols[0] ?>"/>
                    <col width="<?php echo $cols[1] ?>"/>
                    <col width="<?php echo $cols[2] ?>"/>
                    <col width="<?php echo $cols[3] ?>"/>
                    <tr class="zebra-header">
                        <td><?php et('trans.time') ?></td>
                        <td><?php echo t('game.name') ?></td>
                        <td><?php echo t('wins.amount') ?></td>
                        <td><?php echo t('my.booster.amount') ?></td>
                    </tr>
                    <?php $i = 0; foreach($stats as $stat): ?>
                        <tr class="<?php echo $i % 2 == 0 ? 'even' : 'odd' ?>">
                            <td><?php echo $stat['created_at'] ?> GMT</td>
                            <td><?php echo $stat['game_name'] ?></td>
                            <td><?php echo $stat['currency'].' '.nfCents($stat['amount'], true) ?></td>
                            <td><?php echo $stat['currency'].' '.nfCents(abs($stat['transferred_to_vault']), true) // the amount is negative on the table ?></td>
                        </tr>
                    <?php $i++; endforeach ?>
                </table>
                <br>
                <?php $this->p->render('', $this->getDrilldownUrl($_GET['day'], true)) ?>
                <?php endif; ?>
              </td>
            </tr>
            </table>
        <?php endif; ?>
      </div>
    </div>
    <br/>
    <script>
        var addIconAndCenterMsg = function(msg) {
            return `
                <div style="text-align: center;">
                    <img src="/diamondbet/images/<?= brandedCss() ?>weekend-booster/TheWeekendBooster_Logo.png" style="width: 200px;">
                </div>
                <p style="text-align: center;">${msg}</p>
            `;
        }

        // Update user preferences for optin/out
        $('#booster-optout').on('click', function(e) {
            var hasOptedOut = e.target.checked;
            mgJson({
                action: 'update-booster-preference',
                hasOptedOut: hasOptedOut,
                <?php echo privileged() ? "userId: ".$this->cur_user->getId() : '' ?>
            }, function(res) {
                mboxMsg(addIconAndCenterMsg(res.msg));
            });
        });
        // Add Booster to player balance
        function addBoosterToCredit(id) {
            $('#'+id).attr('disabled','disabled');
            mboxClose();
            mgJson({
                action: 'add-booster-to-credit',
                <?php echo privileged() ? "userId: ".$this->cur_user->getId() : '' ?>
            }, function(res) {
                if(res.success){ // waiting for queue response
                    window.location.reload();
                } else { // player not logged in anymore
                    setTimeout(function() {
                        mboxMsg(res.msg, true);
                    }, 1000);
                }
                $('#'+id).removeAttr('disabled');
            });
        }
        $('#booster-getnow').on('click', function(e) {
            var dialogMsg = addIconAndCenterMsg("<?php et('my.booster.vault.get.funds.now.dialog') ?>");
            mboxDialog(dialogMsg, 'mboxClose()', "<?php et('no') ?>", "addBoosterToCredit(this.id)", "<?php et('yes') ?>", false, 280, undefined, undefined, "<?php et('confirm.title') ?>");
        });
        // WS event received when booster-release is completed
        doWs(
            '<?php echo phive('UserHandler')->wsUrl('booster-release') ?>',
            function(e){
                var wsRes = JSON.parse(e.data);
                hideLoader();
                if(wsRes.success) { // we refresh the page when the user close the popup.
                    mboxMsg(addIconAndCenterMsg(wsRes.msg), true, function() { window.location.reload(); });
                } else { // errors
                    mboxMsg(addIconAndCenterMsg(wsRes.msg), true);
                }
            }
        );
    </script>
  <?php }


    function getDrilldownUrl($date = null, $params_only = false) {
        $params = "year={$_GET['year']}&month={$_GET['month']}";
        // this mean we are going down into the daily view
        if(!empty($date)) {
            $params .= "&day=".$date;
        }
        // otherwise we are going back into the month view
        if($params_only) {
            return "&".$params;
        }
        echo phive('UserHandler')->getUserAccountUrl('weekend-booster')."?".$params;
    }

  // remove this too?
  function documentCardStatus($card){
      if($card['active'] == 0)
          return 'deactivated';
      if($card['verified'] == 2)
          return 'rejected';
      if(empty($card['card_scan']))
          return $card['verified'] == 1 ? 'approved' : 'requested';
      else
          return $card['verified'] == 1 ? 'approved' : 'processing';
  }

  function statusBtn($status, $width = 200){ ?>
    <button class="doc-btn <?php echo $status ?>-bkg" style="width: <?php echo $width ?>;"><?php et($status) ?></button>
  <?php }

  function idSectionHeaderTbl($status, $str = 'id', $width = 330, $plain = false){ ?>
    <table class="w-100-pc">
      <tr>
        <td <?php if (!$plain): ?> class="headline-l" <?php endif ?>><?php et("$str.section.headline")?></td>
      </tr>
      <tr>
        <td><button <?php if (!$plain): ?>class="doc-btn <?php if (!$plain) echo $status ?>-bkg"<?php endif ?> style="width: <?php echo $width ?>px;"><?php et($status) ?></button></td>
      </tr>
    </table>
  <?php }

  function idSectionExplanation($str = 'id', $plain = false){ ?>
    <p><?php et($str.'.section.confirm.info') ?></p>
    <?php if (!$plain) :?>
      <div class="headline-default-s"><?php et($str.'.section.addfiles.headline') ?></div>
    <?php else: ?>
      <h3><?php et($str.'.section.addfiles.headline') ?></h3>
    <?php endif ?>
  <?php }


  function isPortable(){
    if($this->site_type == 'mobile' || isIpad())
      return true;
    return false;
  }

    /**
     * @param bool $are_address_details_changed
     * @return array
     */
    function kycCheck(bool $are_address_details_changed)
    {
        $kycResult = [];

        if(!lic('IdScanVerificationEnabled', [cu()], cu())){
            return $kycResult;
        }

        //need to run KYC only specific field change
        if ($are_address_details_changed) {
            //Resseting IDScan and running verification
            $hashed_uid = phive('IdScan')->getHashedUuid($this->cur_user);
            phive('IdScan')->resetVerification($hashed_uid);
            $kycResult = lic('verifyAccountNaturalPerson', [$this->cur_user], $this->cur_user);
        }

        return $kycResult;
    }

  function getSiteType(){
    if($this->isPortable())
      return 'portable';
    return 'normal';
  }

    /**
     * Get the headline for the document
     *
     * @param array $document
     * @return string
     */
    function getDocumentHeadline($document)
    {
        return t("{$document['headline_tag']}.section.headline");
    }


    /**
     * @param array $document
     */
    function displayCrossBrandDocument(array &$document)
    {
        if (!empty($document['expired'])) {
            //set status to requested and pretend the files do not exists (ie we need new ones)
            $document['status'] = Dmapi::STATUS_REQUESTED;
        }

        $tag = $document['tag'];
        $class = phive()->isMobile() ? 'document-mobile' : 'document';
        $headline = $this->getDocumentHeadline($document);
        $status_class = $document['status'] . '-bkg';
        $status_text = ucfirst(t($document['status']));

        ?>
        <div class='simple-box <?= $class ?>' id='<?= $tag ?>'>
            <h3 class='headline-l'><?= $headline ?></h3>
            <h4 id='<?= $tag ?>_status' class='doc-btn document-status <?= $status_class ?>'><?= $status_text ?></h4>
            <?php
            if (lic('shouldDisplayUploadFormForCrossBrandDocument', [$tag, $document['status']], $this->cur_user)) {
                if ($tag === 'idcard-pic') {
                    $this->displayUploadFormIDcard($document);
                } else {
                    $this->displayUploadForm($document);
                }
            }
            ?>
        </div>
        <?php
    }


    /**
     * Todo: put this in a real view file and render it
     */
    function displayDocument($document)
    {
        if(!empty($document['expired'])){
            // Set status to requested and pretend the files don't exist (ie we need new ones).
            $document['status'] = 'requested';
        }

        $tag = $document['tag'];
        $class_mobile = 'document-mobile';
        $class_desktop = 'document';
        // Make tag for credit cards unique because we can have multiple credit card documents on the page
        if($tag == 'creditcardpic') {
            $tag .= '_' . $document['id'];
        }
        $headline = $this->getDocumentHeadline($document);
        ?>
        <div class='simple-box <?php if(phive()->isMobile()): echo $class_mobile; else: echo $class_desktop; endif;?>' id='<?php echo $tag?>'>
            <div class="document__header">
                <h3 class='headline-l'>
                    <?php echo $headline;
                    if(!empty($document['subtag']) && $document['tag'] != 'idcard-pic' && $document['tag'] != 'sourceofincomepic' ) { echo " (#" .$document['subtag'] . ")"; }
                    ?>
                </h3>
                <h4 id="<?php echo $tag; ?>_status" class="doc-btn document-status document-status-btn <?php echo $document['status'] . '-bkg'; ?>">
                    <span class="status"></span>
                    <span>  <?php echo ucfirst(t($document['status'])); ?></span>
                </h4>
            </div>
            <?php
            if(!in_array($document['status'], array('approved'))) {
                $class = '';
                if($this->isPortable()) {
                    $class = '-mobile';
                }

                foreach ($document['files'] as $file) {
                    // Do not show deleted files to the user except expired
                    if($file['deleted_at'] != '' && $file['status'] != 'expired') {
                        continue;
                    }
                    ?>
                    <div>
                        <div class="headline-default-l w-330 filename<?php echo $class?>">
                            <?php
                            if(!empty($file['original_name'])) {
                                echo phive()->ellipsis($file['original_name'], 12);
                            } else {
                                echo 'file.jpg';
                            }
                            ?>
                        </div>
                        <div class="doc-btn filestatus<?php echo $class?> <?php echo $file['status'] . '-bkg'; ?>">
                            <?php et($file['status']); ?>
                        </div>
                    </div>
                    <div class="clear"></div>

                    <?php
                }
            }

            if($document['status'] != 'approved') {
                if ($document['tag'] == 'idcard-pic') {
                    // only show upload buttons for ID cards when the status is requested or rejected
                    if($document['status'] == 'requested' || $document['status'] == 'rejected') {
                        $this->displayUploadFormIDcard($document);
                    }
                } elseif($document['tag'] == 'sourceoffundspic') {
                    $this->displaySourceoffundsForm($document);
                } elseif($document['tag'] == 'sourceofincomepic') {
                    if(in_array($document['status'], ['requested', 'rejected', 'processing'])) {
                        $this->displayUploadFormSourceOfIncome($document);
                    }
                } else {
//                    if($document['tag'] == 'proofofsourceoffundspic') {
//                        $this->displayTickboxForDocument();
//                    }
                    $this->displayUploadForm($document);
                }

            }
            ?>

        </div>
        <?php
    }

    function displayTickboxForDocument()
    {
        ?>
            <?php if(phive()->isMobile()): ?>
            <a id="tickBoxLink" href="/proofofwealthpopup/">
                <?php et('proofofwealth.popup.link'); ?></a>
            <?php else: ?>
            <a id="tickBoxLink" href="javascript:void(0)"
               onclick="showProofOfWealthPopup()">
                <?php et('proofofwealth.popup.link'); ?></a>
            <?php endif;?>

            <br><br>
        <?php
    }

    function displaySourceoffundsForm($document)
    {
        ?>
        <p><?php et($document['headline_tag'].'.section.confirm.info') ?></p>
        <?php if(in_array($document['status'], ['requested', 'rejected'])): ?>
            <a id="download_source_of_funds_form" href="javascript:void(0)"
               onclick="showSourceOfFundsBox('/sourceoffunds1/?document_id=<?php echo $document['id']; ?>')">
                <?php et('download.declaration.form'); ?></a>
        <?php endif; ?>
        <br>
        <br>
        <?php
    }

    /**
     * Todo: put this in a real partial view file and render it
     */
    function displayUploadForm($document)
    {
        $tag = $document['tag'];
        // Make tag for credit cards unique because we can have multiple credit card documents on the page
        if($tag == 'creditcardpic') {
            $tag .= '_' . $document['id'];
        }
        ?>
        <p><?php et($document['headline_tag'].'.section.confirm.info') ?></p>



        <div id="proof_of_address">
            <form action=""
                  method="post" enctype="multipart/form-data">
                <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">

                    <?php
                    if($document['tag'] == 'proofofwealthpic') {
                        $this->displayTickboxForDocument();
                    }
                    ?>

                    <div class="file-upload-title"><?php et('upload.pic'); ?></div>

                    <div class="uploadfields">
                        <input type="hidden" name="document_type" value="<?php echo $document['tag']; ?>">
                        <div class="choose-file-container">
                            <div class="choose-file-field-container">
                                <label for="<?= $tag ?>_choose_file_field" class="choose-file-field"><?php echo et('document.choose.file') ?></label>
                                <input id="<?= $tag ?>_choose_file_field" type="file" name="file" class="hidden" onchange="showFilename('<?= $tag ?>', '')">
                                <span class="hidden document_choose_file"><?php echo et('document.choose.file') ?></span>
                                <span class="hidden document_no_file_chosen"><?php echo et('document.no.file.chosen') ?></span>
                            </div>
                            <span id="<?= $tag ?>_choose_file_filename" style="cursor: default"><?php echo et('document.no.file.chosen') ?></span>
                        </div>
                        <?php if(!empty($document['id'])) { ?>
                            <input type="hidden" name='document_id' value="<?php echo $document['id']; ?>">
                        <?php } ?>
                        <?php if(!empty($document['external_id'])) { ?>
                            <input type="hidden" name='external_id' value="<?php echo $document['external_id']; ?>">
                        <?php } ?>

                    </div>

                    <input type="button" value="<?php et("add.another.file"); ?>" name="add" data-for="proof_of_address" class="add-file-field" id="add-file-field-<?php echo $document['id']; ?>">


                <input id="<?php echo $tag; ?>_submit" type="submit" value="<?php et("submit"); ?>" class="submit" disabled="disabled"/>
            </form>
        </div>
        <?php
    }

    /**
     * Todo: put this in a real partial view file and render it
     */
    function displayUploadFormIDcard($document)
    {
        ?>
        <p><?php et($document['tag'].'.section.confirm.info') ?></p>

        <div class="file-upload-title"><?php et('upload.pic'); ?></div>
        <!--Upload image-->
        <div id="stepChooseIdType">

            <form action=""
                  method="post" enctype="multipart/form-data">

                <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                <input type="hidden" name="document_type" value="<?php echo $document['tag']?>">

                <?php if(!empty($document['id'])) { ?>
                    <input type="hidden" name='document_id' value="<?php echo $document['id']; ?>">
                <?php } ?>

                <label for="idtype"><?php et('select.id.type'); ?>
                    <?php
                        $id_types = array('PASSPORT' => t('Passportidentity.card'), 'ID_CARD' => t('identity.card'), 'DRIVING_LICENSE' => t('driving.license'));
                        $db_select = ['idtype', $id_types, '', array('', t('select.id.type').':')];
                        if (lic('hasDocumentTypeRestriction')) {
                            $id_types = lic('getDocumentTypeAllowed');
                            if (count($id_types) > 0) {
                                $db_select = (count($id_types) == 1) ?
                                    ['idtype', $id_types, key($id_types)] :
                                    ['idtype', $id_types, '', array('', t('select.id.type').':')];
                            }
                        }
                        dbSelect(... $db_select);
                    ?>
                    <div id="idtype_error" class="error-reg"></div>
                </label>

                <div id="image-front-container" style="display:none;">
                    <label for="image-front">
                        <?php et('please.upload.front'); ?>
                        <input type="file" id="image-front" name="image-front" disabled="disabled">
                        <div id="image-front_error" class="error-reg"></div>
                    </label>
                </div>

                <div id="image-back-container" style="display:none;">
                    <label for="image-back">
                        <?php et('please.upload.back'); ?>
                        <input type="file" id="image-back" name="image-back" disabled="disabled">
                        <div id="image-back_error" class="error-reg"></div>
                    </label>
                </div>

                <input id="<?php echo $document['tag']; ?>_submit" type="submit" value="<?php et("submit"); ?>" class="submit" disabled="disabled"/>

            </form>

        </div>
        <?php
    }

    /**
     * Display Upload Form Source Of Income
     *
     * @param $document
     */
    function displayUploadFormSourceOfIncome($document)
    {
        ?>
        <p><?php et($document['tag'].'.section.confirm.info') ?></p>
        <div id="source_of_income">
            <script>
                let expanded = false;

                function showCheckboxes(childId) {
                    var checkboxes = document.getElementById(childId);
                    if (!expanded) {
                        checkboxes.style.display = "block";
                        expanded = true;
                    } else {
                        checkboxes.style.display = "none";
                        expanded = false;
                    }
                }
            </script>
            <form action=""
                  method="post" enctype="multipart/form-data">

                <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                <input type="hidden" name="document_type" value="<?php echo $document['tag']?>">

                <?php if(!empty($document['id'])) { ?>
                    <input type="hidden" name='document_id' value="<?php echo $document['id']; ?>">
                <?php } ?>

                <?php
                $income_types = [
                    'payslip',
                    'pension',
                    'inheritance',
                    'gifts',
                    'tax_declaration',
                    'dividends',
                    'interest',
                    'business_activities',
                    'divorce_settlements', 'gambling_wins',
                    'sales_of_property',
                    'rental_income',
                    'capital_gains',
                    'royalty_or_licensing_income',
                    'other',
                ];

            $options = [];
                foreach ($income_types as $type) {
                    $options[$type] = t('select.income.types.' . $type);
                }

                ?>
                <div class="file-upload-title"><?php et('upload.pic'); ?></div>

                <div class="uploadfields">
                    <?php
                    dbSelect('income_types[]', $options, "", ["", t('select.income.types.options')], '', false, '', false);
                    ?>
                    <div class="choose-file-container">
                        <div class="choose-file-field-container">
                            <label for="source_of_income_choose_file_field" class="choose-file-field"><?php echo et('document.choose.file') ?></label>
                            <input id="source_of_income_choose_file_field" type="file" name="file" class="hidden" onchange="showFilename('source_of_income', '')">
                            <span class="hidden document_choose_file"><?php echo et('document.choose.file') ?></span>
                            <span class="hidden document_no_file_chosen"><?php echo et('document.no.file.chosen') ?></span>
                        </div>
                        <span id="source_of_income_choose_file_filename" style="cursor: default"><?php echo et('document.no.file.chosen') ?></span>
                    </div>
                    <?php if(!empty($document['external_id'])) { ?>
                        <input type="hidden" name='external_id' value="<?php echo $document['external_id']; ?>">
                    <?php } ?>
                </div>

                <input type="button" value="<?php et("add.another.file"); ?>" name="add" data-for="source_of_income" class="add-file-field" id="add-file-field-<?php echo $document['id']; ?>">

                <br/>

                <input id="<?php echo $document['tag']; ?>_submit" type="submit" value="<?php et("submit"); ?>" class="submit" disabled="disabled"/>
            </form>

        </div>
        <?php
    }

    function displayErrors($errors)
    {
        ?>
        <div class="errors">
        <?php
        foreach ($errors as $key => $error) {
            echo t($key, null, false) . ': ' . t($error, null, false) . '<br>';
        }
        ?>
        </div>
        <?php
    }


    /**
     * New interface for when the new backoffice documents page is in use
     *
     * If "restricted_msg_shown" is true then not displaying message again and resetting flag to false;
     */
    function printDocuments2()
    {

        if (!$_SESSION['restricted_msg_shown']) {
            $restriction = $this->cur_user->getDocumentRestrictionType();

            if (!privileged() && !empty($restriction) && $restriction != 'restrict.msg.processing.documents') {
                ?>
                <script>
                    addToPopupsQueue(function () {
                        extBoxAjax('restricted-popup', 'restricted-popup', {
                            msg_title: 'restrict.msg.expired.documents.title',
                            page: 'documents'
                        }, {});
                    });
                </script>
                <?php
            }
        }

        $_SESSION['restricted_msg_shown'] = false;
        // main account verification
        if (!empty($_POST["verify"]) && p('user.verify')) {
            if ($_POST["verify"] == 'verify')
                $this->cur_user->verify();
            else
                $this->cur_user->deleteSetting("verified");
        }

        if (!empty($_GET['create_doc_for'])) {
            phive('Dmapi')->createEmptyDocument($this->cur_user->getId(), $_GET['create_doc_for']);
        }

        if (!empty($_REQUEST['reload'])) {
            jsReloadBase();
        }

        if (!$this->isPortable()) {
            img('documents.header.banner', 960, 308);
        }

        $errors = $this->handleUploads2();

        loadJs("/phive/js/documents.js");

        $this->printDocumentMenuSection($errors);
    }

    function printDocumentMenuSection($errors)
    {
        $verify_status 		= (int)$this->cur_user->getSetting("verified") == 1 ? 'approved' : 'requested';
        ?>
        <div class="account-documents-holder">
            <table class="v-align-top">
              <tr>
                <?php if(method_exists($this, 'printMainMenu') && !$this->isPortable()): ?>
                <td>
                  <div class="doc-left-menu">
                    <?php $this->printMainMenu() ?>
                  </div>
                </td>
                <?php endif ?>
                <td class="documents-cell">
                  <div>
                      <div class="simple-box document-status-section">
                            <?php if (!isPNP()) :?>
                                <div class="headline-default-l"> <?php et('secure.your.account') ?>  </div>
                            <?php endif; ?>
                        <div class="headline-s upload-instructions-headline"> <?php et('upload.instructions.headline') ?> </div>
                        <div class="upload-instructions"><?php et('upload.instructions.html') ?></div>
                        <div class="headline-s document-status-headline"> <?php et('document.statuses.headline') ?> </div>
                        <table class="document-status">
                          <tr>
                            <td>
                                <button class="doc-btn requested-bkg doc-status-btn">
                                    <span class="status"></span>
                                    <span><?php et('requested') ?></span>
                                </button>
                            </td>
                            <td class="document-status-info"> <?php et('documents.requested.info') ?> </td>
                          </tr>
                          <tr>
                            <td> <button class="doc-btn processing-bkg doc-status-btn">
                                    <span class="status"></span>
                                    <span> <?php et('processing') ?></span>
                                </button> </td>
                            <td class="document-status-info"> <?php et('documents.processing.info') ?> </td>
                          </tr>
                          <tr>
                            <td> <button class="doc-btn rejected-bkg doc-status-btn">
                                    <span class="status"></span>
                                    <span><?php et('rejected') ?></span>

                                </button> </td>
                            <td class="document-status-info"> <?php et('documents.rejected.info') ?> </td>
                          </tr>
                          <tr>
                            <td> <button class="doc-btn approved-bkg doc-status-btn">
                                    <span class="status"></span>
                                    <span><?php et('approved') ?></span>
                                </button> </td>
                            <td class="document-status-info"> <?php et('documents.approved.info') ?> </td>
                          </tr>
                        <tr>
                            <td> <button class="doc-btn expired-bkg doc-status-btn">
                                    <span class="status"></span>
                                    <span><?php et('expired') ?></span>
                                </button> </td>
                            <td class="document-status-info"> <?php et('documents.expired.info') ?> </td>
                          </tr>
                        </table>
                      </div>
                      <div class="verification-status-container">
                          <button class="doc-btn <?php echo $verify_status ?>-bkg verification-status">
                              <?php echo t('verification.status') . ((int)$this->cur_user->getSetting("verified") !== 1 ? t('not.verified') : t('verified')) ?>
                          </button>
                      </div>
                  </div>

                <?php
            $user_id = $this->cur_user->getId();
            // get all documents from Dmapi, this includes all requested documents
            $documents = phive('Dmapi')->getUserDocumentsV2($user_id);

            // get all documents that should be cross-brand checked
            $cross_brand_documents = phive('Dmapi')->getCrossBrandDocuments($this->cur_user);

            if (!empty($cross_brand_documents)) {
                $cb_document_tags = array_keys($cross_brand_documents);
                $filtered_cross_brand_documents = array_filter($documents, static function ($elem) use ($cb_document_tags) {
                    return in_array($elem['tag'], $cb_document_tags);
                });
                foreach ($filtered_cross_brand_documents as &$filtered_doc) {
                    $filtered_doc['status'] = $cross_brand_documents[$filtered_doc['tag']]['status'];
                }
                $cross_brand_documents = $filtered_cross_brand_documents;

                $documents = array_filter($documents, static function ($elem) use ($cb_document_tags) {
                    return !in_array($elem['tag'], $cb_document_tags);
                });
            }

            if (is_string($documents)) {
                phive('UserHandler')->logAction(
                    $user_id,
                    $documents,
                    "get-user-documents"
                );
            }

            // Only show upload form if we get a response from the dmapi,
            // so we know if the user has uploaded documents or not,
            // we don't want the user to send additional files if the api is not available
            if ($documents == 'service not available' || $this->dmapi_available == false) {
                ?>
                <div class="errors">
                    <?php et('cannot_get_documents'); ?>
                </div>
                <?php
            } else {

                if (!empty($errors)) {
                    $this->displayErrors($errors);
                }

                ?>
                <div class="document-row">
                <?php
                $i = 1;
                foreach ($documents as $document) {
                    // Until further notice, we are hiding the bankaccountpic document on the front end.
                    // (They still show up in the backoffice)
                    if ($document['tag'] == 'bankaccountpic') {
                        continue;
                        /*if (!isset($document['card_data']['supplier'])) {
                            continue;
                        }

                        if ($document['card_data']['supplier'] !== 'trustly') {
                            continue;
                        }*/
                    }

                    // Internal Documents should not be visible to the user
                    if ($document['tag'] == 'internaldocumentpic') {
                        continue;
                    }

                    $this->displayDocument($document);

                    // Always display 2 documents per row
                    if ($i % 2 == 0) {
                        ?>
                        </div>
                        <div class="document-row">
                        <?php
                    }
                    $i++;
                }
                ?>
                </div>
                <?php
            }

            if (!empty($cross_brand_documents)) {
                $i = 1;
                foreach ($cross_brand_documents as $cb_document) {

                    $this->displayCrossBrandDocument($cb_document);

                    // Always display 2 documents per row
                    if ($i % 2 == 0) {
                        ?>
                        </div>
                        <div class="document-row">
                        <?php
                    }
                    $i++;
                }
                ?>
                </div>
                <?php
            }
            ?>

                </td>
              </tr>
            </table>
        </div>
        <?php
    }


    function printPaymentSection($info, $locstr, $ptype, $plain = false, $str_override = false){
        if(empty($info))
            return;
        ?>
        <div class="<?php if (!$plain) :?>simple-box<?php endif ?> pad-stuff-ten margin-five-bottom">
            <?php $this->picSection("{$ptype}pic", "{$ptype}pic", "{$ptype}pic-verified", "{$ptype}pic", "{$ptype}picfile", "{$ptype}pic_submit", "{$ptype}pic_orig", $plain, $str_override) ?>
            <?php if(!empty($locstr)): ?>
              <p class="<?= $plain?"":"headline-default-s"?>">
                  <?php echo t($locstr).': '.$info ?>
              </p>
            <?php endif ?>
        </div>
    <?php }

  function getAccUserNoSub(){
    $route 			= explode('/', $_GET['dir']);

    list($username, $page) = $this->setup($route);

    $this->username = empty($username) ? (empty($_POST['login_username']) ? $_SESSION['mg_username'] : $_POST['login_username']) : $username;

    if(!empty($this->username))
      $this->cur_user = cu($this->username);

    if(empty($this->cur_user) && $this->uh->getSetting('scale_back') === true){
      $this->uh->unarchiveUser($this->username, true);
      $this->cur_user = cu($this->username);
    }

      if(empty($this->cur_user)){
          jsRedirect('/');
          die(t('you.have.timed.out'));
      }

    $this->page 	= $page;
  }

  function setup($route){

    if($this->isPortable())
      $route = array_values(array_filter($route, function($v){ return $v != 'mobile'; }));

    if(count($route) == 3){
      if(strlen($route[0]) == 2)
        list($lang, $acc, $username) = $route;
      else
        list($acc, $username, $page) = $route;
    }else if(count($route) == 2){
      list($acc, $username) = $route;

      if(strlen($acc) == 2 && $username == 'account'){
        $username = $_SESSION['mg_username'];
      }else if($username == 'signup'){
        $page = $username;
        $username = '';
      }
    }else if(count($route) == 1){
      $username = $_SESSION['mg_username'];
    }else
      list($lang, $acc, $username, $page) = $route;

    if (!empty($_REQUEST['username']) && empty($username)) $username = $_REQUEST['username'];

    return array($username, $page);
  }

  function init(){
    //$this->page 	= array_pop(phive('Pager')->raw_dir);

    $this->loc 		= phive('Localizer');
    $this->mg 		= phive('QuickFire');
    $this->uh 		= phive('UserHandler');
    $this->micro 	= phive('Casino');

    $this->handlePost(array('site_type'));

    $this->getAccUserNoSub();

    $this->setTrTypes();

    $this->p = phive("Paginator");

    $this->datef = $this->site_type == 'mobile' ? 'Y-m-d' : 'Y-m-d H:i:s';
  }

  function canView($perm = 'account.view'){
    $u = cu();
    if(empty($u))
      return false;
    if(strtolower($this->username) == strtolower($_SESSION['mg_username']))
      return true;
    if(strtolower($this->username) == strtolower($_SESSION['local_usr']['email']))
      return true;
    if(strtolower($this->username) == strtolower(cuPlId()))
      return true;
    if(p($perm)){
      phive('IpGuard')->check();
      return true;
    }
    return false;
  }

    /*
  function canView($perm = 'account.view'){
    if(strtolower($this->username) == strtolower($_SESSION['mg_username']))
      return true;
    if(p($perm))
        return true;
    return false;
    }
    */

  function canPrint($p, $func){
    if($this->canDo($p))
      $this->$func();
  }

  function canDo($p){
      if(strtolower($this->username) == strtolower($_SESSION['mg_username']))
          return true;
      if(strtolower($this->username) == strtolower($_SESSION['local_usr']['email']))
          return true;
      if(strtolower($this->username) == strtolower(cuPlId()))
          return true;
      if(p($p))
          return true;
      return false;
  }

    /**
     * @param array $data
     * @param bool $is_splitted_permission_used temporary parameter to support
     *      both /api/v1/user/update-profile-contact and /api/v2/user/update-profile-contact at the same time.
     *      Should be removed when all clients stop using v1 (together with 'else' statement in function body).
     * @return ErrorsOrEmptyResponse
     *
     * @api
     */
    public function checkEditContactDataPermissions(
        array $data,
        bool $is_splitted_permission_used = false
    ): ErrorsOrEmptyResponse {

        if ($is_splitted_permission_used) {
            $user = cu();
            $this->cur_user = $user;

            if (p('change.contact.info')) {
                return new ErrorsOrEmptyResponse();
            }

            $mobile_email_edit_blocked = !phive()->isEmpty($user->hasSetting('change-cinfo-unlock-date'));
            $address_edit_blocked = !phive()->isEmpty($user->hasSetting('change-address-unlock-date'));

            if ($this->isMobileOrEmailChanged($data) && $mobile_email_edit_blocked) {
                return new ErrorsOrEmptyResponse(['change.30day.limit.html']);
            }

            if ($this->areAddressDetailsChanged($data) && $address_edit_blocked) {
                return new ErrorsOrEmptyResponse(['change.30day.limit.html']);
            }

            return new ErrorsOrEmptyResponse();
        } else {
            $user = cu();
            $this->cur_user = $user;

            $mobile_email_edit_blocked = !phive()->isEmpty($user->hasSetting('change-cinfo-unlock-date'));
            $address_edit_blocked = !phive()->isEmpty($user->hasSetting('change-address-unlock-date'));

            return (!$mobile_email_edit_blocked && !$address_edit_blocked) || p('change.contact.info')
                ? new ErrorsOrEmptyResponse()
                : new ErrorsOrEmptyResponse(['change.30day.limit.html']);
        }
    }

    /**
     * @param int $validation_code
     * @return bool
     *
     * @api
     */
    public function isUpdateProfileContactValidationCodeCorrect(int $validation_code): bool
    {
        $user = cu();
        $this->cur_user = $user;

        if (!$this->cur_user->hasSetting('email_code') && !$this->cur_user->hasSetting('sms_code')) {
            return false;
        }

        $is_correct_email_code = $validation_code == $this->cur_user->getSetting('email_code');
        $is_correct_sms_code = $validation_code == $this->cur_user->getSetting('sms_code');

        return $is_correct_email_code || $is_correct_sms_code;
    }

    /**
     * @param int $validation_code
     * @return void
     *
     * @api
     */
    public function setValidationCodeVerifiedSettings(int $validation_code): void
    {
        $user = cu();
        $this->cur_user = $user;

        $is_correct_email_code = $validation_code == $this->cur_user->getSetting('email_code');
        $is_correct_sms_code = $validation_code == $this->cur_user->getSetting('sms_code');

        if ($is_correct_email_code) {
            $this->cur_user->setSetting('email_code_verified', 'yes');
        }

        if ($is_correct_sms_code) {
            $this->cur_user->setSetting('sms_code_verified', 'yes');
        }
    }

    /**
     * @return GetProfileContactValidatePopupResponseData
     *
     * @api
     */
    public function getProfileContactValidatePopupData(): GetProfileContactValidatePopupResponseData
    {
        $user = cu();

        $description = t2('edit-profile.validation-code.description.html', [
            'email' => $user->getAttribute('email'),
            'mobile' => $user->getMobile()
        ]);

        return new GetProfileContactValidatePopupResponseData(
            'msg.title',
            $description,
            'edit-profile.validation-code.placeholder',
            'edit-profile.validation-code.submit-btn',
            'edit-profile.validation-code.resend-btn'
        );
    }

    private function validateContactInfoAndGetErrors(array $fields_to_update): array
    {
        $uh = phive('DBUserHandler');

        $this->patchContactInfoPostData($fields_to_update);
        $req_fields = $uh->getReqFields(array_keys($fields_to_update));
        $err = $uh->validateUser('cinfo', $req_fields);

        $provinces = lic('getProvinces', [], $this->cur_user);
        $forced_province = lic('getForcedProvince', [], $this->cur_user);

        if ($fields_to_update['main_province'] && !isset($provinces[$fields_to_update['main_province']])) {
            $err['province'] = "province";
        }

        if (
            $fields_to_update['building'] &&
            $this->cur_user->getSetting('main_province') !== $forced_province &&
            $this->cur_user->getDataByName('building') === null
        ) {
            $err['building'] = 'building.not-available';
        }

        // Check here for similar users
        $do_fraud = phive('Config')->getValue('lga', 'reg-fraud-check');
        if($do_fraud == 'yes') {
            // update user data $ud to make sure the fraud check will check against the new values
            $ud = $this->cur_user->data;
            foreach ($fields_to_update as $name => $value) {
                $ud[$name] = $value;
            }
            if(p('change.contact.info')){
                // When an admin changes the info, only set an error message
                $similar_users = phive('UserHandler')->getSimilarUsers($ud);
                if($similar_users) {
                    $err[] = 'Cannot update, this account is too similar other user(s): ' . $similar_users;
                }
            } else {
                // When a user changes his info himself, also block the account
                $fraud_check = phive('UserHandler')->lgaFraudCheck($ud);
                if($fraud_check != 'ok') {
                    $err[] = $fraud_check;
                }
            }
        }

        return $err;
    }

    public function getAllowedContactInfoFieldsToUpdate(UserContactData $data, ?array $fields_to_update = null): array
    {
        $allowed_fields = ['email', 'mobile', 'city', 'zipcode', 'address'];

        if (licSetting('require_main_province', $this->cur_user)) {
            $allowed_fields[] = 'main_province';
        }

        if ($data->getBuilding() !== null) {
            $allowed_fields[] = 'building';
        }

        if (
            $data->getBirthdate() !== null &&
            ($this->cur_user->getAttr('dob') === '0000-00-00' || p('users.editall'))
        ) {
            $allowed_fields[] = 'dob';
        }

        if (p('users.editall')) {
            $allowed_fields[] = 'firstname';
            $allowed_fields[] = 'lastname';
        }

        if ($fields_to_update !== null) {
            $allowed_fields = array_intersect($allowed_fields, $fields_to_update);
        }

        $formatted_data = $this->formatUserContactData($data);
        $decoded_fields = [];
        foreach ($allowed_fields as $field) {
            $decoded_fields[$field] = phive()->html_entity_decode_wq($formatted_data[$field]);
        }

        return $decoded_fields;
    }

    private function formatUserContactData(UserContactData $data): array
    {
        return [
            'id' => $data->getId(),
            'email' => $data->getEmail(),
            'mobile' => $data->getMobile(),
            'city' => $data->getCity(),
            'zipcode' => $data->getZipcode(),
            'address' => $data->getAddress(),
            'main_province' => $data->getProvince(),
            'building' => $data->getBuilding(),
            'dob' => $data->getBirthdate(),
            'firstname' => $data->getFirstName(),
            'lastname' => $data->getLastName(),
        ];
    }

    private function mapContactFieldsToUserContactDataFields(array $data): array
    {
        $data['birthdate'] = $data['dob'];
        $data['province'] = $data['main_province'];

        unset($data['dob'], $data['main_province']);

        return $data;
    }

    /**
     * @param array $fields_to_update
     * @param bool $is_api
     *
     * @return \Laraphive\Support\DataTransferObjects\ErrorsOrEmptyResponse
     *
     * @api
     */
    public function updateContactInfo(array $fields_to_update, bool $is_api = false): ErrorsOrEmptyResponse
    {
        if ($this->cur_user === null) {
            $this->cur_user = cu();
        }

        if (empty($fields_to_update)) {
            return new ErrorsOrEmptyResponse();
        }

        $err = $this->validateContactInfoAndGetErrors($fields_to_update);
        $is_mobile_or_email_changed = $this->isMobileOrEmailChanged($fields_to_update);
        $are_address_details_changed = $this->areAddressDetailsChanged($fields_to_update);

        if (!$is_api && !p('users.editall') && $is_mobile_or_email_changed) {
            $validation_code = $_POST['validation_code'];

            if (!$this->isUpdateProfileContactValidationCodeCorrect($validation_code)) {
                $err['validation_code'] = 'validation_code';
            } else {
                $this->setValidationCodeVerifiedSettings($validation_code);
            }
        }

        $is_mobile_changed = isset($fields_to_update['mobile'])
            && $fields_to_update['mobile'] !== $this->cur_user->getAttr('mobile');

        if ($is_mobile_changed) {
            $calling_code = phive('Mosms')->extractCountryCodeFromMobile($fields_to_update['mobile']);

            if ($calling_code === null) {
                $err['calling-code'] = 'calling-code.unknown';
            } else {
                $fields_to_update['calling_code'] = $calling_code;
            }
        }

        $actor_id = cuAttr('id');
        $poa = 'addresspic';

        if ($are_address_details_changed && $this->cur_user->data['id'] == $actor_id) { // change of address -> require new proof of address doc

            $user = cu($this->cur_user->data['id']);
            $status_rejected = Dmapi::STATUS_REJECTED;
            $remote_user_id = $user->getRemoteId();
            $remote_brand = getRemote();
            $local_brand = getLocalBrand();

            // If the address was changed by the user itself, send a call to Dmapi to reject the file(s), and set the document to 'requested'

            $doc_type_setting_name = Phive('Dmapi')->getSettingNameForDocumentType($poa);
            linker()->updateDocumentSetting($user, $doc_type_setting_name, $status_rejected, $local_brand);

            if ($user->shouldSyncCDDStatus()) {
                $user->updateCDDFlagOnDocumentStatusChange();
                $user->logCDDActions("Address change");
            }

            phive('Dmapi')->rejectAllFilesFromDocument($this->cur_user->data['id'], $poa);
            toRemote(
                $remote_brand,
                'rejectAllDocumentsOnRemote',
                [$poa, $remote_user_id]
            );

            $this->cur_user->unVerify();
            phive('UserHandler')->logAction($this->cur_user,
                "Invalidated old proof of address pic, because of address change", "profile-update", true);
            phive('UserHandler')->logAction($this->cur_user, "Unverified player, because of address change",
                "profile-update", true);
            $this->cur_user->addComment("Player needs to provide proof of address for the new address // system");
        }

        phive("UserHandler")->logAction(
            $this->cur_user,
            "Attempted to update profile with: ".var_export($fields_to_update, true),
            "profile-update-attempt",
            true
        );

        if(empty($err)){

            if (isset($fields_to_update['email']) && strpos($this->cur_user->getUsername(), '@') !== false) {
                $fields_to_update['username'] = $fields_to_update['email'];
            }

            if(p('change.contact.info')){
                $kycResult = $this->kycCheck($are_address_details_changed);

                if($kycResult['idscan'] == 'check'){
                    //Saving temporary data
                    phive('IdScan')->setTemporaryData('contact', $fields_to_update, $this->cur_user);

                    //Requesting IDScan verification immediately. Block from other actions.
                    //$fields_to_update will be saved later after IDScan verification
                    $this->cur_user->setSetting('idscan_block', 1);
                    if(! $is_api) {
                        jsReloadBase();
                    }
                } else {
                    $this->cur_user->setContactInfo($fields_to_update);

                    phive("UserHandler")->logAction(
                        $this,
                        "Updated profile with: " . var_export($fields_to_update, true),
                        "profile-update-by-admin",
                        true);
                }

            } else {
                $this->cur_user->deleteSetting('sms_code');
                $this->cur_user->deleteSetting('email_code');

                $can_update_contact_info = phive()->isEmpty($this->cur_user->getSetting('change-cinfo-unlock-date'))
                    || phive()->isEmpty($this->cur_user->getSetting('change-address-unlock-date'));

                if (!$can_update_contact_info) {
                    return new ErrorsOrEmptyResponse($err);
                }

                $kycResult = $this->kycCheck($are_address_details_changed);

                if ($kycResult['idscan'] == 'check') {
                    //Saving temporary data
                    phive('IdScan')->setTemporaryData('contact', $fields_to_update, $this->cur_user);

                    //Requesting IDScan verification immediately. Block from other actions.
                    //$fields_to_update will be saved later after IDScan verification
                    $this->cur_user->setSetting('idscan_block', 1);

                    if(! $is_api) {
                        jsReloadBase();
                    }

                    return new ErrorsOrEmptyResponse($err);
                } else {
                    $newContactInfo = phive('DBUserHandler')->updateContactInformation(
                        $this->cur_user->getId(),
                        $fields_to_update
                    );

                    $err = array_merge($err, $newContactInfo->getErrors());
                }

                if (! empty($err)) {
                    return new ErrorsOrEmptyResponse($err);
                }

                if ($is_mobile_or_email_changed) {
                    $this->cur_user->setSetting('change-cinfo-unlock-date', phive()->hisMod('+30 day'));
                }

                if ($are_address_details_changed) {
                    $this->cur_user->setSetting('change-address-unlock-date', phive()->hisMod('+30 day'));
                }
            }
        }

        return new ErrorsOrEmptyResponse($err);
    }

    private function isMobileOrEmailChanged(array $fields_to_update): bool
    {
        $email = $fields_to_update['email'] ?? null;
        $mobile = $fields_to_update['mobile'] ?? null;

        $is_email_changed = $email !== null && $email !== $this->cur_user->getAttr('email');
        $is_mobile_changed = $mobile !== null && $mobile !== $this->cur_user->getAttr('mobile');

        return $is_email_changed || $is_mobile_changed;
    }

    private function areAddressDetailsChanged(array $fields_to_update): bool
    {
        $city = $fields_to_update['city'] ?? null;
        $zipcode = $fields_to_update['zipcode'] ?? null;
        $address = $fields_to_update['address'] ?? null;
        $building = $fields_to_update['building'] ?? null;
        $province = $fields_to_update['main_province'] ?? null;

        $is_city_changed = $city !== null && $city !== $this->cur_user->getAttr('city');
        $is_zipcode_changed = $zipcode !== null && $zipcode !== $this->cur_user->getAttr('zipcode');
        $is_address_changed = $address !== null && $address !== $this->cur_user->getAttr('address');
        $is_building_changed = $building !== null && $building !== $this->cur_user->getDataByName('building');
        $is_province_changed = $province !== null && $province !== $this->cur_user->getDataByName('main_province');

        return $is_city_changed || $is_zipcode_changed || $is_address_changed || $is_building_changed || $is_province_changed;
    }

    /**
     * Patch the POST data.
     * Required since phive('DBUserHandler')->validateUser() method uses POST data.
     *
     * @param array $fields_to_update
     * @return void
     */
    private function patchContactInfoPostData(array $fields_to_update): void
    {
        $_POST = $_REQUEST = array_merge($_POST, $fields_to_update);

        $_POST['user_id'] = $this->cur_user->getId();

        if (isset($_POST['dob'])) {
            $dateTime = new DateTime($_POST['dob']);
            $_POST['birthdate'] = $_REQUEST['birthdate'] = $dateTime->format('d');
            $_POST['birthmonth'] = $_REQUEST['birthmonth'] = $dateTime->format('m');
            $_POST['birthyear'] = $_REQUEST['birthyear'] = $dateTime->format('Y');
        }
    }

    function printSignupUpdateForm($update = true)
    {
        $err = [];

        $user_contact_data = (new UserContactDataFactory())->create(
            $this->cur_user->getId(),
            $this->mapContactFieldsToUserContactDataFields(
                $this->substituteNonEditableFieldsWithAttrs($this->cur_user, $_POST)
            )
        );

        if (!empty($_POST['submit_contact_info'])) {
            if (p('users.editall')) {
                unset($_SESSION['show_contact_form']);
            }
            $fields_to_update = $this->getAllowedContactInfoFieldsToUpdate($user_contact_data, array_keys($_POST));

            $newContactInfo = $this->updateContactInfo($fields_to_update);
            $err = $newContactInfo->getErrors();
        } else if (!empty($_POST['validate_contact_info'])) {
            $fields_to_update = $this->getAllowedContactInfoFieldsToUpdate($user_contact_data, array_keys($_POST));
            $err = $this->validateContactInfoAndGetErrors($fields_to_update);
        } else if(!empty($_POST['submit_accinfo'])){
            $requestData = UpdateProfileAccountRequestFactory::createFromArray($_REQUEST);

            $updateProfileAccountService = UpdateProfileAccountServiceFactory::create($this->cur_user);

            $updateProfileAccountService->updateProfileAccount($requestData);
        }else if(!empty($_POST['submit_password'])){
            $response = phive('DBUserHandler')->updateProfilePassword($update);
            $err = $response['err'];
            $user_id = $response['user_id'];
        }elseif($_POST['submit_2FA']) {
            $this->cur_user->setSetting('2-factor_authentication', $_REQUEST['2-factor_authentication'] == 'on' ? 1 : 0);
        } elseif (!empty($_POST['submit_terms_and_conditions']) || !empty($_GET['submit_terms_and_conditions'])) {
            $url = phive('UserHandler')->getUserAccountUrl('update-account');
            $redirect_url = phive('UserHandler')->getUserAccountUrl('update-account');

            if ($this->cur_user->hasSetting('tac_block_sports') && empty($_GET['checked_stc'])){
                $redirect_url .= '?checked_stc=1&submit_terms_and_conditions=1';

                $this->jsRedirect($url . '?showstc=1&tc-redirect=' . urlencode($redirect_url));
            }

            if ($this->cur_user->hasSetting('tac_block') && empty($_GET['checked_tac'])){
                $redirect_url .= '?checked_stc=1&checked_tac=1&submit_terms_and_conditions=1';

                $this->jsRedirect($url . '?showtc=1&tc-redirect=' . urlencode($redirect_url));
            }

            if ($this->cur_user->hasSetting('bonus_tac_block')){
                $this->jsRedirect($url . '?showbtc=1&tc-redirect=' . urlencode($redirect_url));
            }

            $this->jsRedirect($url);
        }

    if(!empty($_POST['dep_lim_submit']) || !empty($_POST['lock_submit'])){

      if(!empty($_POST['dep_lim_submit']) && !empty($_POST['dep_lim']) && !empty($_POST['dep_period'])){
        phive("UserHandler")->logAction($this->cur_user, "Set deposit limit to {$_POST['dep_lim']} for {$_POST['dep_period']} days", "profile-limit", true);
        $this->cur_user->setSetting('dep-period', $_POST['dep_period']);
        $this->cur_user->setSetting('dep-lim', $_POST['dep_lim'] * 100);
        if(!p('account.admin'))
          $this->cur_user->setSetting('dep-lim-unlock-date', phive()->hisMod('+48 hour'));
      }

      if(!empty($_POST['lock_submit']) && !empty($_POST['num_days'])){
        $num_days = (int)$_POST['num_days'] > 3000000 ? 3000000 : (int)$_POST['num_days'];

        phive('DBUserHandler/RgLimitsActions')->setUserObject($this->cur_user)->lock($num_days);

        $this->jsRedirect('?signout=true');
      }
    }

    if(empty($err) && (!empty($_POST['submit']) || !empty($_POST['submit_contact_info']) || !empty($_POST['submit_password']))){
      if(!$update){
        phive('UserHandler')->login($_POST['username'], $_POST['password']);
        $this->jsRedirect(phive('UserHandler')->getUserAccountUrl());
      }else{
        $this->cur_user->setAttrsToSession();
        $this->printForm($err, $update, $user_contact_data);
      }
    }else
      $this->printForm($err, $update, $user_contact_data);

  }

    /**
     * @param DBUser $user
     * @param array $changed_fields
     * @return array
     *
     * @api
     */
    public function substituteNonEditableFieldsWithAttrs(DBUser $user, array $changed_fields): array
    {
        $all_contact_fields = [
            'email',
            'mobile',
            'city',
            'main_province',
            'zipcode',
            'address',
            'building',
            'dob',
            'firstname',
            'lastname',
        ];

        $editable_contact_fields = lic('getLicSetting', ['editable_user_profile_fields'], $this->cur_user);

        $email_mobile_fields = ['email', 'mobile'];
        $email_mobile_edit_disabled = !p('admin_top')
            && !phive()->isEmpty($user->getSetting('change-cinfo-unlock-date'));

        $address_info_fields = ['city', 'zipcode', 'address', 'building', 'main_province'];
        $address_info_edit_disabled = !p('admin_top')
            && !phive()->isEmpty($user->getSetting('change-address-unlock-date'));

        $admin_editable_fields = ['firstname', 'lastname', 'dob'];

        $result = [];
        foreach ($all_contact_fields as $name) {
            $should_skip_editing = ($email_mobile_edit_disabled && in_array($name, $email_mobile_fields, true))
                || ($address_info_edit_disabled && in_array($name, $address_info_fields, true))
                || (!p('users.editall') && in_array($name, $admin_editable_fields, true))
                || ($editable_contact_fields && !in_array($name, $editable_contact_fields));

            if ($should_skip_editing) {
                $result[$name] = $user->getDataByName($name);
                continue;
            }

            $result[$name] = array_key_exists($name, $changed_fields)
                ? $changed_fields[$name]
                : $user->getDataByName($name);
        }

        return $result;
    }

  function showActivateBtn(){
    if(!$this->cur_user->isBlocked() || $this->cur_user->isSuperBlocked())
      return false;
    $lock_reason = $this->uh->getBlockReason($this->cur_user->getId());
    if(in_array($lock_reason, [0, 1, 2, 5]) && p('normal.activate.permission'))
      return true;
    return false;
  }

  function showBlockBtn($unlock_date, $block_value){
    if($block_value == 'activate' && !p('user.unlock'))
      return false;
    if((!empty($unlock_date) || $this->cur_user->isSuperBlocked()) && !p('user.super.unlock'))
      return false;
    if(p('user.block') || p('user.super.block'))
      return true;
    return false;
  }


  function doKycInOut($alts, $func){
      foreach($alts as $alt => $sub_alts){
          if(empty($sub_alts))
              $func($alt, $alt, $sub_alt);
          else{
              foreach($sub_alts as $sub_alt)
                  $func($sub_alt, $alt, $sub_alt);
          }
      }

  }


  /**
   * This function has been cleaned up. Everything related to admin stuff has been removed
   *
   * @param bool $isApi
   *
   * @return array
   */
  function handleUploads2(bool $isApi = false): array
  {
    $errors = array();
    $redirect = false;
    $uh = phive('UserHandler');

    // From now on this will send ALL documents to Dmapi, also Credit card documents
    if($_POST['document_type'] == 'idcard-pic' && empty($_FILES)) {
        $errors['idcard-pic.section.headline'] = 'register.err.idpic.error';
    }

    if(!empty($_FILES)) {

        if (isset($_POST['id_type'])){
            $_POST['id_type'] = phive()->rmNonAlphaNums($_POST['id_type']);
        }

        $document_type = $_POST['document_type'];
        $document_type_file = $document_type . 'file';

        if($document_type == 'addresspic') {  // addressfile is different, not addresspicfile as the other
            $document_type_file = 'addressfile';
        }
        $error_key = $document_type . '.section.headline';

        $errors = phive('Filer')->validateUploadedFiles($error_key);

        if(!empty($errors)) {
            return $errors;
        }

        // ID images are handled differently then other images
        if($_POST['document_type'] == 'idcard-pic') {
            // send pictures to DMAPI
            $actor_id = cuAttr('id');
            $id_type = $_POST['idtype'];
            $data = phive('Dmapi')->handlePostedIDV2($this->cur_user->getId());

            $user_data = $this->cur_user->getData();
            $country_iso2 = $user_data['country'];

            // convert country code to iso3
            $country = phive('Cashier')->getIso3FromIso2($country_iso2);

            if(!empty($data['errors'])) {
                $errors[$error_key] = implode(', ', $data['errors']);

                return $errors;
            }

            $checkedImage =  phive('Dmapi')->checkImage($data['files']);

            $result = phive('Dmapi')->addMultipleFilesToDocument($_POST['document_id'], $checkedImage, $id_type, $country, $this->cur_user->getId());

            // show message when dmapi is not responding
            if(empty($result)) {
                $this->dmapi_available = false;
            }

            if(!empty($result['errors'])) {
                $errors[$error_key] = implode(', ', $result['errors']);
            } else {
                //Updating expiry date to null. Later date will be set by admin
                phive('Dmapi')->updateDocumentColumns(array('expiry_date'=>null), $_POST['document_id'], $this->cur_user->getId());
                $redirect = true;
            }
        } elseif ($_POST['document_type'] == 'sourceofincomepic') {
            // send pictures to DMAPI
            $income_types = json_encode(['types' => $_POST['income_types']]);
            $data = phive('Dmapi')->handlePostedDocument($this->cur_user->getId());

            if (count(array_filter($_POST['income_types'])) != count($_FILES)) {
                $data['errors'][] = 'error.income.type.not.selected';
            }

            foreach ($data['files'] as $key => $file) {
                $data['files'][$key]['tag'] = $_POST['income_types'][$key];
            }

            $user_data = $this->cur_user->getData();
            $country_iso2 = $user_data['country'];

            // convert country code to iso3
            $country = phive('Cashier')->getIso3FromIso2($country_iso2);

            if(!empty($data['errors'])) {
                $errors[$error_key] = implode(', ', $data['errors']);

                return $errors;
            }
            $checkedImage =  phive('Dmapi')->checkImage($data['files']);

            $result = phive('Dmapi')->addMultipleFilesToDocument($_POST['document_id'], $checkedImage, $income_types, $country, $this->cur_user->getId());

            // show message when dmapi is not responding
            if(empty($result)) {
                $this->dmapi_available = false;
            }

            if(!empty($result['errors'])) {
                $errors[$error_key] = implode(', ', $result['errors']);
            } else {
                $redirect = true;
            }
        }
        else {
            // some other file was uploaded

            // send pictures to DMAPI
            $data = phive('Dmapi')->handlePostedDocument($this->cur_user->getId());

            if(!empty($data['errors'])) {
                $errors[$error_key] = implode(', ', $data['errors']);
            } else {

                $checkedImage =  phive('Dmapi')->checkImage($data['files']);
                // Because we have empty documents already, we only have to add files to the document
                $result = phive('Dmapi')->addMultipleFilesToDocument($_POST['document_id'], $checkedImage, '', '', $this->cur_user->getId());

                // show message when dmapi is not responding
                if(empty($result)) {
                    $this->dmapi_available = false;
                }
                elseif(!empty($result['errors'])) {

                    $errors[$error_key] = $document_type . '.error';
                } else {
                    $redirect = true;
                }
            }
        }
    } else if ($isApi) {
        return ['The file field is required'];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
        lic('onUploadDocument', [$this->cur_user], $this->cur_user);
    }

    if($redirect) {
        phive('DBUserHandler')->doCheckUnrestrict($this->cur_user, $document_type);
        if (!$isApi) {
            // reload document page to prevent duplicate form submissions
            jsReloadBase();
        }
    }

    return $errors;

  }

  // TODO henrik remove
  function printAdmin($action = '', $showstuff = true, $get_pendings = true, $return = false, $handle_post = true){
      return;

    $action = empty($action) ? $this->page : $action;
    if ($get_pendings)
      $pending = array_pop(phive('Cashier')->getPendingsUser($this->cur_user->getId(), "= 'approved'", "LIMIT 0,1", '', "AND deducted_amount = 0"));

    $other_settings = $other = array("sub_aff_no_neg_carry", "casino_loyalty_percent", "bonus_block", "max_thirty_withdrawal", "permanent_dep_lim", "permanent_dep_period", 'free_deposits', 'free_withdrawals', 'withdraw_period', 'withdraw_period_times', 'lock-date', 'affiliate_admin_fee', 'dep-limit-playblock');

    if ($handle_post) {

      if(!empty($pending))
        $next_free_date = phive()->hisMod('+7 day', $pending['timestamp']);

      if(!empty($_REQUEST['verify_email']))
        $this->cur_user->setSetting('email_code_verified', 'yes');

      if(!empty($_REQUEST['unlock-date-7']))
        $this->cur_user->setSetting('unlock-date', $_REQUEST['unlock-date-7']);

      if(!empty($_POST["block"])){
        $this->cur_user->deleteSetting('unlock-date');
        $this->cur_user->deleteSetting('lock-date');
        $this->cur_user->deleteSetting('lock-hours');

        if($_POST["block"] == 'block'){
            phive('UserHandler')->addBlock($this->cur_user->data['username'], 3);
            //TODO why is the below line commented out? Shouldn't we kickout people when they're blocked?
            //phive('UserHandler')->logoutUser($this->cur_user->getId());
          //if (p('admin_top')) $this->jsRedirect('?username='.$_GET['username']);
        }else{
            if (!$this->cur_user->hasSetting('indefinitely-self-excluded')) {
                phive('UserHandler')->removeBlock($this->cur_user);
                //$this->cur_user->setAttribute("active","1");
                //$this->cur_user->deleteSettings('excluded-date', 'unexclude-date');
            }
        }
      }

      foreach(array('cur-lgaloss-lim', 'cur-lgawager-lim', 'cur-lgatime-lim') as $lgas){
        if(empty($_REQUEST[$lgas]) == false)
          $this->cur_user->deleteSetting($lgas);
      }

      if(!empty($_POST['toggle_play_block'])){
        $play_block = (int)$this->cur_user->getSetting("play_block");
        if($play_block === 1)
          $this->cur_user->deleteSetting('play_block');
        else
          $this->cur_user->setSetting('play_block', 1);
      }

        if(!empty($_POST["remove_bonus_fraud_flag"]))
            $this->cur_user->deleteSetting('bonus-fraud-flag');

        if(!empty($_POST["remove_ccard_fraud_flag"])){
            phive('Cashier')->fraud->removeCardFraudFlag($this->cur_user);
            //$this->cur_user->deleteSetting('ccard-fraud-flag');
        }

        if(!empty($_POST["remove_ccard_fraud_flag_permanently"])){
            $this->cur_user->deleteSetting('ccard-fraud-flag');
            $this->cur_user->setSetting('no-ccard-fraud-flag', 1);
        }


      if(!empty($_POST["delete_phonedate"])){
        $this->cur_user->deleteSetting('phoned-date');
      }

      if(!empty($_POST["update_phonedate"])){
        $this->cur_user->setSetting('phoned-date', phive()->hisNow());
      }

      if(!empty($_POST["super-block"])){
        $this->cur_user->superBlock();
        $this->cur_user->superBlockRemote();
      }

      if(!empty($_POST["verify"])){
        if($_POST["verify"] == 'verify')
          $this->cur_user->verify();
        else
          $this->cur_user->deleteSetting("verified");
      }

      if(!empty($_POST["phoneVerify"])){
        if($_POST["phoneVerify"] == 'verify phone'){
          $this->cur_user->setAttribute("verified_phone", 1);
        }else
          $this->cur_user->setAttribute("verified_phone", 0);
      }

      if(!empty($_POST["clear_ips"])){
        $ip = $this->cur_user->getAttribute("reg_ip");
        phive('SQL')->query("UPDATE users SET reg_ip = '' WHERE reg_ip = '$ip'");
      }

      if(!empty($_POST["update_majority_date"]))
        $this->cur_user->setSetting('majority_date', phive()->hisNow());

      if(!empty($_POST["show_euteller"]))
        $this->cur_user->setSetting('show_euteller', 1);

      if(!empty($_POST["show_bank"]))
        $this->cur_user->setSetting('show_bank', 1);

        if(!empty($_POST["add_country"]))
            $this->cur_user->addLoginCountry($_POST["add_country"]);

      if(!empty($_POST["add_comment"])) {
        foreach(array('limits', 'complaint', 'phone_contact') as $ttype){
          if($_POST[$ttype] == 'on'){
            $tag = $ttype;
            break;
          }
        }
        $this->cur_user->addComment($_POST["add_comment"], !empty($_POST['sticky'])? 1 : 0, $tag);
      }

      $dclimits = array("dc_daily_limit" => 'in-dc-day-limit', "dc_num_quick_limit" => 'n-quick-deposits-limit');

      foreach($dclimits as $key => $setting){
        if(isset($_POST[$key])){
          if($_POST[$key] === '')
            $this->cur_user->deleteSetting($setting);
          else
            $this->cur_user->setSetting($setting, $_POST[$key]);
        }
      }

      if(!empty($_POST["inout_value"]) && !empty($_POST["inout_method"]) && !empty($_POST["inout_type"]))
        $this->cur_user->setSetting("{$_POST["inout_method"]}-{$_POST["inout_type"]}-limit", $_POST["inout_value"], true, 0, 'change_inout_limit');

      $settings = array("delete_psol", "delete_country", "delete_limit", "vip_percent",
        "vip_threshold", "rakeback_percent", "delete_poker", "poker_vip_points", "delete_psolution",
        "mb_email", "net_account", "ppal_email");

      foreach ($settings as $s) {
        if(!empty($_POST[$s]) || $_POST[$s] === "0"){
          if(substr($s, 0, 6) == "delete"){
            $this->cur_user->deleteSetting($_POST[$s]);
          }else{
            $this->cur_user->setSetting($s, $_POST[$s]);
          }
        }
      }

      if (!empty($_POST["delete_comment"])) {
        $this->cur_user->deleteComment($_POST["delete_comment"]);
      }

      if(!empty($_POST['submit_other'])){

        $other_max = phive("Config")->getByTagValues('max-user-other');
        $other_min = phive("Config")->getByTagValues('min-user-other');

        foreach($other_settings as $s) {
          if($_POST[$s] == '')
            $this->cur_user->deleteSetting($s);
          else if($_POST[$s] == 0){
            $this->cur_user->setSetting($s, 0);
          }else{
            if(!empty($other_max[$s]))
              $other_val = min($other_max[$s], $_POST[$s]);
            else if(!empty($other_min[$s]))
              $other_val = max($other_min[$s], $_POST[$s]);
            else
              $other_val = $_POST[$s];
            $this->cur_user->setSetting($s, $other_val);
          }
        }
      }
    }
    $block_value 	= $this->cur_user->getAttribute("active") === "0" ? 'activate' : 'block';
    $verify_value 	= (int)$this->cur_user->getSetting("verified") !== 1 ? 'verify' : 'unverify';
    $verifyPhone_value 	= (int)$this->cur_user->getAttribute("verified_phone") !== 1 ? 'verify phone' : 'unverify phone';
    $settings 		= $this->cur_user->getAllSettings();

    $countries 		= array();
    $comments 		= array();
    $limits		= array();
    $psolutions		= array();
    $poker		= array();
    $comments     = $this->cur_user->getAllComments();

    foreach($settings as $s){
      if(strpos($s['setting'], 'login-allowed-') !== false )
        $countries[] = $s;

      if(strpos($s['setting'], '-limit') !== false )
        $limits[] = $s;

      if(in_array($s['setting'], array('mb_email', 'net_account', 'ppal_email')))
        $psolutions[] = $s;

      if(in_array($s['setting'], array('vip_percent', 'vip_threshold', 'rakeback_percent', 'poker_vip_points')))
        $poker[] = $s;

    }

    $light_attrs = array();
    if ($handle_post) {
      if(p('change.attributes.light') && !empty($_POST['submit_attributes_light'])){
        foreach($light_attrs as $attr)
          $this->cur_user->setAttribute($attr, $_REQUEST[$attr]);
      }
    }
    $super_blocked = phive()->isEmpty($this->cur_user->getSetting('super-blocked')) ? 0 : 1;
    $unlock_date   = $this->cur_user->getSetting('unlock-date');
    $lock_date     = $this->cur_user->getSetting('lock-date');

    $welcome_dep         = phive('Cashier')->getTransaction($this->cur_user, array('description' => '#welcome.deposit'));
    $welcome_dep_removed = phive('Cashier')->getTransaction($this->cur_user, array('description' => '#welcome.deposit.removed'));
    $bet                 = phive('SQL')->loadAssoc("SELECT * FROM bets WHERE user_id = {$this->cur_user->getId()} LIMIT 0,1");

    if ($handle_post) {
      if(!empty($_REQUEST['remove_welcome']) && empty($welcome_dep_removed)){
        phive('Cashier')->transactUser($this->cur_user, -$welcome_dep['amount'], "#welcome.deposit.removed", null, null, 15, false);
        phive('UserHandler')->logAction($this->cur_user, cu()->getUsername()." removed welcome deposit.", 'money_transfer');
        $welcome_dep_removed = true;
      }
    }
    $params = array(
        'super_blocked' => $super_blocked,
        'unlock_date' => $unlock_date,
        'lock_date' => $lock_date,
        'block_value' => $block_value,
        'verify_value' => $verify_value,
        'verifyPhone_value' => $verifyPhone_value,
        'next_free_date' => $next_free_date,
        'bet' => $bet,
        'welcome_dep' => empty($welcome_dep)? $welcome_dep : "",
        'welcome_dep_removed' => empty($welcome_dep_removed)? $welcome_dep_removed : "",
        'countries' => $countries,
        'comments' => $comments,
        'limits' => $limits,
        'psolutions' => $psolutions,
        'poker' => $poker,
        'settings' => $settings,
        'other_settings' => $other_settings,
        'other' => $other,
        'action' => $action,
        'pending' => $pending,
        'dclimits' => $dclimits,
        'light_attrs' => $light_attrs
      );

    if ($return) return $params;
    if ($showstuff)
      $this->printAdminHTML($params);
  }
  function printAdminHTML($params = array()) {
      extract($params);
    ?>
    <div class="news-container" style="position: absolute; z-index: 500;">
      <div class="news-middle" style="margin-top: 0px;">
        <div class="uadmin-menu">
          <ul>
            <li>
              <a href="/admin/addbonus/?user_id=<?php echo $this->cur_user->getId() ?>">Bonuses</a>
            </li>
            <li>
              <a href="/admin/addreward/?user_id=<?php echo $this->cur_user->getId() ?>">Add Reward</a>
            </li>
            <li>
              <a href="/admin/addtrophy/?user_id=<?php echo $this->cur_user->getId() ?>">Add Trophy</a>
            </li>
            <li>
              <a href="/admin/affadmin/?user_id=<?php echo $this->cur_user->getId() ?>">Affiliate</a>
            </li>
            <?php if(p('user.transfer.cash')): ?>
            <li>
              <a href="/admin/transact-user/?user_id=<?php echo $this->cur_user->getId() ?>">Transfer Money</a>
            </li>
            <?php endif ?>
            <li>
              <a href="/admin/edit-user/?p=editusers&id=<?php echo $this->cur_user->getId() ?>">Edit / Permisssions</a>
            </li>
            <?php if(p('user.uploads')): ?>
            <li>
              <a href="/admin/upload-user-file/?id=<?php echo $this->cur_user->getId() ?>">File Uploads</a>
            </li>
            <?php endif ?>
            <?php if(p('user.add.deposit')): ?>
            <li>
              <a href="/admin/add-deposit/?user_id=<?php echo $this->cur_user->getId() ?>">Add Deposit</a>
            </li>
            <?php endif ?>
          </ul>
        </div>

        <table class="simple-table u-admin-table v-align-top">
          <col width="490"/>
          <col width="490"/>
          <tr class="even">
            <td>
              User id: <?php echo $this->cur_user->getId() ?>
              <br/>
              <br/>

              <?php if($this->showBlockBtn($lock_date, $block_value)): ?>
                <form method="post">
                  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                  <input type="hidden" name="username" value="<?= htmlspecialchars($_GET['username']) ?>"/>
                  <input type="submit" value="<?php echo ucfirst($block_value).' '.$this->username ?>" />
                  <input type="hidden" name="block" value="<?php echo $block_value ?>" />
                </form>
                <br/>
              <?php else: ?>
                <p>
                  User is <?php echo $block_value == 'activate' ? 'Blocked' : 'Not Blocked' ?>
                </p>
              <?php endif ?>

              <?php if(!empty($lock_date)): ?>
                <?php echo "Player has self-locked on $lock_date" ?>
                <br/>
                <br/>
              <?php endif  ?>

              <?php if(!empty($unlock_date)): ?>
                <?php echo "Player will be unlocked on $unlock_date" ?>
                <br/>
                <br/>
              <?php endif  ?>

              <?php if(p('user.unlock.in.7.days') && $this->cur_user->getAttr('active') != 1 && phive()->isEmpty($this->cur_user->getSetting('super-blocked'))):
                $today_plus7 = phive()->hisMod('+7 day', '', 'Y-m-d');
              ?>
                <form method="post">
                  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                  <input type="submit" value="<?php echo 'Change unlock date to: '.$today_plus7 ?>" />
                  <input type="hidden" name="unlock-date-7" value="<?php echo $today_plus7 ?>" />
                </form>
                <br/>
                <br/>
              <?php endif ?>

              <?php if(p('user.super.block')): ?>
                <form method="post">
                  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                  <input type="submit" value="<?php echo 'Toggle hide normal block, cur value: '.$super_blocked ?>" />
                  <input type="hidden" name="super-block" value="<?php echo empty($super_blocked) ? 1 : 0 ?>" />
                </form>
                <br/>
              <?php endif ?>

              <?php if(p('user.verify')): ?>
                <form method="post">
                  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                  <input type="submit" value="<?php echo ucfirst($verify_value).' '.$this->username ?>" />
                  <input type="hidden" name="verify" value="<?php echo $verify_value ?>" />
                </form>
                <br/>
              <?php else: ?>
                <p>
                  User is <?php echo (int)$this->cur_user->getSetting("verified") !== 1 ? 'Not Verified' : 'Verified' ?>
                </p>
              <?php endif ?>


              <?php if(p('user.verify.phone')): ?>
                <form method="post">
                  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                  <input type="submit" value="<?php echo ucfirst($verifyPhone_value) ?>" class="verifyPhone" alt="verify phone"/>
                  <input type="hidden" name="phoneVerify" value="<?php echo $verifyPhone_value ?>" />
                </form>
                <br/>
              <?php else: ?>
                <p>
                  User's phone is <?php echo (int)$this->cur_user->getAttribute("verified_phone") !== 1 ? 'Not Verified' : 'Verified' ?>
                </p>
              <?php endif ?>

              <?php if(p('user.clear.ips')): ?>
              <form method="post">
                <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                <input type="submit" name="clear_ips" value="Clear IP Log" />
              </form>
              <br/>
              <?php endif ?>

              <?php if(p('user.play.block')): ?>
                <form method="post">
                  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                  <input type="submit" name="toggle_play_block" value="<?php echo (int)$this->cur_user->getSetting("play_block") === 1 ? 'Play is blocked: Unblock' : 'Play is allowed: Block' ?>" />
                </form>
              <?php endif ?>

              <?php if($this->cur_user->hasSetting('experian_block')): ?>
                <p><strong>
                  <?php if((int)$this->cur_user->getSetting('experian_res') === -1): ?>
                    Player is play and deposit blocked due to automatic age verification failure.
                  <?php else: ?>
                    Player is play and deposit blocked due to automatic verification failure.
                  <?php endif ?>
                </strong></p>
              <?php endif ?>

                <?php if($this->cur_user->hasSetting('tac_block') || $this->cur_user->hasSetting('tac_block_sports') || $this->cur_user->hasSetting('bonus_tac_block')): ?>
                <p><strong>
                  Player is play and deposit blocked due to not accepting our current terms and conditions.
                </strong></p>
              <?php endif ?>

              <?php if($this->cur_user->hasSetting('unexclude-date')):
                  $permanent = $this->cur_user->hasSetting('indefinitely-self-excluded') ? 'permanent' : '';
                  ?>
                <p>
                  <strong>
                      Player has <?=$permanent?> self excluded on <?php echo $this->cur_user->getSetting('excluded-date')  ?>.
                      <br/>
                      <? if(empty($permanent)): ?>
                        Player will be able to unexclude on <?php echo $this->cur_user->getSetting('unexclude-date')  ?>.
                      <? endif;?>
                  </strong>
                </p>
              <?php endif ?>

              <?php if(p('user.phonedate')): ?>
                <br/>
                <form method="post">
                  <input type="submit" name="update_phonedate" value="Set Phoned Date to Current Time" />
                  <input type="submit" name="delete_phonedate" value="Delete Phoned Date" />
                  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                  <br/>
                  Last Phoned Date: <?php echo $this->cur_user->getSetting('phoned-date') ?>
                </form>
              <?php endif ?>
              <?php if(p('user.verify.email') && $this->cur_user->getSetting('email_code_verified') != 'yes'): ?>
                <br/>
                <form method="post">
                  <input type="submit" name="verify_email" value="Verify Email" />
                  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                </form>
                <br/>
              <?php elseif( $this->cur_user->getSetting('email_code_verified') == 'yes'): ?>
                <br/>
                User's email is verified.
                <br/>
              <?php endif ?>
              <?php if(empty($next_free_date) == false): ?>
                <br/>
                Last approved FREE withdraw date +7 days: <?php echo $next_free_date ?>
                <br/>
              <?php endif ?>
              <?php if(empty($bet) && !empty($welcome_dep) && empty($welcome_dep_removed) && p('remove.welcome.deposit')): ?>
                <br/>
                <form method="post">
                  <input type="submit" name="remove_welcome" value="Remove Welcome Deposit" />
                  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                </form>
                <br/>
              <?php endif ?>
              <?php if(p('user.show.bank')): ?>
                <br/>
                <?php if($this->cur_user->hasSetting('show_bank')): ?>
                  Bank is shown.
                <?php endif ?>
                <form method="post">
                  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                  <input type="submit" name="show_bank" value="Show Bank" />
                </form>
              <?php endif ?>
              <?php if(p('user.show.euteller')): ?>
                <br/>
                <?php if($this->cur_user->hasSetting('show_euteller')): ?>
                  Euteller is shown.
                <?php endif ?>
                <form method="post">
                  <input type="submit" name="show_euteller" value="Show Euteller" />
                  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                </form>
              <?php endif ?>
            </td>
            <td>
              <?php if(p('user.login.countries')): ?>
                <br/>
                Allowed login countries:
                <br/>
                <br/>
                <?php foreach($countries as $c): ?>
                  <form method="post">
                    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                    <input type="submit" value="<?php echo "Remove {$c['setting']}" ?>" />
                    <?php if(p('login.country.manage')): ?>
                      <input type="hidden" name="delete_country" value="<?php echo $c['setting'] ?>" />
                    <?php endif ?>
                  </form>
                  <br/>
                <?php endforeach ?>
                <br/>
                <?php if(p('login.country.manage')): ?>
                  <form method="post">
                    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                    <?php dbSelect("add_country", phive('Cashier')->getBankCountries(''), '', array('', t('choose.country')), 'narrow-input') ?>
                    <br/>
                    <br/>
                    <input type="submit" value="Add Country" />
                  </form>
                <?php endif ?>
              <?php endif ?>
            </td>
          </tr>
          <tr class="odd">
            <td>
              <br/>
              Comments:
              <br/>
              <br/>
              <div style="height: 300px; overflow: scroll; overflow-x: hidden; ">
                <table cellpadding="0" cellspacing="0">
                  <tr>
                    <td colspan="2">
                      <form method="post">
                        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                        <input type="text" name="add_comment" style="width: 386px" />
                        <div style="width: 100px">
                          <input type="checkbox" name="sticky" style="width: 10px" />
                          <label for="sticky" style="width: 30px">Keep sticky</label>
                        </div>
                        <div style="width: 100px; float: left">
                          <input type="checkbox" name="complaint" style="width: 10px" />
                          <label for="complaint" style="width: 30px">Complaint</label>
                        </div>
                        <div style="width: 100px; float: left">
                          <input type="checkbox" name="limits" style="width: 10px" />
                          <label for="limits" style="width: 30px">Discussion about RG limits</label>
                        </div>
                        <input type="submit" value="Add Comment" style="float: left; width: 120px"/>
                      </form>
                    </td>
                  </tr>
              <?php foreach($comments as $c): ?>
                  <?php if (empty($c['tag']) || $c['tag'] == 'complaint') : ?>
                  <tr <?= $c['sticky']? "style='background-color: #eee'": "" ?><?= ($c['tag'] == 'complaint')? "style='background-color: #abb'": "" ?>><td>
                        <?php if ($c['sticky']) echo "<strong>"; ?>
                        <?= $c['created_at'] ?>
                        <?php if ($c['sticky']) echo "</strong>"; ?>
                        <?= "<br>".$c['comment'] ?><?= $c['tag'] == 'complaint' ? ' / <strong>COMPLAINT</strong>' : '' ?>
                      </td><td>
                        <form method="post">
                          <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                          <input type="submit" value="X"/>
                          <input type="hidden" name="delete_comment" value="<?php echo $c['id'] ?>" />
                        </form></td></tr>
                  <?php endif ?>
              <?php endforeach ?>
                </table>
              </div>
            </td>
            <td>
              <?php if(p('user.inout.limits')): ?>
                <?php foreach($limits as $l): ?>
                  <form method="post">
                    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                    <input type="submit" value="<?php echo "X ".t($l['setting']).": {$l['value']}" ?>" />
                    <input type="hidden" name="delete_limit" value="<?php echo $l['setting'] ?>" />
                  </form>
                  <br/>
                <?php endforeach ?>
                <form method="post">
                  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                  Modify Deposit / Withdrawal upper limit:
                  <br/><br/><?php dbSelect("inout_type", array('in' => 'Deposit', 'out' => 'Withdrawal'), '', array('', 'Choose Type'), 'narrow-input') ?>
                  <br/><br/><?php dbSelect("inout_method", array('ppal' => 'PayPal', 'skrill' => 'Skrill', 'neteller' => 'Neteller', 'wirecard' => 'Card', 'bank' => 'Bank'), '', array('', 'Choose Method'), 'narrow-input') ?>
                  <br/><br/>Limit (in cents):<br/> <input type="text" name="inout_value" class="narrow-input" />
                  <br/><br/><input type="submit" value="Submit" />
                </form>
              <?php endif ?>
            </td>
          </tr>
          <tr class="even">
            <td>
              <?php if(p('user.inout.defaults')): ?>
                <br/>Payment solution info:<br/><br/>
                <?php foreach($psolutions as $sol): ?>
                  <form method="post">
                    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                    <input type="submit" value="<?php echo "X ".t($sol['setting']).": {$sol['value']}" ?>" />
                    <input type="hidden" name="delete_psol" value="<?php echo $sol['setting'] ?>" />
                  </form>
                  <br/>
                <?php endforeach ?>
              <?php endif ?>
            </td>
            <td>
              <?php if(p('user.inout.defaults')): ?>
                <br/>Set payment solutions info:<br/><br/>
                <form method="post" id="">
                  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                  <br/>
                  <br/>
                  <p><?php et("mb.email") ?>:</p>
                  <?php dbInput('mb_email', $this->cur_user->getSetting('mb_email')) ?>
                  <p><?php et("net.account") ?>:</p>
                  <?php dbInput('net_account', $this->cur_user->getSetting('net_account')) ?>
                  <p><?php et("ppal.email") ?>:</p>
                  <?php dbInput('ppal_email', $this->cur_user->getSetting('ppal_email')) ?>
                  <br/><br/><input type="submit" value="Submit" id="psolutionBtn"/>
                </form>
              <?php endif ?>
            </td>
          </tr>
          <tr>
            <td>
              <?php if(p('user.dc.daily.limit')): ?>
                <form method="post" id="">
                  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                  <p><?php et("dc.daily.deposit.limit") ?>:</p>
                  <?php dbInput('dc_daily_limit', $this->cur_user->getSetting('in-dc-day-limit')) ?>
                  <br/><br/><input type="submit" value="Submit" id="psolutionBtn"/>
                </form>
                <form method="post" id="">
                  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                  <p>Number of quick deposits before revalidation:</p>
                  <?php dbInput('dc_num_quick_limit', $this->cur_user->getSetting('n-quick-deposits-limit')) ?>
                  <br/><br/><input type="submit" value="Submit"/>
                </form>
              <?php endif ?>
            </td>
            <td>
              <?php if(p('remove.cur.lga.limit')): ?>
                <?php foreach(array('cur-lgaloss-lim', 'cur-lgawager-lim', 'cur-lgatime-lim') as $lgas): ?>
                  <?php if($this->cur_user->hasSetting($lgas)): ?>
                    <form method="post">
                      <input type="submit" value="<?php echo "Remove ".$lgas ?>" name="<?php echo $lgas ?>" />
                      <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                    </form>
                    <br/>
                  <?php endif ?>
                <?php endforeach ?>
              <?php endif ?>
            </td>
          </tr>
          <tr>
            <td>
              <br/>Casino / Other settings:<br/>
              <?php if(p('user.casino.settings')): ?>
                <form method="post">
                  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                  <?php foreach ($other as $os): ?>
                    <p><?php echo $os ?>:</p>
                    <?php dbInput($os, $this->cur_user->getSetting($os)) ?>
                  <?php endforeach ?>
                  <br/><br/><input name="submit_other" type="submit" value="Submit"/>
                </form>
              <?php endif ?>
            </td>
            <td>
              <br/>User Attributes:<br/>
              <?php if(p('change.attributes.light')): ?>
                <form method="post">
                  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                  <?php foreach ($light_attrs as $attr): ?>
                    <p><?php et($attr) ?>:</p>
                    <?php dbInput($attr, $this->cur_user->getAttr($attr)) ?>
                  <?php endforeach ?>
                  <br/><br/><input name="submit_attributes_light" type="submit" value="Submit"/>
                </form>
              <?php endif ?>
            </td>
          </tr>
        </table>

      </div>
      <div class="news-bottom"></div>
    </div>

    <?php
  }

    function handleAffAdmin($action = ''){
        if(!p('account.admin'))
            return;

        $action = empty($action) ? $this->page : $action;
        $keys 	= array("pixel_url", 'aff_manager');

        foreach ($keys as $s) {
            if(isset($_POST["delete_".$s]))
                $this->cur_user->deleteSetting($s);

            if(!empty($_POST[$s]) || $_POST[$s] === "0")
                $this->cur_user->setSetting($s, $_POST[$s]);
        }

        $settings 		= $this->cur_user->getAllSettings();
        $values			= array();

        foreach($settings as $s){
            if(in_array($s['setting'], $keys))
                $values[$s['setting']] = $s;
        }

        return [$keys, $values];
    }

  function printAffAdmin($action = '') {
      list($keys, $values) = $this->handleAffAdmin($action);
    ?>
    <div class="news-container">
    <div class="news-middle">
    <table class="simple-table u-admin-table v-align-top">
      <tr>
        <td>
          <form method="post" id="pokerForm">
          <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
            <br/>
            <br/>
            <?php foreach($keys as $s): ?>
              <p><?php et($s) ?>:</p>
              <?php dbInput($s, $values[$s]['value']) ?>
            <?php endforeach ?>
            <br/><br/><input type="submit" value="Submit" id="pokerBtn"/>
          </form>
        </td>
      </tr>
    </table>
    </div>
    </div>
    <?php
  }

  function printExtra(){ ?>
    <p>
      Site type (normal/mobile) :
      <?php dbInput('site_type', $this->site_type) ?>
    </p>
  <?php }

    function printForm($err, $update, UserContactData $user_contact_data){
        loadCss("/diamondbet/css/" . brandedCss() . "edit-profile.css");

        /** @var \Laraphive\Domain\User\DataTransferObjects\EditProfile\EditProfileResponseData $fields */
        $fields = phive('DBUserHandler')->getEditProfile($user_contact_data);
        $personalInfo = $fields->getPersonalInfoData();
        $contactInfo = $fields->getContactInfoData();
        $accountInfo = [];

        if(!in_array('accounts_info', lic('getLicSetting', ['hide_edit_profile_sections'], $this->cur_user))) {
            $accountInfo = $fields->getAccountInfoData();
        }

        $termsConditions = $fields->getTermsConditionsData();
        $admin_dep_limit = $this->cur_user->getSetting('permanent_dep_lim');
        $realtime = $this->cur_user->getSetting('realtime_updates');
        ?>
        <script>
            $(document).ready(function(){
                if (navigator.userAgent.match(/iphone/i)) {
                    let formElement = $(".registerform #mobile");
                    if (formElement.length) {
                        formElement.addClass('reg-mobile-iphone-font');
                    }
                }
            });
        </script>
        <?php $this->printTopMenu() ?>
        <div><?php if (!$update) echo t("reginfo.top.html") ?></div>
        <?php if ($update): ?>
        <div class="general-account-holder">
            <div class="simple-box personal-info-box">
                <form name="registerform" method="post" action="">
                    <input type="hidden" name="token" value="<? echo $_SESSION['token']; ?>">
                    <h3 class="section-title">
                        <?php et($personalInfo->getHeadline()) ?>
                    </h3>
                    <div class="registerform">
                        <?php foreach ($personalInfo->getFormElements() as $element): ?>
                            <?php if ($element->isShow() === true): ?>
                                <?php if ($element instanceof SelectBoxData): ?>
                                    <div>
                                        <strong><?php echo t($element->getAlias()) ?></strong>
                                    </div>
                                    <div class="select-option">
                                            <span class="edit-profile-select-wrapper">
                                                <?php dbSelect(
                                                    $element->getName(),
                                                    $element->getOptions()->getItems(),
                                                    $element->getValue(),
                                                    array('', t($element->getAlias())),
                                                ) ?>
                                            </span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($element instanceof CheckboxData): ?>
                                    <?php if ($element->getName() === 'realtime_updates'): ?>
                                        <div class="personal-info-item">
                                            <div>
                                                <?php dbCheckSetting(
                                                    $this->cur_user,
                                                    $element->getName(),
                                                    $element->getValue()
                                                ) ?>

                                            </div>
                                            <div><?php et($element->getAlias()) ?></div>
                                        </div>
                                    <?php elseif ($element->getName() === 'accept_tac'): ?>
                                        <div class="personal-info-item">
                                            <div>
                                                <?php dbCheck($element->getName(), $element->getValue()) ?>
                                            </div>
                                            <div><?php et($element->getAlias()) ?></div>
                                        </div>
                                    <?php elseif ($element->getName() === 'accept_bonus_tac'): ?>
                                        <div class="personal-info-item">
                                            <div>
                                                <?php dbCheck($element->getName(), $element->getValue()) ?>
                                            </div>
                                            <div><?php et($element->getAlias()) ?></div>
                                        </div>
                                    <?php else: ?>
                                        <div class="personal-info-item">
                                            <div>
                                                <?php dbCheckSetting(
                                                    $this->cur_user,
                                                    $element->getName(),
                                                    $element->getValue()
                                                ) ?>

                                            </div>
                                            <div><?php et($element->getAlias()) ?></div>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <div>
                            <?php foreach ($personalInfo->getDescriptions() as $description): ?>
                                <div><?php echo t($description) ?></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="update-button">
                            <div>
                                <?php foreach ($personalInfo->getButtons() as $button): ?>
                                    <input type="<?= $button->getType(); ?>" name="<?= $button->getName(); ?>"
                                           value="<?php echo t($button->getValue()); ?>"
                                           class="btn btn-l btn-default-l edit-profile-submit-btn"
                                    />
                                <?php endforeach; ?>
                                <input type="hidden" name="token" value="<? echo $_SESSION['token']; ?>">
                            </div>
                        </div>
                    </div>

                </form>
            </div>

            <div id="contact-info-box" class="simple-box contact-info-box">
                <h3 class="section-title">
                    <?php et($contactInfo->getHeadline()) ?>
                </h3>
                <?php
                if (!p('admin_top')) {
                    $this->printEditContactDetails($err, $contactInfo);
                } elseif ($_SESSION['show_contact_form']) {
                    $this->printEditContactDetails($err, $contactInfo);
                } else {
                    $this->spyObfuscatedData('show_contact_form', $this->cur_user->userId, true);
                }

                if (!empty($_POST['validate_contact_info'])) {
                    $this->errorZone($err, false);
                }
                ?>
                <?php if (!empty($_POST['submit_contact_info'])) {
                    if (empty($err)) {
                        $err = t('contact.details.updated.successfully');
                    }
                    $this->errorZone($err);
                } ?>
            </div>

            <?php if(!empty($accountInfo)): ?>
             <div class="simple-box account-info-box">
                <form name="registerform" method="post" action="">
                    <input type="hidden" name="token" value="<? echo $_SESSION['token']; ?>">
                    <h3 class="section-title">
                        <?php et($accountInfo->getHeadline()) ?>
                    </h3>
                    <div class="registerform account-info-section">
                        <div class="account-info">
                            <?php foreach ($accountInfo->getFormElements() as $element): ?>
                                <?php if ($element instanceof InputData): ?>
                                    <div class="account-info-item">
                                        <div><strong><?php echo t($element->getAlias()) ?></strong></div>
                                        <div><?php dbInput(
                                                $element->getName(),
                                                $element->getValue(),
                                                $element->getInputType()
                                            ) ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <div class="account-info-item">
                            <?php foreach ($accountInfo->getFormElements() as $element): ?>
                                <?php if ($element instanceof LabelData): ?>
                                    <div>
                                        <strong>* <?php et($element->getAlias()) ?></strong>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php foreach ($accountInfo->getButtons() as $button): ?>
                                <div>
                                    <input type="<?= $button->getType(); ?>" name="<?= $button->getName(); ?>"
                                           value="<?= t($button->getValue()); ?>"
                                           class="btn btn-l btn-default-l edit-profile-submit-btn"
                                    />
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </form>
                <?php if (!empty($_POST['submit_password'])) {
                    if (empty($err)) {
                        $err['password'] = 'updated.successfully';
                    }
                    $this->errorZone($err);
                } ?>
             </div>
            <?php endif; ?>

        <?php lic('showAccount2FaSecuritySettings', [$this->cur_user], $this->cur_user); ?>

        <?php if ($this->shouldBlockTAC($this->cur_user)): ?>
            <div class="simple-box term-and-condition-box">
                <h3 class="section-title">
                    <?= t($termsConditions->getHeadline()) ?>
                </h3>

                <form method="post">
                    <div>
                        <div>
                            <div class="edit-profile-terms__btn-data">
                                <input type="hidden" name="token" value="<? echo $_SESSION['token']; ?>">
                                <?php foreach ($termsConditions->getButtons() as $button): ?>
                                    <input
                                        type="<?= $button->getType() ?>"
                                        name="<?= $button->getName() ?>"
                                        value="<?= t($button->getValue()) ?>"
                                        class="btn btn-l btn-default-l edit-profile-terms__btn-input edit-profile-submit-btn"
                                    />
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <br clear="all"/>
        </div>
          <?php endif ?>
    <?php }


}
