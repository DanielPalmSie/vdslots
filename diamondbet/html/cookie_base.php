<?php
require_once __DIR__ . '/../../phive/phive.php';

$box_id = $_POST['box_id'] ?? 'cookie_notification_popup-box';
$currentUrl = $_SERVER['REQUEST_URI'];

$cookie = phive('Cookie');

$isMobile = phive()->isMobile();
$platform = $isMobile ? 'mobile' : 'desktop';
$gtm_key = phive()->getDomainSetting('google_analytic_tag_key');

?>
<div id="cookie-banner" class="onFloating cookie-popup__transparent" style="display: none" data-nosnippet>
    <div class="cookies-banner-outer">
        <div class="cookies-banner-content">
            <div class="cookie-popup__container cookie-popup__container--<?= $platform ?>">
                <div>
                    <div class="cookie-popup__detail">
                        <h4 class="cookie-popup__sub-header cookie-sub-header"><?php et('cookie.use') ?></h4>
                        <p class="cookie-popup__description">
                <span>
                    <?php et('cookie.banner.info.text') ?>
                    <strong class="cookie-popup__link--cookie-policy"> <?= $cookie->redirectLink('/cookie-policy/', "", 'Cookie Policy'); ?> </strong></span>
                        </p>
                    </div>
                </div>

                <div class="cookie-popup__action-manage cookie-popup__action-manage--<?= $platform ?>">
                    <button
                        id="btn-cookie-accept-all"
                        class="btn btn-l btn-default-l cookie-popup__button cookie-accept-all"
                        type="button"
                    >
                        <?php et('accept') ?>
                    </button>
                    <div class="cookie-popup__sub-header" id="cookie-popup-manage"><?php et('cookie.manage') ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
        $(function () {
            $(document).ready(function () {
                window.dataLayer = window.dataLayer || [];
                const cookieConsentManagement = Cookie.cookieConsentManagement;

                $('.cookie-popup__link--cookie-policy').on('click', function() {
                    window.dataLayer.push({
                        'event': 'cookie_policy_click',
                        'consent_type': 'cookie_policy_click',
                        'consent_label': cookieConsentManagement,
                    });
                })

                $('#cookie-popup-manage').on('click', function (e) {
                    e.preventDefault();
                    $('#cookie-banner').hide();
                    Cookie.showManageCookiePopup()
                });

                $('.cookie-accept-all').on('click', function (e) {
                    $('#cookie-necessary, #cookie-functional, #cookie-analytics, #cookie-marketing').prop('checked', true);
                    let checkAllUpdates = {
                        necessaryChecked: true,
                        functionalChecked: true,
                        analyticsChecked: true,
                        marketingChecked: true
                    };
                    e.preventDefault();
                    $('#cookie-banner').hide();
                    Cookie.handleCheckSubmission('<?= $box_id ?>', checkAllUpdates, '<?= $gtm_key ?>');
                    window.dataLayer.push({
                        'event': 'cookie_consent_accept',
                        'consent_type': 'cookie_consent_accept',
                        'consent_label': cookieConsentManagement,
                        'consent_preferences': {
                            'functional': 'yes',
                            'analytics': 'yes',
                            'marketing': 'yes'
                        }
                    });
                });

                Cookie.fixCookiePopupFromFooter();
            });

        });
    </script>
