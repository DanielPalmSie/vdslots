<?php
$boxId = $_POST['box_id'];
$mobile = phive()->isMobile() ? 'mobile' : '';
$user = cu() ?? null;
$uid = $user ? $user->getId() : null;

?>
<div class="deposit-notification">
    <div class="deposit-notification__logo">
        <div class="deposit-notification__logo-icon"><img
                    src="/diamondbet/images/<?= brandedCss() ?>deposit_success.svg"></div>
    </div>
    <div class="deposit-notification__body">
        <div class="deposit-notification__body__title"><?= et('deposit.complete.headline') ?></div>
        <div class="deposit-notification__body__description">
            <?= et('firstdeposit.success.description') ?>
        </div>
    </div>

    <div class="deposit-notification__offer">
        <div class="deposit-notification__offer-logo">
            <div class="deposit-notification__offer-logo-gift"><img
                        src="/diamondbet/images/<?= brandedCss() ?>/deposit-gift.png"></div>
        </div>
        <div class="deposit-notification__description deposit-notification__offer-welcome">
            <p>
                <?= et('deposit.match.activate.bonus.description') ?>
            </p>
        </div>
    </div>

    <div class="deposit-notification__actions deposit-notification__actions__firstdeposit">
        <?php if (isPNP()): ?>
            <button class="btn btn-l btn-default-l activate-btn">
                <?= et('firstdeposit.activate.offer.yes.btn') ?>
            </button>
        <?php else: ?>
            <button class="btn btn-l btn-default-l activate-btn" onclick="activateWelcomeOffers()">
                <?= et('firstdeposit.activate.offer.yes.btn') ?>
            </button>
        <?php endif; ?>
        <button class="btn btn-l btn-default-l close-btn"  onclick="closePopup()">
            <?= et('firstdeposit.activate.offer.no.btn') ?>
        </button>
    </div>
</div>

<script>

    function closePopup() {
        gotoLang('/');
    }

    function activateWelcomeOffers() {
        mgAjax({action: 'activate-welcome-offers', 'user_id': '<?php echo $uid ?>'}, function () {
            location.href = '<?= phive('UserHandler')->getUserAccountUrl('my-profile') ?>'
        });
    }

</script>