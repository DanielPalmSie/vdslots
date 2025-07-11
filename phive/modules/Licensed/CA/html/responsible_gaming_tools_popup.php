<div>
    <div class="center-stuff">
        <p class="responsible-gaming-desc">
            <?php et('responsible.gaming.tools.description') ?>
        </p>
    </div>
    <br/>
    <div>
        <button class="btn btn-l btn-default-l responsible-gambling-btn" onclick="handleRgToolsResponseYes('responsible-gaming-popup-box')">
            <?php et('yes') ?>
        </button>
        <button class="btn btn-l no-btn responsible-gambling-btn" onclick="handleRgToolsResponseNo()"><?php et('no') ?></button>
    </div>
</div>

<script>
    function handleRgToolsResponseYes(id) {
        sendRgToolsResponse('yes');

        closeResponsibleGamingPopup(id);
    }

    function handleRgToolsResponseNo() {
        sendRgToolsResponse('no');

        licFuncs.provincePopupHandler().showResponsibleGamingMessagePopup();
    }

    function sendRgToolsResponse(answer) {
        const ajaxUrl = '/phive/modules/Micro/ajax.php';
        $.post(ajaxUrl, {action: 'rg-tools-answer', answer: answer});
    }

    function closeResponsibleGamingPopup(id) {
        mgAjax({action: "close-responsible-gaming-popup-box"}, function (response) {
            isMobileDevice() ? location.reload() : closePopup( id, true, false);
        });
    }
</script>
