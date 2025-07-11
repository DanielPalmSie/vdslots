<?php

use Videoslots\User\ThirdPartyVerificationFields\Factory\TopPartFactory;

$mbox = new MboxCommon();
$u_obj = $mbox->getUserOrDie();

$box_id = $_POST['box_id'] ?? 'spending_amount_box';
$setting_action = 'viewed-resp-gaming';

if(lic('hasViewedResponsibleGaming', [$u_obj], $u_obj)) {
    die(jsRedirect(phive('Casino')->getBasePath()));
}

$occupation_dropdown_enabled = licSetting('occupation_dropdown_enabled', $u_obj);

$sow = phive('Dmapi')->getUserDocumentsByTag($u_obj->getId(), 'sourceoffundspic');
$current_occupation = '';
if(!empty($sow)) {
    $current_occupation = array_pop($sow)['source_of_funds_data']['occupation'];
}

$occupation_setting = $u_obj->getSetting('occupation');
if (empty($current_occupation) && !empty($occupation_setting)) {
    $current_occupation = $occupation_setting;
}

$cur_lic_iso = lic('getIso');

if($occupation_dropdown_enabled) {
    $industryList = lic('getIndustries', [$u_obj], $u_obj);
    $occupations = lic('getOccupations', [reset($industryList), $u_obj], $u_obj);
}
?>

<div class="lic-mbox-wrapper">
    <?php
    $is_paynplay_mode = isPNP();
    $top_part_data = (new TopPartFactory())->create($box_id, 'rg.spending.popup.title', !$is_paynplay_mode);
    $mbox->topPart($top_part_data);
    ?>
    <div class="lic-mbox-container limits-deposit-set occupation-pop-container  occupation-pop-container-<?= $cur_lic_iso ?>" style="text-align: center">
        <div class="top-description">
            <?php et('rg.spending.popup.top.message') ?>
        </div>
        <?php if($occupation_dropdown_enabled) : ?>
            <h3 class="spending-limit-popup-heading"><?php et('rg.spending.popup.occupation.label') ?> </h3>
            <div class="industry-section w-100-pc">
                <div class="spending-limit-popup-field-label"> <?php et('rg.spending.popup.industry.label') ?> </div>
                <div class="select-wrapper">
                    <select id="select-industry-list" tabindex="0" required>
                        <option value='' disabled selected><?php et('occupational.popup.industry.title') ?></option>
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
                <div class="spending-limit-popup-field-label"> <?php et('rg.spending.popup.job-title.label') ?> </div>
                <div class="w-100-pc job-title-input-container">
                    <input id="occupation" class="new-standard-input" type="text" autocapitalize="off" autocorrect="off" placeholder="<?php et('rg.spending.popup.job-title.input') ?>" value="<?php echo $current_occupation ?>">
                </div>
            </div>
        <?php else: ?>
            <div class="margin-ten">
                <h3 style="color: black"> <?php et('rg.spending.popup.occupation.label') ?> </h3>
                <input id="occupation" class="new-standard-input" type="text" autocapitalize="off" autocorrect="off" placeholder="<?php et('rg.spending.popup.occupation.input') ?>" value="<?php echo $current_occupation ?>">
            </div>
        <?php endif; ?>
        <div id="occupationError" class="error-message"></div>
        <div class="spending-limit-section w-100-pc">
            <h3 class="spending-limit-popup-field-label"> <?php et('rg.spending.popup.spending.label') ?> </h3>
            <input id="spending_amount" class="new-standard-input" type="number" autocapitalize="off" autocorrect="off" placeholder="<?php cs(true) ?>" value="">
        </div>
        <div id="amountError" class="error-message"></div>

        <div class="main-description">
            <?php et('rg.spending.popup.main.message.html') ?>
            <?php dbCheck('tick-ok') ?>
            <span class="cb-label"><?php et('rg.spending.popup.tick.box.label') ?></span>
        </div>
        <div id="checkboxCheck" class="error-message"></div>

        <?php $is_paynplay_mode ? et('rg.spending.popup.bottom.message.html') : ''  ?>
        <?php btnDefaultXl(t('rg.spending.popup.button'), '', "okTickBox('', '$setting_action')", null, 'margin-ten-top') ?>
        <input id="occupation-empty-job-title" style="display: none" value="<?php et('occupational.form.validation.emptyJobTitle') ?>"></input>
        <input id="occupation-valid-job-title" style="display: none" value="<?php et('occupational.form.validation.validJobTitle') ?>"></input>
    </div>
</div>

<script>
    $(document).ready(function(){
       if( isIndustryVisible()) {
            const occupations_list = <?php echo json_encode($occupations); ?>;
            const boxId = '<?php echo $box_id ?>';
            licFuncs.initializeOccupationAutoComplete("#occupation", "#"+boxId , occupations_list);
            licFuncs.industryOnChange("#select-industry-list", boxId, "#occupation");
        }

        $('#spending_amount').on('change blur', function(e){
            e.target.value = getMaxIntValue(e.target.value);
        });
    });

    function okTickBox(url, action) {

        var occupationElement = $('#occupationError');
        var industryElement = $('#industryError');
        var amountElement = $('#amountError');
        var checkboxCheck = $('#checkboxCheck');

        var occupation     = $('#occupation').val();
        var spendingAmount = getMaxIntValue($('#spending_amount').val().replace(/[^\d.,]/g, ''));
        var errorCount = 0;

        // Regex to accept UKGC, Italian and Spanish characters as per the sharepoint document.
        let charset = "[a-zA-ZÀàÈèÌìÒòÙùÁáÉéÍíÓóÚúÑñ]";
        let industry = $('#select-industry-list').val() ?? null;
        const regex = new RegExp(`^${charset}+(?:\\s${charset}+)*$`, "u");

        if(!isIndustryVisible()) {
            occupationElement.text('');
        }
        amountElement.text('');
        checkboxCheck.text('');

        if ($('input[name="tick-ok"]:checked').length === 0) {
            checkboxCheck.text('<?php et('occupational.form.validation.checkboxRequired') ?>');
            errorCount++;
        }

        if(isIndustryVisible() && empty(industry)) {
            industryElement.text('<?php et('occupational.form.validation.emptyIndustry') ?>');
            errorCount++;
        }

        if(empty(occupation) && !$('#occupation').is(":hidden")) {
            if(isIndustryVisible()) {
                occupationElement.text('<?php et('occupational.form.validation.emptyJobTitle') ?>');
            }else {
                occupationElement.text('<?php et('occupational.form.validation.occupationRequired') ?>');
            }
            errorCount++;
        } else if(!isIndustryVisible() && !regex.test(occupation)) {
            occupationElement.text('<?php et('occupational.form.validation.alphaSingleSpace') ?>');
            errorCount++;
        }

        if(empty(spendingAmount)) {
            amountElement.text('<?php et('occupational.form.validation.monthlyBudgetRequired') ?>');
            errorCount++;
        }

        if(!empty(occupationElement.text())) {
            errorCount++;
        }

        if(errorCount > 0) {
            return;
        }

        let data = {occupation: occupation, spending_amount: spendingAmount}

        if (!empty(industry)) {
            data = {...data, industry: industry}
        }
        mgAjax({action: action, ...data}, function(ret){
            if (ret === 'nok') {
                jsReloadBase();
                return;
            }

            if(ret == 'over-limit'){
                mboxClose('<?php echo $box_id ?>');
                var extraOptions = isMobile() ? {} : {width: <?= isPNP() ? 350 : 600 ?>};
                var options = {
                    module: 'Licensed',
                    file: 'loss_is_set_over_max_limit_popup'
                };
                extBoxAjax('get_raw_html', 'loss-is-set-over-max-limit-popup', options, extraOptions);
                return;
            }

            var rg_callback = '<?= phive('Licensed')->getRedirectBackToLinkAfterRgPopup() ?>';

            <?php if(!empty($_POST['gid'])): ?>
            playGameDepositCheckBonus('<?=$_POST['gid']?>');
            <?php else: ?>
            window.location.href = rg_callback;
            <?php endif; ?>

            mboxClose('<?php echo $box_id ?>');
        });
    }

    function isIndustryVisible() {
        return $("#select-industry-list").length;
    }
</script>
