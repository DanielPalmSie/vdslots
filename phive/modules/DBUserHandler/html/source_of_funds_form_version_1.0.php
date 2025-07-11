<?
/**
 * This file holds the HTML for the source_of_funds form,
 * and can be included from phive and from admin2.
 */
?>

<div class="registration-container">
    <div class="registration-content">
        <form id="sourceoffundsbox_step1" action="" method="post">
            <input type="hidden" name="token" value="<?php echo $_SESSION['token'];?>">

            <div class="sourceoffundsbox-top">
                <?php et('please.fill.in'); ?>
                &nbsp;&nbsp;/&nbsp;&nbsp;
                <?php et('confidential'); ?>
            </div>

            <div class="clear"></div>

            <p style="margin-right: 20px;"><?php et('source.of.funds.header'); ?></p>
            <p><?php et('i.wish.to.declare'); ?></p>

            <div class="sourceoffundsbox-content-left">

                <!-- name_of_account_holder -->
                <div>
                    <div class="input-label-1">
                        <?php et('declaration.part.1'); ?>
                    </div>
                    <input id="name_of_account_holder"
                           class="input-normal input-sourceoffunds-1"
                           name="name_of_account_holder"
                           type="text"
                           autocapitalize="off"
                           autocorrect="off"
                           value="<?php echo $full_name;?>" />
                </div>
                <div class="formfield_note">
                    (<?php et('name.of.account.holder'); ?>)
                </div>
                <div class="clear"></div>

                <!-- address -->
                <div>
                    <div class="input-label-1">
                        <?php et('declaration.part.2'); ?>
                    </div>
                    <input id="address"
                           class="input-normal input-sourceoffunds-1"
                           name="address"
                           type="text"
                           autocapitalize="off"
                           autocorrect="off"
                           value="<?php echo $address; ?>" />
                </div>
                <div class="formfield_note">
                    (<?php et('address'); ?>)
                </div>
                <div class="clear"></div>

                <p style="margin-right: 20px;"><?php et('declaration.part.3'); ?></p>

                <!-- funding_methods -->
                <div id="funding_methods" class="checkbox-group">
                    <div class="checkbox-float-left">
                        <input id="salary"        name="salary"       type="checkbox"/>
                            <?php et('salary'); ?>
                    </div>
                    <div class="checkbox-float-left">
                        <input id="business"      name="business"     type="checkbox"/>
                            <?php et('business'); ?>
                    </div>
                    <div class="checkbox-float-left">
                        <input id="income"        name="income"       type="checkbox"/>
                            <?php et('income'); ?>
                    </div>
                    <div class="checkbox-float-left">
                        <input id="dividend"      name="dividend"     type="checkbox"/>
                            <?php et('dividend'); ?>
                    </div>
                    <div class="checkbox-float-left">
                        <input id="interest"      name="interest"     type="checkbox"/>
                            <?php et('interest'); ?>
                    </div>
                    <div class="checkbox-float-left">
                        <input id="gifts"         name="gifts"        type="checkbox"/>
                            <?php et('gifts'); ?>
                    </div>
                    <div class="checkbox-float-left">
                        <input id="pocket_money"  name="pocket_money" type="checkbox"/>
                            <?php et('pocket.money'); ?>
                    </div>

                </div>

                <div class="clear"></div>


                <!-- others -->
                <div>
                    <div class="input-label-2">
                        <div class="" style="height: 40px;">
                            <div style="height: 10px;"></div>
                            <?php et('others'); ?>
                            <div class="formfield_note formfield_note-2">
                                (<?php et('optional'); ?>)
                            </div>
                        </div>
                    </div>
                    <input id="others"
                           class="input-normal input-sourceoffunds-2"
                           name="others"
                           type="text"
                           autocapitalize="off"
                           autocorrect="off"
                           value="<?php echo $others;?>" />
                </div>

                <div class="clear"></div>

            </div>

            <div class="sourceoffundsbox-content-right">
                <div>
                    <div class="input-label-2">
                        <?php et('occupation'); ?>
                    </div>
                    <input id="occupation"
                           class="input-normal input-sourceoffunds-2"
                           name="occupation"
                           type="text"
                           autocapitalize="off"
                           autocorrect="off"
                           value="<?php echo $occupation;?>" />
                </div>
                <div class="formfield_note">
                </div>
                <div class="clear"></div>

                <br>
                <?php et('your.annual.income'); ?>
                <div class="formfield_note formfield_note-2">
                    (<?php et('provide.details'); ?>)
                </div>

                <div id="annual_income" class="checkbox-group">
                    <div class="radio-1">
                        <input id="annual_income_1"        name="annual_income"       type="radio"
                               value="<?php echo $annual_income_options['annual_income_1'];?>"
                               />
                        <?php echo $annual_income_options['annual_income_1'];?>
                    </div>
                    <div class="radio-2">
                        <input id="annual_income_2"        name="annual_income"       type="radio"
                               value="<?php echo $annual_income_options['annual_income_2'];?>"
                               />
                        <?php echo $annual_income_options['annual_income_2'];?>
                    </div>
                    <div class="radio-2">
                        <input id="annual_income_3"        name="annual_income"       type="radio"
                               value="<?php echo $annual_income_options['annual_income_3'];?>"
                               />
                        <?php echo $annual_income_options['annual_income_3'];?>
                    </div>
                    <div class="radio-2">
                        <input id="annual_income_4"        name="annual_income"       type="radio"
                               value="<?php echo $annual_income_options['annual_income_4'];?>"
                               />
                        <?php echo $annual_income_options['annual_income_4'];?>
                    </div>
                    <div class="radio-2">
                        <input id="annual_income_5"        name="annual_income"       type="radio"
                               value="<?php echo $annual_income_options['annual_income_5'];?>"
                               />
                        <?php echo $annual_income_options['annual_income_5'];?>
                    </div>
                    <div class="radio-1">
                        <input id="annual_income_6"        name="annual_income"       type="radio"
                               value="<?php echo $annual_income_options['annual_income_6'];?>"
                               />
                        <?php echo $annual_income_options['annual_income_6'];?>
                    </div>
                </div>

                <div class="clear"></div>

                <div class="formfield_note formfield_note-4">
                    <?php et('explain.no.income'); ?>
                </div>
                <div class="formfield_note formfield_note-2 formfield_note-4">
                    (<?php et('optional'); ?>)
                </div>

                <div>
                    <label for="no_income_explanation">
                        <textarea id="no_income_explanation" class="required" name="no_income_explanation"><?php echo $no_income_explanation; ?></textarea>
                    </label>
                </div>
            </div>

            <div class="clear"></div>

            <p style="margin-right: 20px;"><?php et('confirm.funds.are.legit'); ?></p>
            <p><?php et('declare.details.true'); ?></p>
            <div style="display: inline-block; width: 400px;">
                <p><strong><?php et('signature.of.account.holder'); ?></strong></p>
            </div>
            <div style="display: inline-block; width: 460px; text-align: right; font-size: 10px;">
                (<?php et('enter.account.password'); ?>)
            </div>

            <div class="clear"></div>

            <div class="sourceoffundsbox-content-left">
                <div>
                    <div class="input-label-2">
                        <?php et('name'); ?>
                    </div>
                    <input id="name"
                           class="input-normal input-sourceoffunds-2"
                           name="name"
                           type="text"
                           autocapitalize="off"
                           autocorrect="off"
                           value="<?php echo $name; ?>" />
                </div>

                <!-- date -->
                <div id="date-container">
                    <label>
                        <div id="date-title" class="input-label-2">
                            <?php echo t('date') ?>
                        </div>
                        <span class="styled-select">
                            <?php dbSelect("submission_day", $fc->getDays(), $day, array('', t('day'))) ?>
                        </span>
                        <span class="styled-select">
                            <?php dbSelect("submission_month", $fc->getFullMonths(), $month, array('', t('month'))) ?>
                        </span>
                        <span class="styled-select" id="year-cont">
                            <?php dbSelect("submission_year", $fc->getYears(), $year, array('', t('year'))); ?>
                        </span>

                    </label>
                </div>

            </div>

            <div class="sourceoffundsbox-content-right">
                <div class="clear"></div>
                <input type="hidden" id="document_id"  name="document_id"  value="<?php echo $document_id; ?>"/>
                <input type="hidden" id="user_id"      name="user_id"      value="<?php echo $user_id; ?>"/>
                <input type="hidden" id="form_version" name="form_version" value="<?php echo $current_version; ?>"/>

                <!-- submit -->
                <?php // Dont show submit button for admin users that don't have the permission to update this form  ?>
                <?php if($can_submit): ?>
                    <div id="submit_source_of_funds_form" class="submit_source_button" onclick="<?php echo $submit_function_name; ?>()">
                        <div<?php if(phive()->isMobile()) { echo ' class="register-big-btn-txt register-button-emptydob-mobile"'; } ?>>
                            <?php et('send.declaration.form'); ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

            <div class="clear"></div>

        </form>

        <div class="sourceoffundsbox-footer-left">

        </div>

        <div class="sourceoffundsbox-footer-right">
            <?php et('confidential'); ?>
        </div>
    </div>

</div>

