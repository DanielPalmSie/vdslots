<?php

class RegistrationFormBuilder
{
    /** @var array|null $fields */
    private ?array $fields;
    /** @var string $side */
    private string $side;

    /**
     * RegistrationFormBuilder constructor.
     *
     * @param string $side
     * @param array|null $fields
     * @param array $source
     */
    public function __construct(string $side, ?array $fields, array $source)
    {
        $fields = array_filter($fields ?? [], function($field) use ($source) {
            return !empty($source[$field]);
        });
        if (empty($fields)) {
            return;
        }
        $step2_text_class = licJur() ? "step2-text-size-" . strtolower(licJur()) : "";
        $this->fields = $fields;
        $this->side = $side;
        ?>
        <div class="registration-content-<?= $side ?>">
            <div class="regstep2-<?= $side ?>">
                <? if ($side === 'left'): ?>
                    <div class="step2-description">
                        <div class="registration-info-txt <?= $step2_text_class ?>">
                            <p><?php echo t("register.step2.infotext"); ?></p>
                            <?php lic('registrationExtraTopLeft') ?>
                        </div>
                    </div>
                <? endif; ?>

                <? foreach ($fields as $field): ?>

                    <?php if(!empty($source[$field]['label'])): ?>
                        <div class="registration-step-2-control field <?= empty($source[$field]['label'])?> " style="padding-top: 5px;">
                            <label class="registration-info-txt <?= $step2_text_class ?>"><?= t($source[$field]['label']); ?></label>
                        </div>
                    <?php endif; ?>

                    <div
                        class="registration-step-2-control field-<?= $field ?> <?= empty($source[$field]['disabled']) ? '' : 'disabled-field'?> "
                        style="display: <?= isset($source[$field]['visible']) && !$source[$field]['visible'] ? 'none' : 'block' ?>"
                    >
                        <? $this->getField($field, $source[$field]) ?>
                        <?php if($field !== 'email_code'): ?>
                            <div id="<?= $field . '_msg' ?>" class="info-message" style="display:none"><?= t($source[$field]['error_message']) ?></div>
                            <div id="<?= $field . '_count' ?>"></div>
                        <?php endif; ?>
                    </div>

                <? endforeach; ?>
            </div>
        </div>
        <?
    }

    /**
     * Return the correct autocomplete alias
     *
     * @param $field
     * @return string
     */
    public static function getAutocomplete($field)
    {
        $map = [
            'firstname' => 'given-name',
            'lastname' => 'family-name',
            'lastname_second' => 'family-name-second',
            'address' => 'street-address',
            'zipcode' => 'postal-code',
            'city' => 'address-level2',
            'birthdate' => 'bday-day',
            'birthmonth' => 'bday-month',
            'birthyear' => 'bday-year',
        ];

        return $map[$field] ?? $field;
    }

    /**
     * Returns the proper html code based on field and config
     *
     * @param string $field
     * @param array $config
     */
    public function getField($field, $config)
    {
        if ($field === 'bonus_code_text') {
            return $this->getBonusCodeText();
        }


        if ($field === 'fiscal_code') {
            $this->getFiscalCode($field, $config);
        } elseif ($field === 'doc_number') {
            $this->getDocNumber($field, $config);
        } elseif ($field === 'email_code') {
            $this->getEmailCode($field, $config);
        } elseif ($field === 'sex') {
            $this->getSex($field, $config);
        } elseif ($field === 'birthdate' || $field === 'doc_issue_date') {
            $this->getDate($field, $config);
        } elseif (isset($config['checkbox'])) {
            $this->getCheckbox($field, $config);
        } elseif (isset($config['options'])) {
            $this->getSelect($field, $config);
        } else {
            $this->getInputText($field, $config);
        }
    }

    /**
     * Html code for bonus_code_text field
     */
    private function getBonusCodeText()
    {
        ?>
        <div>
            <div id="bonus_code_text"><p><?php echo t('register.click.bonus.code'); ?></p></div>
        </div>
        <?
    }

    /**
     * Html code for bonus_code_text field
     */
    private function getFieldName($config, $defaultValue)
    {
        $name = $defaultValue;
        if (!$config['skip_frontend_validation']) {
            $name = $config['name'] ?: $defaultValue;
        }
        return $name;
    }

    /**
     * Html code for input type select
     *
     * @param string $field
     * @param array $config
     */
    private function getSelect($field, $config)
    {
        $name = $this->getFieldName($config, $field);
        ?>
        <label for="<?= $field ?>">
            <? if($config['disablelabel'] !== true){ ?>
            <span class="label label-<?= $field ?>"><?= $config['placeholder'] ?></span>
            <? } ?>
            <span class="styled-select">
                <? dbSelect($name, $config['options'], $config['value'], ['', $config['input_placeholder'] ?: $config['placeholder']], 'form-item select-validation', false, '', true, $config['disabled'], self::getAutocomplete($field)); ?>
            </span>
        </label>
        <?
    }

    /**
     * Html code for input type checkbox
     *
     * @param string $field
     * @param array $config
     */
    private function getCheckbox($field, $config)
    {
        ?>
        <br clear="all"/>
        <label for="<?= $field ?>" class="regstep2-right">
            <input id="<?= $field ?>" name="<?= !empty($config['name']) ? $config['name'] : $field ?>" type="checkbox" class="form-item" <? if($config['checked']) {?> checked<? }?>/>
            <span id="<?= $field ?>-span"><?= $config['placeholder'] ?></span>
        </label>
        <?
    }

    /**
     * Html code for input type text
     *
     * @param string $field
     * @param array $config
     */
    private function getInputText(string $field, array $config)
    {
        $name = $this->getFieldName($config, 'minlen');
        ?>
        <label for="<?= $field ?>">
            <?php if (!empty($config['description_top'])): ?>
                <p class="description-top"><?= $config['description_top'] ?></p>
            <?php endif; ?>
            <input
                id="<?= $field ?>"
                autocomplete="<?= self::getAutocomplete($field) ?>"
                value="<?= $config['value'] ?>"
                placeholder="<?= $config['placeholder'] ?>"
                <?= $config['disabled'] ? 'disabled="true"' : '' ?>
                class="input-normal form-item <?= $config['skip_frontend_validation'] ? 'skip-validation' : '' ?>"
                name="<?= $name ?>"
                type="text"
                autocorrect="off"
                <?= $this->side === 'left' && array_search($field, $this->fields) === 0 ? 'autofocus' : ''?>
            />
        </label>
        <?
    }

    /**
     * Html code for fiscal_code field
     *
     * @param string $field
     * @param array $config
     */
    private function getFiscalCode($field, $config)
    {
        ?>
        <label for="<?= $field ?>" class="input-with-icon">
            <input id="<?= $field ?>" autocomplete="<?= $field ?>" value="<?= $config['value'] ?>" placeholder="<?= $config['placeholder'] ?>" <?= $config['disabled'] ? 'disabled="true"' : '' ?> class="input-normal form-item" name="minlen" type="text" autocorrect="off" autofocus/>
            <div class="icon"></div>
        </label>
        <?
    }

    /**
     * Html code for doc_number field
     * In the future it could be used for input type text with no placeholder but with text label
     *
     * @param string $field
     * @param array $config
     */
    private function getDocNumber($field, $config)
    {
        ?>
        <label for="<?= $field ?>">
            <span class="label label-<?= $field ?>"><?= $config['placeholder'] ?></span>
            <input id="<?= $field ?>" autocomplete="<?= $field ?>"
                   placeholder="<?= $config['input_placeholder'] ?: $config['placeholder'] ?>"
                   value="<?= $config['value'] ?>" <?= $config['disabled'] ? 'disabled="true"' : '' ?>
                   class="input-normal form-item" name="minlen" type="text" autocorrect="off"/>
        </label>
        <?
    }

    /**
     * Html code for input type date
     *
     * @param string $field
     * @param array $config
     */
    private function getDate($field, $config)
    {
        ?>
        <label>
            <span class="label label-<?= $field ?>"><?= $config['placeholder'] ?></span>
            <? foreach ($config['fields'] as $f => $conf): ?>
                <span class="styled-select <?= ($f === 'doc_month') ? 'doc-month' : '' ?>">
                    <?php dbSelect($f, $conf['options'], $conf['value'], ['', $conf['placeholder']], 'form-item date-item', false, '', true, $conf['disabled']); ?>
                </span>
            <? endforeach; ?>
        </label>

        <?php
    }


    /**
     * Html code for sex field
     *
     * @param string $field
     * @param array $config
     */
    private function getSex($field, $config)
    {
        ?>
        <span class="label label-<?= $field ?>"><?= $config['placeholder'] ?></span>
        <div style="margin-top: 10px">
            <? foreach ($config['fields'] as $f => $conf): ?>
                <input type="radio" id="<?= $f ?>" name="sex" <?= $config['disabled'] ? 'disabled="true"' : '' ?> value="<?= $config['data'][$f] ?>" <?= $conf['value'] ? 'checked="true"' : '' ?> class="rb form-item"/>
                <label class="gender" for="<?= $f ?>"><?= $conf['placeholder'] ?></label>
            <? endforeach; ?>
        </div>
        <?php
    }


    /**
     * Html code for email_code field
     *
     * @param string $field
     * @param array $config
     */
    private function getEmailCode($field, $config)
    {
        ?>
        <div>
            <label for="validation_code">
                <p class="label label-<?= $field ?>"><?php echo t('enter.validation.code'); ?></p>
                <div id='customer_email'><p><?php echo t('enter.email') . ': ' . $config['data']['email']; ?></p></div>
                <div id="change_email_mobile"><p><?php echo t('register.change.email.mobile'); ?></p></div>
                <div id='customer_mobile'><p><?php echo t('account.mobile') . ' ' . $config['data']['mobile']; ?></p></div>
                <input name="minlen" id="email_code" class="input-normal form-item" type="text" inputmode="numeric" autocapitalize="off" autocorrect="off" autocomplete="one-time-code" placeholder="<?= $config['placeholder'] ?>" value="<?= $config['value'] ?>"/>
            </label>
            <div class="info-message" style="display:none"></div>

            <!-- resend code -->
            <div id="resend_code" class="resend-button">
                <div class=""><?php et('resend.code'); ?></div>
            </div>
        </div>
        <?php
    }
}
