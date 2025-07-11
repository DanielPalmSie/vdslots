<?php
$ada = phive('AdaChat');

$bot = $ada->getSetting('bot_handle');
$libUrl = $ada->getSetting('lib_url');
$cluster = $ada->getSetting('cluster');
$site_locale = phive('Localizer')->getLanguage();

$metaFields = [];
$sensitiveMetaFields = [];
if (isLogged()) {
    $user = cu();
    $metaFields['name'] = $user->getFirstName();
    if (!empty($_SESSION['ada_token'])) {
        $sensitiveMetaFields['jwt_token'] = $_SESSION['ada_token'];
    } else {
        $_SESSION['ada_token'] = $ada->getAuthToken($user->getId());
        $sensitiveMetaFields['jwt_token'] = $_SESSION['ada_token'];
    }
}

?>

<script>
    window.adaSettings = {
        handle: "<?= $bot ?>",
        <?php if (!empty($cluster)) : ?>
        cluster: "<?= $cluster ?>",
        <?php endif; ?>
        hideMask: true,
        language: "<?= $site_locale ?>",
        metaFields: <?= json_encode($metaFields) ?>,
        sensitiveMetaFields: <?= json_encode($sensitiveMetaFields) ?>,
        toggleCallback: (isDrawerOpen) => {
            if (isDrawerOpen) {
                $.cookie('ada_chat_state', 'open')
            } else {
                $.cookie('ada_chat_state', 'closed')
            }
        }
    };
    window.openAdaChatBot = function () {
        window.adaEmbed.toggle();
        $.cookie('ada_chat_state', 'open');
    };

    $(document).ready(function () {
        var body = document.getElementsByTagName('body')[0];
        var script = document.createElement('script');

        script.type = 'text/javascript';
        script.src = '<?= $libUrl ?>';
        script.async = true;
        script.onload = function () {
            if ($.cookie('ada_chat_state') == 'open') {
                window.adaEmbed.toggle();
            }
        };
        body.appendChild(script);

    });
</script>



