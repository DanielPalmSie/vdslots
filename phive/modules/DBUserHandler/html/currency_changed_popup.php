<?php

use Videoslots\User\ThirdPartyVerificationFields\Factory\TopPartFactory;

$mbox = new MboxCommon();
$box_id = $_POST['box_id'] ?? 'currency-changed-popup';
$top_part_data = (new TopPartFactory())->create($box_id, 'currency-changed.popup.header.title');

$user = $mbox->getUserOrDie();
$currency_changed_from = $user->getSetting('currency_changed_from');
$currency_changed_to = $user->getSetting('currency_changed_to');

$description_alias = "currency-changed.popup.description.$currency_changed_from.to.$currency_changed_to";
$description = t($description_alias, null, false);
$alias_value_exists = $description !== "($description_alias)";

if (!$alias_value_exists) {
    $description = t2(
        'currency-changed.popup.description.default',
        ['currency_from' => $currency_changed_from, 'currency_to' => $currency_changed_to]
    );
}

?>

<div class="lic-mbox-wrapper">
    <?php $mbox->topPart($top_part_data); ?>
    <div class="lic-mbox-container">
        <div>
            <div class="currency-changed-popup__image-wrapper">
                <img
                    class="currency-changed-popup__image"
                    src="/diamondbet/images/<?= brandedCss() ?>warning.png"
                    alt="warning"
                >
            </div>
            <h6 class="currency-changed-popup__title">
                <?= t('currency-changed.popup.title') ?>
            </h6>
            <div class="currency-changed-popup__description">
                <?= $description ?>
            </div>
        </div>
        <?php btnDefaultXl(t('currency-changed.popup.button.text'), '', '', '', 'currency-changed-popup__button') ?>
    </div>
</div>

<script>
    $(function() {
        const boxId = '<?= $box_id ?>';

        $(`#${boxId} .lic-mbox-close-box, .currency-changed-popup__button`).on('click', function() {
            mgAjax({action: 'remove_currency_changed_settings'}, () => {});
            mboxClose(boxId);
        });
    });
</script>
