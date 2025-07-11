<?php
require_once __DIR__ . '/../../../phive.php';

$box_id = $_POST['box_id'] ?? 'cookie_notification_popup-box';
$currentUrl = $_SERVER['REQUEST_URI'];

$cookie = phive('Cookie');

$box_headline_alias = $_POST['boxtitle'] ?? 'msg.title';

$isMobile = phive()->isMobile();
$platform = $isMobile ? 'mobile' : 'desktop';
$gtm_key = phive()->getDomainSetting('google_analytic_tag_key');

?>

<div class="lic-mbox-wrapper <?= $_POST['extra_css'] ?: '' ?>">
    <div class="lic-mbox-header relative">
        <div class="lic-mbox-close-box" onclick="closeCookiesPopup('<?=$box_id;?>')"><span class="icon icon-vs-close"></span></div>
        <div class="lic-mbox-title">
            <?php et($box_headline_alias) ?>
        </div>
    </div>
    <div class="lic-mbox-container">
        <div class="cookie-popup__container cookie-popup__container-manage cookie-popup__container--<?= $platform ?>">
            <div>
                <div class="cookie-popup__detail">
                    <p class="cookie-popup__description">
                        <span><?php et('cookie.banner.info.text') ?>
                            <strong class="cookie-popup__link--cookie-policy">  <?= $cookie->redirectLink('/cookie-policy/', "", 'Cookie Policy'); ?> </strong>
                        </span>
                    </p>
                </div>
            </div>
            <form class="cookie-popup__toggle-container" id="cookie-popup-toggle-form" action="javascript:">
                <hr/>
                <div class="cookie-popup__toggle-info">
                    <div class="cookie-popup__info">
                        <h4 class="cookie-popup__sub-header"><?php et('necessary') ?></h4>
                        <p class="cookie-popup__description"><?php et('cookie.banner.necessary.text') ?></p>
                    </div>
                    <label class="cookie-popup__toggle">
                        <input value="cookie-necessary"
                               class="cookie-popup__toggle-checkbox"
                               id="cookie-necessary"
                               name="cookie-necessary"
                               type="checkbox" disabled="disabled" checked>
                        <span class="cookie-popup__toggle-switch" style="opacity: 0.6"></span>
                    </label>

                </div>
                <div class="cookie-popup__toggle-info">
                    <div class="cookie-popup__info">
                        <h4 class="cookie-popup__sub-header"><?php et('functional') ?></h4>
                        <p class="cookie-popup__description"><?php et('cookie.banner.functional.text') ?></p>
                    </div>
                    <label class="cookie-popup__toggle">
                        <input value="cookie-functional"
                               class="cookie-popup__toggle-checkbox"
                               id="cookie-functional"
                               name="cookie-functional"
                               type="checkbox"
                               checked="false">
                        <span class="cookie-popup__toggle-switch"></span>
                    </label>
                </div>
                <div class="cookie-popup__toggle-info">
                    <div class="cookie-popup__info">
                        <h4 class="cookie-popup__sub-header"><?php et('analytics') ?></h4>
                        <p class="cookie-popup__description"><?php et('cookie.banner.analytics.text') ?></p>
                    </div>
                    <label class="cookie-popup__toggle">
                        <input value="cookie-analytics"
                               class="cookie-popup__toggle-checkbox"
                               id="cookie-analytics"
                               name="cookie-analytics"
                               type="checkbox"
                               checked>
                        <span class="cookie-popup__toggle-switch"></span>
                    </label>
                </div>
                <div class="cookie-popup__toggle-info">
                    <div class="cookie-popup__info">
                        <h4 class="cookie-popup__sub-header"><?php et('marketing') ?></h4>
                        <p class="cookie-popup__description"><?php et('cookie.banner.marketing.text') ?></p>
                    </div>
                    <label class="cookie-popup__toggle">
                        <input value="cookie-marketing"
                               class="cookie-popup__toggle-checkbox"
                               id="cookie-marketing"
                               name="cookie-marketing"
                               type="checkbox"
                               checked>
                        <span class="cookie-popup__toggle-switch"></span>
                    </label>
                </div>
                <div class="cookie-popup__action-btn-wrapper">
                    <button
                        id="cookie-popup-button-confirm"
                        class="btn btn-l btn-default-l cookie-popup__button cookie-popup__button--confirm"
                        type="submit"
                    >
                        <?php et('confirm') ?>
                    </button>
                </div>
                <div class="cookie-popup__action-btn-wrapper">
                    <button
                        id="btn-cookie-allow-all"
                        class="btn btn-l cookie-popup__button cookie-popup__button--allow-all cookie-accept-all"
                        type="button"
                    >
                        <?php et('cookie.allow.all') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    $(function () {
        $(document).ready(function () {
            const cookieConsentManagement = Cookie.cookieConsentManagement;

            $('.cookie-popup__link--cookie-policy').on('click', function() {
                window.dataLayer.push({
                    'event': 'cookie_policy_click',
                    'consent_type': 'cookie_policy_click',
                    'consent_label': cookieConsentManagement,
                });
            })

            $('.cookie-accept-all').on('click', function (e) {
                $('#cookie-necessary, #cookie-functional, #cookie-analytics, #cookie-marketing').prop('checked', true);
                let checkAllUpdates = {
                    necessaryChecked: true,
                    functionalChecked: true,
                    analyticsChecked: true,
                    marketingChecked: true
                };
                e.preventDefault();
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

            $('#cookie-popup-toggle-form').submit(function (e) {
                e.preventDefault();
                let cookieUpdate = {
                    necessaryChecked: $('#cookie-necessary').prop('checked'),
                    functionalChecked: $('#cookie-functional').prop('checked'),
                    analyticsChecked: $('#cookie-analytics').prop('checked'),
                    marketingChecked: $('#cookie-marketing').prop('checked')
                }
                Cookie.handleCheckSubmission('<?= $box_id ?>', cookieUpdate, '<?= $gtm_key ?>');
                window.dataLayer.push({
                    'event': 'cookie_consent_toggle',
                    'consent_type': 'cookie_consent_toggle',
                    'consent_label': cookieConsentManagement,
                    'consent_preferences' : {
                        'functional': cookieUpdate.functionalChecked ? 'yes' : 'no',
                        'analytics': cookieUpdate.analyticsChecked ? 'yes' : 'no',
                        'marketing': cookieUpdate.marketingChecked ? 'yes' : 'no'
                    }
                });
            });
        });
    });

    function closeCookiesPopup(box_id) {
        mboxClose('<?= $box_id ?>', Cookie.showCookiePopup());
    }
</script>
