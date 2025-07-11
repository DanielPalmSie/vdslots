<?php
/**
 * This file holds the HTML for the source_of_funds form,
 * and can be included from phive and from admin2.
 *
 * Changes in this version:
 * - Your savings was added
 */
use Videoslots\User\ThirdPartyVerificationFields\Factory\TopPartFactory;

$mbox = new MboxCommon();
$u_obj = $mbox->getUserOrDie();

if($u) {
    $u_obj = $u;
}

$box_id = $_POST['box_id'] ?? 'sourceoffunds-box';

$occupation_setting = $u_obj->getSetting('occupation');
$current_occupation = !empty($occupation) ? $occupation : $occupation_setting;
if (empty($current_occupation)) {
    $current_occupation = '';
}

$industry_setting = $u_obj->getSetting('industry');
$current_industry = !empty($industry_setting) ? $industry_setting : '';

$occupation_dropdown_enabled = licSetting('occupation_dropdown_enabled', $u_obj);

if($occupation_dropdown_enabled) {
    $industryList = lic('getIndustries', [$u_obj], $u_obj);
    $occupations = lic('getOccupations', [reset($industryList), $u_obj], $u_obj);
}

$submitFunctionCall = $submit_function_name . '(' . json_encode($occupation_dropdown_enabled) . ')';

$is_paynplay_mode = isPNP();

?>

<div class="registration-container">
    <div class="registration-content">
        <form id="sourceoffundsbox_step1" action="" method="post">
            <input type="hidden" name="token" value="<?php echo $_SESSION['token'];?>">

            <div class="sourceoffundsbox-top">
                <?php et('please.fill.in'); ?>
                /
                <?php et('confidential'); ?>
            </div>

            <div class="clear"></div>

            <p class="sourceoffundsbox__header"><?php et('source.of.funds.header'); ?></p>
            <div class="sourceoffundsbox-content">
                <div class="sourceoffundsbox-content-left">

                    <p class="sourceoffundsbox__declare"><?php et('i.wish.to.declare'); ?></p>

                    <!-- name_of_account_holder -->
                    <div class="account-holder-field">
                        <div class="input-label-1">
                            <?php et('declaration.part.1'); ?>
                        </div>
                        <input id="name_of_account_holder"
                               class="input-normal input-sourceoffunds-1"
                               name="name_of_account_holder"
                               type="text"
                               autocapitalize="off"
                               autocorrect="off"
                               value="<?php echo $full_name;?>" <?php if (!$can_edit_form) echo 'disabled'; ?>/>
                    </div>
                    <div class="formfield_note">
                        (<?php et('name.of.account.holder'); ?>)
                    </div>
                    <div class="clear"></div>

                    <!-- address -->
                    <div class="account-holder-field">
                        <div class="input-label-1">
                            <?php et('declaration.part.2'); ?>
                        </div>
                        <input id="address"
                               class="input-normal input-sourceoffunds-1"
                               name="address"
                               type="text"
                               autocapitalize="off"
                               autocorrect="off"
                               value="<?php echo $address; ?>" <?php if (!$can_edit_form) echo 'disabled'; ?>/>
                    </div>
                    <div class="formfield_note">
                        (<?php et('address'); ?>)
                    </div>
                    <div class="clear"></div>

                    <p class="sourceoffundsbox__declaration"><?php et('declaration.part.3'); ?></p>

                    <!-- funding_methods -->
                    <div id="funding_methods" class="checkbox-group">
                        <div class="checkbox-float-left">
                            <input id="salary"        name="salary"       type="checkbox" <?php if (!$can_edit_form) echo 'disabled'; ?> />
                            <?php et('salary'); ?>
                        </div>
                        <div class="checkbox-float-left">
                            <input id="business"      name="business"     type="checkbox" <?php if (!$can_edit_form) echo 'disabled'; ?> />
                            <?php et('business'); ?>
                        </div>
                        <div class="checkbox-float-left">
                            <input id="income"        name="income"       type="checkbox" <?php if (!$can_edit_form) echo 'disabled'; ?> />
                            <?php et('income'); ?>
                        </div>
                        <div class="checkbox-float-left">
                            <input id="dividend"      name="dividend"     type="checkbox" <?php if (!$can_edit_form) echo 'disabled'; ?> />
                            <?php et('dividend'); ?>
                        </div>
                        <div class="checkbox-float-left">
                            <input id="interest"      name="interest"     type="checkbox" <?php if (!$can_edit_form) echo 'disabled'; ?> />
                            <?php et('interest'); ?>
                        </div>
                        <div class="checkbox-float-left">
                            <input id="gifts"         name="gifts"        type="checkbox" <?php if (!$can_edit_form) echo 'disabled'; ?> />
                            <?php et('gifts'); ?>
                        </div>
                        <div class="checkbox-float-left">
                            <input id="pocket_money"  name="pocket_money" type="checkbox" <?php if (!$can_edit_form) echo 'disabled'; ?> />
                            <?php et('pocket.money'); ?>
                        </div>

                    </div>

                    <div class="clear"></div>


                    <!-- others -->
                    <div class="account-holder-field other-optional">
                        <div class="input-label-2">
                            <div>
                                <?php et('others'); ?>
                                <span>(<?php et('optional'); ?>)</span>
                            </div>
                        </div>
                        <input id="others"
                               class="input-normal input-sourceoffunds-2 input-declare"
                               name="others"
                               type="text"
                               autocapitalize="off"
                               autocorrect="off"
                               value="<?php echo $others;?>" <?php if (!$can_edit_form) echo 'disabled'; ?>/>
                    </div>

                    <div class="clear"></div>

                    <?php if($occupation_dropdown_enabled) : ?>
                        <div class="account-holder-field industry-container">
                            <div class="input-label-2">
                                <?php et('sowd_popup.form.industry_label'); ?>
                            </div>
                            <span class="styled-select">
                                <?php
                                dbSelect(
                                    'industry',
                                    $industryList,
                                    $current_industry,
                                    array(),
                                    'styled-select',
                                    false,
                                    'tabindex="0" ' . (!$can_edit_form ? 'disabled' : ''),
                                    'industry'
                                );
                                ?>
                            </span>
                        </div>
                        <div class="formfield_note">
                        </div>

                        <div class="account-holder-field job-title">
                            <div class="input-label-2">
                                <?php et('job.title'); ?>
                            </div>
                            <div class="select-wrapper">
                                <input id="occupation"
                                       class="new-standard-input input-disabled"
                                       type="text"
                                       autocapitalize="off"
                                       autocorrect="off"
                                       placeholder="<?php et('occupation-loading-job-titles') ?>"
                                       value="<?php echo $current_occupation ?>" <?php if (!$can_edit_form) echo 'disabled'; ?> />

                                <input id="occupation-job-title" style="display: none" value="<?php et('occupational.popup.job-title.input') ?>" />
                                <input id="occupation-empty-job-title" style="display: none" value="<?php et('occupational.form.validation.emptyJobTitle') ?>" />
                                <input id="occupation-valid-job-title" style="display: none" value="<?php et('occupational.form.validation.validJobTitle') ?>" />
                                <input id="occupation-loading-job-titles" type="hidden" value="<?php et('occupational.form.loading.jobTitles') ?>" />
                                <input id="occupation-error-loading" type="hidden" value="<?php et('occupational.form.error.loading') ?>" />
                                <input id="occupation-error-loading-try-again" type="hidden" value="<?php et('occupational.form.error.loading.tryAgain') ?>" />
                            </div>
                            <div id="occupationError" class="error-message"></div>
                        </div>

                    <?php else : ?>
                        <!-- Default Occupation field for other jurisdictions -->
                        <div class="account-holder-field occupation-input">
                            <div class="input-label-2">
                                <?php et('occupation'); ?>
                            </div>
                            <input id="occupation"
                                   class="input-normal input-sourceoffunds-2 input-declare"
                                   name="occupation"
                                   type="text"
                                   autocapitalize="off"
                                   autocorrect="off"
                                   value="<?php echo htmlspecialchars($current_occupation); ?>" <?php if (!$can_edit_form) echo 'disabled'; ?> />
                        </div>
                    <?php endif; ?>
                    <div class="clear"></div>

                </div>
                <div class="sourceoffundsbox-content-right">

                    <div class="formfield_note formfield_note-2 sourceoffundsbox__income-title">
                        <p><?php et('your.annual.income'); ?></p> <span>(<?php et('provide.details'); ?>)</span>
                    </div>

                    <div id="annual_income" class="checkbox-group">
                        <div class="radio-1">
                            <input id="annual_income_1"        name="annual_income"       type="radio"
                                   value="<?php echo $annual_income_options['annual_income_1'];?>"
                                <?php if (!$can_edit_form) echo 'disabled'; ?> />
                            <?php echo $annual_income_options['annual_income_1'];?>
                        </div>
                        <div class="radio-1">
                            <input id="annual_income_2"        name="annual_income"       type="radio"
                                   value="<?php echo $annual_income_options['annual_income_2'];?>"
                                <?php if (!$can_edit_form) echo 'disabled'; ?> />
                            <?php echo $annual_income_options['annual_income_2'];?>
                        </div>
                        <div class="radio-1">
                            <input id="annual_income_3"        name="annual_income"       type="radio"
                                   value="<?php echo $annual_income_options['annual_income_3'];?>"
                                <?php if (!$can_edit_form) echo 'disabled'; ?> />
                            <?php echo $annual_income_options['annual_income_3'];?>
                        </div>
                        <div class="radio-1">
                            <input id="annual_income_4"        name="annual_income"       type="radio"
                                   value="<?php echo $annual_income_options['annual_income_4'];?>"
                                <?php if (!$can_edit_form) echo 'disabled'; ?> />
                            <?php echo $annual_income_options['annual_income_4'];?>
                        </div>
                        <div class="radio-1">
                            <input id="annual_income_5"        name="annual_income"       type="radio"
                                   value="<?php echo $annual_income_options['annual_income_5'];?>"
                                <?php if (!$can_edit_form) echo 'disabled'; ?> />
                            <?php echo $annual_income_options['annual_income_5'];?>
                        </div>
                        <div class="radio-1">
                            <input id="annual_income_6"        name="annual_income"       type="radio"
                                   value="<?php echo $annual_income_options['annual_income_6'];?>"
                                <?php if (!$can_edit_form) echo 'disabled'; ?> />
                            <?php echo $annual_income_options['annual_income_6'];?>
                        </div>
                    </div>

                    <div class="clear"></div>

                    <div class="formfield_note sourceoffundsbox__no-income-text">
                        <?php et('explain.no.income'); ?>
                    </div>
                    <div class="formfield_note formfield_note-2 optional-text">
                        (<?php et('optional'); ?>)
                    </div>

                    <div class="sourceoffundsbox__no_income_text_area">
                        <label for="no_income_explanation">
                            <textarea id="no_income_explanation" <?php if (!$can_edit_form) echo 'disabled'; ?> class="required" style="resize: none" name="no_income_explanation"><?php echo $no_income_explanation; ?></textarea>
                        </label>
                    </div>
                    <div class="formfield_note formfield_note-2 sourceoffundsbox__other-income-title">
                        <p><?php et('your.savings'); ?></p> <span>(<?php et('provide.details.of.savings'); ?>)</span>
                    </div>

                    <!--your_savings-->
                    <div id="your_savings" class="checkbox-group">
                        <div class="radio-1">
                            <input id="your_savings_1"        name="your_savings"       type="radio"
                                   value="<?php echo $your_savings_options['your_savings_1'];?>"
                                <?php if (!$can_edit_form) echo 'disabled'; ?>   />
                            <?php echo $your_savings_options['your_savings_1'];?>
                        </div>
                        <div class="radio-1">
                            <input id="your_savings_2"        name="your_savings"       type="radio"
                                   value="<?php echo $your_savings_options['your_savings_2'];?>"
                                <?php if (!$can_edit_form) echo 'disabled'; ?>   />
                            <?php echo $your_savings_options['your_savings_2'];?>
                        </div>
                        <div class="radio-1">
                            <input id="your_savings_3"        name="your_savings"       type="radio"
                                   value="<?php echo $your_savings_options['your_savings_3'];?>"
                                <?php if (!$can_edit_form) echo 'disabled'; ?>   />
                            <?php echo $your_savings_options['your_savings_3'];?>
                        </div>
                        <div class="radio-1">
                            <input id="your_savings_4"        name="your_savings"       type="radio"
                                   value="<?php echo $your_savings_options['your_savings_4'];?>"
                                <?php if (!$can_edit_form) echo 'disabled'; ?>   />
                            <?php echo $your_savings_options['your_savings_4'];?>
                        </div>
                        <div class="radio-1">
                            <input id="your_savings_5"        name="your_savings"       type="radio"
                                   value="<?php echo $your_savings_options['your_savings_5'];?>"
                                <?php if (!$can_edit_form) echo 'disabled'; ?>   />
                            <?php echo $your_savings_options['your_savings_5'];?>
                        </div>
                        <div class="radio-1">
                            <input id="your_savings_6"        name="your_savings"       type="radio"
                                   value="<?php echo $your_savings_options['your_savings_6'];?>"
                                <?php if (!$can_edit_form) echo 'disabled'; ?>   />
                            <?php echo $your_savings_options['your_savings_6'];?>
                        </div>
                    </div>

                    <div class="clear"></div>

                    <div style="display: none">
                        <label for="savings_explanation">
                            <textarea id="savings_explanation" class="required" name="savings_explanation"><?php echo $savings_explanation; ?></textarea>
                        </label>
                    </div>
                </div>
            </div>

            <div class="clear"></div>

            <p class="sourceoffundsbox__legit-text"><?php et('confirm.funds.are.legit'); ?></p>
            <p class="sourceoffundsbox__declare-text"><?php et('declare.details.true'); ?></p>
            <div class="clear"></div>
            <div class="sourceoffundsbox__signature-text">
                <p><?php et('signature.of.account.holder'); ?></p>
            </div>
            <div class="sourceoffundsbox-content">
                <div class="sourceoffundsbox-content-left">
                    <div class="account-holder-field name-container">
                        <div class="input-label-2">
                            <?php et('name'); ?>
                        </div>
                        <input id="name"
                               class="input-normal input-sourceoffunds-2"
                               name="name"
                               type="text"
                               autocapitalize="off"
                               autocorrect="off"
                               value="<?php echo $name; ?>" <?php if (!$can_edit_form) echo 'disabled'; ?> />
                    </div>

                    <!-- date -->
                    <div id="date-container">
                        <div id="date-title" class="input-label-2">
                            <?php echo t('date') ?>
                        </div>
                        <label>
                            <span class="styled-select">
                                <?php dbSelect("submission_day", $fc->getDays(), $day, array('', t('day')), '', false, '', true, !$can_edit_form) ?>
                            </span>
                            <span class="styled-select">
                                <?php dbSelect("submission_month", $fc->getFullMonths(), $month, array('', t('month')), '', false, '', true, !$can_edit_form) ?>
                            </span>
                            <span class="styled-select" id="year-cont">
                                <?php dbSelect("submission_year", $fc->getYears(), $year, array('', t('year')), '', false, '', true, !$can_edit_form ); ?>
                            </span>

                        </label>
                    </div>

                </div>
                <div class="sourceoffundsbox-content-right">
                    <input type="hidden" id="document_id"  name="document_id"  value="<?php echo $document_id; ?>"/>
                    <input type="hidden" id="user_id"      name="user_id"      value="<?php echo $user_id; ?>"/>
                    <input type="hidden" id="form_version" name="form_version" value="1.1"/>

                    <!-- submit -->
                    <?php // Dont show submit button for admin users that don't have the permission to update this form  ?>
                    <?php if($can_submit): ?>
                        <div id="submit_source_of_funds_form" class="submit_source_button" onclick="<?php echo $submitFunctionCall;; ?>">
                            <div<?php if(phive()->isMobile()) { echo ' class="register-big-btn-txt register-button-emptydob-mobile"'; } ?>>
                                <?php et('send.declaration.form'); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
            <div class="clear"></div>

        </form>

        <div class="sourceoffundsbox-footer-left">
            <?php et('footer.address'); ?>
        </div>

        <div class="sourceoffundsbox-footer-right">
            <?php et('confidential'); ?>
        </div>
    </div>

</div>


<script>
    $(document).ready(function() {
        if (!document.getElementById('source_of_funds_modal')) {
            const $occupationInput = $("#occupation");
            $occupationInput.removeClass('input-disabled');
            const occupations_list = <?php echo json_encode($occupations); ?>;
            const boxId = '<?php echo $box_id ?>';

            licFuncs.initializeOccupationAutoComplete("#occupation", "#" + boxId, occupations_list, () => {
                setTimeout(()=> {
                    $occupationInput
                        .prop('disabled', false)
                        .removeClass('input-loading')
                        .attr('placeholder', $("#occupation-job-title").val());
                }, 1000);
            });

            licFuncs.industryOnChange("#industry", boxId, "#occupation");
        }
    });
</script>


