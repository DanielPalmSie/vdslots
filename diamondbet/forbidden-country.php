<?php
require_once __DIR__ . '/../phive/phive.php';

?>

<!DOCTYPE html>
<html>
<head>
    <title><?= phive()->getSiteTitle() ?> Casino - Maintenance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php loadCss("/diamondbet/css/" . brandedCss() . "forbidden-country.css"); ?>
</head>
<body>
<div class="contained">
    <div class="content">

        <div class="logo">
            <img src="/diamondbet/images/<?= brandedCss() ?>logo.png" alt="">
        </div>
        <div class="text">
            <div class="icon">

                <xml version="1.0" encoding="utf-8">
                    <!-- Generator: Adobe Illustrator 26.5.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)  -->
                    <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg"
                         xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                         viewBox="0 0 256 256" style="enable-background:new 0 0 256 256;" xml:space="preserve">
          <path class="st0" d="M127.69,17.48c-60.99,0-110.6,49.62-110.6,110.6c0,60.99,49.62,110.6,110.6,110.6s110.6-49.62,110.6-110.6
    C238.29,67.1,188.68,17.48,127.69,17.48z M30.09,128.08c0-24.42,9.02-46.78,23.9-63.91l138.25,137.06
    c-17.22,15.21-39.82,24.46-64.55,24.46C73.87,225.69,30.09,181.9,30.09,128.08z M201.39,192L63.14,54.94
    c17.22-15.21,39.82-24.46,64.55-24.46c53.82,0,97.6,43.78,97.6,97.6C225.29,152.51,216.27,174.86,201.39,192z"/>
        </svg>
            </div>
            <h1 class="title">Unfortunately we do not accept players from your country.</h1>
            <div>Please contact <b
                    class="support-text-color"><?= phive('MailHandler2')->getSetting('support_mail') ?></b> for more
                information and inquiries.
            </div>
        </div>
    </div>
</div>
</body>
</html>
