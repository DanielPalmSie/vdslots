<?php
// is there a way to get jurisdiction if a user is not logged in?
$jurisdiction = licJur();

// the url to pass in to btnCancelDefaultL in the DOM
$url = 'https://google.com';

function onEnter() {
    return "onEnter()";
}

loadCss('/diamondbet/css/' . brandedCss() . 'jurisdictional_notice_prompt.css');
?>

<div class="jur-notice-container">
    <img class="jur-notice-container__img" src="" />
    <p class="jur-notice-container__message">
        <?php et('new.jurisdiction.popup.message') ?>
    </p>
    <div class="jur-notice-container__btn-container">
        <?php btnActionL(
            t('Enter'),
            '',
            onEnter(),
            '',
            'jur-notice-container__btn')
        ?>
        <?php btnCancelDefaultL(
            t('Exit'),
            '',
            goToLlink($url),
            '',
            'jur-notice-container__btn')
        ?>
    </div>
    <script>
        function onEnter() {
            if (!showJurisdictionPopup && $.cookie('jurisdiction_popup') !== null) {
                showLoginBox('login');
            }
            sCookieDays('jurisdiction_popup', 1, 1);
            $.multibox('close', 'mbox-msg');
            showJurisdictionPopup = true;
        }

        var img = document.getElementsByClassName('jur-notice-container__img');
        var jurisdiction = '<?php echo $jurisdiction ?>';

        var mobileImg = "/diamondbet/images/"+ '<?php echo brandedCss() ?>' +"jurisdictional_notice/"+ jurisdiction + "/jurisdictional_notice_mobile.png";
        var regularImg = "/diamondbet/images/"+ '<?php echo brandedCss() ?>' +"jurisdictional_notice/"+ jurisdiction + "/jurisdictional_notice.png";

        var mql = window.matchMedia("(orientation: portrait)");

        // If there are matches, we're in portrait
        if(mql.matches) {
            // Portrait orientation
            img[0].src = mobileImg
        } else {
            // Landscape orientation
            img[0].src = regularImg
        }

        // Add a media query change listener
        mql.addEventListener('change', function(m) {
            if(m.matches) {
                // Changed to portrait
                img[0].src = mobileImg
            }
            else {
                // Changed to landscape
                img[0].src = regularImg
            }
        });

    </script>
</div>