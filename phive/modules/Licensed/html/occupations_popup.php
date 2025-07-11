<?php

use Videoslots\User\ThirdPartyVerificationFields\Factory\TopPartFactory;

$mbox = new MboxCommon();
$u_obj = $mbox->getUserOrDie();

$box_id = $_POST['box_id'] ?? 'occupation-popup-box';
$setting_action = 'update-occupation-data';

if (lic('hasViewedOccupationPopup', [$u_obj], $u_obj)) {
    die(jsRedirect(phive('Casino')->getBasePath()));
}

$industryList = lic('getIndustries', [$u_obj], $u_obj);
$occupations = lic('getOccupations', [reset($industryList), $u_obj], $u_obj);

?>

<div class="lic-mbox-wrapper">
    <?php
    $is_paynplay_mode = isPNP();
    $top_part_data = (new TopPartFactory())->create($box_id, 'occupational.popup.title', !$is_paynplay_mode);
    $mbox->topPart($top_part_data);
    ?>
    <div class="lic-mbox-container limits-deposit-set occupation-pop-container occupations-popup  occupation-popup-container-section" style="text-align: center">
        <div class="occupations-popup__main-content">
            <div class="top-description">
                <?php et('occupational.popup.top.message') ?>
            </div>
            <div class="occupations-popup-fields">
                <div class="industry-section w-100-pc">
                    <div class="select-wrapper">
                        <select id="select-industry-list" tabindex="0" required>
                            <option value='' disabled selected hidden><?php et('occupational.popup.industry.title') ?></option>
                            <?php foreach ($industryList as $key => $industry): ?>
                                <option value="<?= $industry; ?>">
                                    <?= $industry; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div id="industryError" class="error-message"></div>
                <div class="job-title-section w-100-pc hidden-force">
                    <div class="w-100-pc job-title-input-container">
                        <input id="occupation" class="new-standard-input" type="text" autocapitalize="off" autocorrect="off" value="" required placeholder="<?php et('occupational.popup.job-title.input') ?>">
                    </div>
                </div>
                <div id="occupationError" class="error-message"></div>
            </div>
        </div>
        <?php btnDefaultXl(t('rg.spending.popup.button'), '', "okTickBox('', '$setting_action')", null) ?>
        <input id="occupation-empty-job-title" style="display: none" value="<?php et('occupational.form.validation.emptyJobTitle') ?>"></input>
        <input id="occupation-valid-job-title" style="display: none" value="<?php et('occupational.form.validation.validJobTitle') ?>"></input>
        <input id="occupation-loading-job-titles" style="display: none" value="<?php et('occupational.form.loading.jobTitles') ?>"></input>
        <input id="occupation-error-loading" style="display: none" value="<?php et('occupational.form.error.loading') ?>"></input>
        <input id="occupation-error-loading-try-again" style="display: none" value="<?php et('occupational.form.error.loading.tryAgain') ?>"></input>
    </div>
</div>

<script>
    $(document).ready(function() {

        const occupations_list = <?php echo json_encode($occupations); ?>;
        const boxId = '<?php echo $box_id ?>';
        licFuncs.initializeOccupationAutoComplete("#occupation", "#" + boxId, occupations_list);
        licFuncs.industryOnChange("#select-industry-list", boxId, "#occupation");
    });

    function okTickBox(url, action) {

        var occupationElement = $('#occupationError');
        var industryElement = $('#industryError');

        var occupation = $('#occupation').val();
        var industry = $('#select-industry-list').val();
        var errorCount = 0;

        if (empty(industry)) {
            industryElement.text('<?php et('occupational.form.validation.emptyIndustry') ?>');
            errorCount++;
        }
        if (empty(occupation) && !$('#occupation').is(":hidden")) {
            occupationElement.text('<?php et('occupational.form.validation.emptyJobTitle') ?>');
            errorCount++;
        }

        if (!empty(occupationElement.text())) {
            errorCount++;
        }

        if (errorCount > 0) {
            return;
        }

        mgAjax({
            action: action,
            occupation: occupation,
            industry: industry
        }, function(ret) {
            if (ret === 'nok') {
                jsReloadBase();
                return;
            }
            mboxClose('<?php echo $box_id ?>');
        });
    }
</script>