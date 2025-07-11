<?php
$freshdesk_plugin_url = phive()->getSetting('freshdesk_plugin_url');
$freshdesk_widget_ids = phive()->getSetting('freshdesk_widget_ids', []);
$site_locale = phive('Localizer')->getLanguage();
$widget_id = $freshdesk_widget_ids[$site_locale] ?? $freshdesk_widget_ids['DEFAULT'];
?>
<script type="text/javascript">
    function closeFreshdeskWidget() {
        window.fcWidget.close();
        $.cookie('freshdesk_chat_state', 'closed');
    }

    function openFreshdeskWidget() {
        window.fcWidget.open();
        $.cookie('freshdesk_chat_state', 'open');
    }

    window.fcWidgetMessengerConfig = {
        locale: "<?= $site_locale ?>",
        eagerLoad: false,
        config: {
            headerProperty: {
                hideChatButton: true,
            }
        }
    }

    let freshChatScript = document.createElement('script');
    freshChatScript.src = '<?= $freshdesk_plugin_url ?>';
    freshChatScript.setAttribute('chat', 'true');
    freshChatScript.setAttribute('widgetId', '<?= $widget_id ?>');
    freshChatScript.id = 'fresh-desk-script';
    freshChatScript.onload = () => {
        const checkWidgetAvailability = setInterval(() => {
            if (window.fcWidget && typeof window.fcWidget.on === 'function') {
                clearInterval(checkWidgetAvailability);

                window.fcWidget.on('widget:loaded', function() {
                    $.cookie('freshdesk_chat_state') === 'open' ? openFreshdeskWidget() : closeFreshdeskWidget();
                    window.fcWidget.on("widget:closed", function(resp) {
                        $.cookie('freshdesk_chat_state', 'closed');
                    });

                    window.fcWidget.on("widget:opened", function(resp) {
                        $.cookie('freshdesk_chat_state', 'open');
                    });
                });

                window.openFreshdesk = function () {
                    window.fcWidget.isOpen() ? closeFreshdeskWidget() : openFreshdeskWidget();
                }
            }
        }, 100);
    };
    let body = document.body || document.getElementsByTagName('body')[0];
    body.appendChild(freshChatScript);
</script>

