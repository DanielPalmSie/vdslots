<div class="center-stuff">
    <div class="chat-offline-form">
        <img alt="Chat unavailable" src="/diamondbet/images/modals/error.svg" width="260" height="220">
        <div class="chat-offline-message">
            <h3 style="color: #000"><?php et('chat.offline.message.subtitle') ?></h3>
            <?php et('chat.offline.message.description') ?>
        </div>
        <br>
        <div>
            <?php btnDefaultL(t("Ok"), "", "closePopup('mbox-msg', true, false)", phive()->isMobile() ? "" : 210, "chat-offline__button") ?>
        </div>
    </div>
</div>
