<?php

if (phive()->getSetting('zendesk_api_key') === null ) {
    return null;
}

$site_locale = phive('Localizer')->getLanguage();
$zendesk_api_url = phive()->getSetting('zendesk_api_url');
$zendesk_api_key = phive()->getSetting('zendesk_api_key');
?>

<script type="text/javascript">
    $(document).ready(function () {
        var body = document.getElementsByTagName('body')[0];
        var script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = '<?= $zendesk_api_url."?key=".$zendesk_api_key ?>';
        script.id = 'ze-snippet';
        script.onload = function () {
            zE(function () {
                zE('messenger:set', 'locale', '<?= $site_locale ?>');

                if ($.cookie('zendesk_chat_state') != 'open') {
                    zE('messenger', 'hide');
                }

                zE('messenger:on', 'close', function () {
                    zE('messenger', 'hide');
                    $.cookie('zendesk_chat_state', 'closed');
                });
                window.openZendesk = function () {
                    zE('messenger', 'open');
                    zE('messenger', 'show');
                    $.cookie('zendesk_chat_state', 'open');
                };

            });
        };
        body.appendChild(script);

    });
</script>
