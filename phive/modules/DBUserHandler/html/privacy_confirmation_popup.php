<?php
$brand = phive('BrandedConfig')->getBrand();
$box_id = $_POST['boxid'] ?? 'privacy-confirmation-notification';
$mobile_in_privacy = $_POST['mobile'] ?? '';
$post = $_POST['post'] ?? '';
$mobile = phive()->isMobile() ? 'mobile' : '';
$user = cu() ?? null;
$uid = $user ? $user->getId() : false;
$lang_tag = phive('Localizer')->getCurNonSubLang();

?>

<div class="privacy-confirmation-notification">
    <div class="privacy-confirmation-notification__logo">
        <div class="privacy-confirmation-notification__logo-icon">
            <img src="/diamondbet/images/privacy-confirm-logo.png" alt="Privacy Confirmation">
        </div>
    </div>

    <div class="privacy-confirmation-notification__content">
        <div class="privacy-confirmation-notification__body">
            <div class="privacy-confirmation-notification__body__title">
                <?= et('privacy.confirmation.title'); ?>
            </div>
            <div class="privacy-confirmation-notification__body__description">
                <p><?= et2('privacy.confirmation.description', "$brand"); ?></p>
                <p><?= et('privacy.confirmation.description.opt'); ?></p>
            </div>
        </div>

        <div class="privacy-confirmation-notification__actions">
            <button class="privacy-confirmation-notification__button btn btn-l btn-default-l activate-btn"
                    onclick="PrivacyConfirmation.privacyAccept()">
                <?= et('privacy.confirmation.accept'); ?>
            </button>

            <button
                class="privacy-confirmation-notification__button privacy-confirmation-notification__button--secondary btn btn-l"
                onclick="PrivacyConfirmation.privacyEdit()">
                <?= et('privacy.confirmation.edit.preference'); ?>
            </button>
        </div>
    </div>

    <div class="privacy-confirmation-notification__footer">
        <p onclick="PrivacyConfirmation.privacyLater()"><u><?= et('privacy.confirmation.maybe.later'); ?></u></p>
    </div>
</div>


<script>
    const post = "<?= $post ?>";
    const mobile = "<?= $mobile_in_privacy ?>";
    const boxId = "<?= $box_id ?>";

    const PrivacyConfirmation = {
        privacyAccept() {
            privacyAction('accept', mobile, post);
        },

        privacyEdit() {
            this.showPrivacyDashPopup(boxId);
        },

        privacyLater() {
            privacyAction('cancel', mobile, post);
        },

        showPrivacyDashPopup(id) {
            mboxClose(id, function () {
                showPrivacySettings();
            });
        }
    };

    $(document).ready(function () {
        if ("<?= $mobile ?>" === 'mobile') {
            if ($('#privacy-confirmation-notification').is(':visible')) {
                $('a.multibox-close').css('z-index', '2008');
            }
        }
    });
</script>


