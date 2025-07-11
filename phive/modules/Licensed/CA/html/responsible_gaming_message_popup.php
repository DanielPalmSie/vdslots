<div>
    <div class="center-stuff">
        <p class="responsible-gaming-message-desc">
            <?php et('responsible.gaming.message.description') ?>
        </p>
        <p class="responsible-gaming-message-desc">
            <?php echo tAssoc('responsible.gaming.visit.page.description.html',
                [
                        'responsibleGamingUrl'=> phive('Licensed')->getRespGamingUrl(),
                        'accountResponsibleGamingUrl' => phive('Licensed')->getRespGamingUrl(cu())
                ]) ?>
        </p>
        <p class="responsible-gaming-message-desc">
            <?php et('responsible.gaming.contact.description.html') ?>
        </p>
    </div>
    <br/>
    <div>
        <button class="btn btn-l btn-default-l responsible-gambling-message-btn" onclick="licFuncs.provincePopupHandler().closeResponsibleGamingTool()"><?php et('ok') ?></button>
    </div>
</div>