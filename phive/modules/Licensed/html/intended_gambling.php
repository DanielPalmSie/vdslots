<?php

use Videoslots\IntendedGamblingPopup\Services\IntendedGamblingPopupService;
use Videoslots\User\ThirdPartyVerificationFields\Factory\TopPartFactory;

$box_id = 'intended_gambling';
$mbox = new MboxCommon();
$user = cu();

$intendedGambling = new IntendedGamblingPopupService();
/**
 * @var \Laraphive\Domain\User\DataTransferObjects\IntendedGamblingPopup\HeaderData $header
 * @var \Laraphive\Domain\User\DataTransferObjects\IntendedGamblingPopup\ContainerData $container
 * @var \Laraphive\Domain\User\DataTransferObjects\IntendedGamblingPopup\ButtonData $footer
 */
['header' => $header, 'container' => $container, 'footer' => $footer] = $intendedGambling->getIntendedGamblingPopupSections($user);
?>

<div class="lic-mbox-wrapper version2 intended-gambling">

    <?php
    $top_part_data = (new TopPartFactory())->create($box_id, $header->getHeadline(), true);
    $mbox->topPart($top_part_data);
    ?>

    <div class="lic-mbox-container">

        <div class="left center-stuff">
            <img src="<?= $header->getImage() ?>" alt="">
            <h3><?= t($header->getTitle()) ?></h3>
            <p><?= t($header->getDescription()) ?></p>
        </div>

        <div class="right">
            <div class="container">
                <p><?= t($header->getFormTitle()) ?>
                    <span><?= "({$container->getCurrency()})"; ?></span></p>
                <div class="ranges">
                    <? foreach ($container->getRanges() as $k => $range): ?>
                        <? if (empty($range['value'])): ?>
                            <div class="range">
                                <input type="radio" id="range<?= $k ?>" name="range" value="<?= $range['value'] ?>">
                                <label id="range-label-<?= $k ?>" for="range<?= $k ?>">
                                    <input type="number" placeholder="<?= t($range['label']) ?>" class="custom-value">

                                </label>
                            </div>
                        <? else: ?>
                            <div class="range">
                                <input type="radio" id="range<?= $k ?>" name="range" value="<?= $range['value'] ?>">
                                <label id="range-label-<?= $k ?>" for="range<?= $k ?>"><?= $range['label'] ?></label>
                            </div>
                        <? endif; ?>
                    <? endforeach; ?>
                </div>
                <div class="error-content error hidden">
                    <div style="clear:both;"></div>
                    <br>
                    <span></span>
                </div>
                <button class="lic-mbox-btn lic-mbox-btn-active"
                        id="submit-form"><?= t($footer->getAlias()) ?></button>
            </div>
        </div>
        <div style="clear:both;"></div>

    </div>
</div>
<br>
<script>
    $(".intended-gambling .ranges .range .custom-value").keyup(function () {
        $(this).parent().parent().find("[type='radio']").attr('value', $(this).val() > 0 ? "1-" + $(this).val() : '');
    });

    $(".intended-gambling .ranges .range").click(function () {
        $(".intended-gambling .ranges [type='radio']").removeAttr('checked');
        $(".intended-gambling .ranges .range").removeClass('is-checked');
        $(this).find("[type='radio']").prop('checked', true);
        $(this).addClass('is-checked');
    });

    $(".intended-gambling .range").first().find('input').prop('checked', true);

    $(".intended-gambling button").click(function () {
        saveAccCommon('intended_gambling', {range: $("[name='range']:checked").val()}, function (res) {
            if (!empty(res.msg)) {
                $(".intended-gambling .error").removeClass('hidden');
                $(".intended-gambling .error span").text(res.msg);
                hideLoader();
            } else {
                //Some kind of error etc.
                jsReloadBase();
            }
        });
    })

</script>
