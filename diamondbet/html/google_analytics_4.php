<?php
use DBUserHandler\Libraries\GoogleEventAggregator;
require_once __DIR__ . '/../../phive/phive.php';

$events = GoogleEventAggregator::getEvents();
$user = cuRegistration();

if(empty($user)) {
    $user = null;
}

/** Main logic to push events in case of page reloads */
/** GA4 will only load if GTM is not loaded, only one is required at a time, GTM automatically load GA4 */
/** If GTM is not loaded then GA4 can be loaded independently */
/** HELP: https://developers.google.com/analytics/devguides/collection/ga4/tag-options#overview */
if (isGoogleAnalytic4Enabled() && !isExternalTrackingEnabled() && isset($_COOKIE['cookies_consent_info'])) { ?>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo phive()->getDomainSetting('google_analytic_4_measurement_id') ?>"></script>
    <script>
        initializeConsent();
        var user_id = <?php echo !empty($user) ? $user->getId(): 'null' ?>;
        if(user_id !== null) { dataLayer.push({userId: user_id}); }
        updateConsent();
        gtag('config', '<?php echo phive()->getDomainSetting('google_analytic_4_measurement_id') ?>');
    </script>
    <?php
}
