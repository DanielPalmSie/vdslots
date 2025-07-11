<?php
require_once __DIR__ . '/../../../../../diamondbet/boxes/DiamondBox.php';

class PrivacyDashboardBoxBase extends DiamondBox
{
    /** @var DBUser $cur_user */
    public $cur_user;

    /**
     * @param DBUser|int|null $cur_user
     */
    public function printHTML($cur_user = null)
    {
        $this->cur_user = phive('DBUserHandler')->getCuOrReg($cur_user);

        /** @var PrivacyHandler $ph */
        $ph = phive('DBUserHandler/PrivacyHandler');

        loadJs("/phive/js/privacy_dashboard.js");

        $boxTitle = $this->boxTitleSection();
        $formBody = '';
        foreach ($ph->getPrivacySections($this->cur_user) as $name => $section) {
            $formBody .= $this->buildSection($section, strtolower($name) === 'main', $name);
        }

        $postFunc = sprintf(
            "postPrivacySettings(%d, %s, '%s')",
            intval(phive()->isMobile()),
            (!empty($_REQUEST['skip_all_empty_check']) || phive()->isMobile()) ? 'true' : 'false',
            $this->cur_user->hasDeposited() ? 'popup' : 'registration'
        );

        $btnText    = t('privacy.dashboard.save.button');
        $footer     = t('privacy.dashboard.footer.html');

        $popupMessage = htmlspecialchars(t('privacy.settings.error.message.html'));

        echo <<<HTML
            <div class="privacy-box-content general-account-holder">
                <div class="simple-box pad-stuff-ten">
                    {$boxTitle}
                    <form name="privacy-settings" id="privacy-settings-form" method="POST">{$formBody}</form>
                    <br>
                    <div class="privacy-btn">
                        <button class="btn btn-l btn-default-l " onclick="{$postFunc}">
                            <span>{$btnText}</span>
                        </button>
                    </div>
                    <br>
                    <div class="account-privacy-info">{$footer}</div>
                </div>
            </div>
            <script>
                const error_message_content_popup = `{$popupMessage}`;
                $(document).ready(function(){ setupPrivacy(); });
            </script>
        HTML;
    }

    /**
     * @return string
     */
    private function boxTitleSection(): string
    {
        $title      = t('privacy.dashboard.title');
        $subTitle   = t('privacy.dashboard.subtitle');
        $checkbox   = $this->checkbox('do-all');
        $checkLabel = t('privacy.dashboard.select.all.top.option');

        return <<<HTML
            <div class="privacy-headline">
                    <div class="account-headline account-privacy-info">{$title}</div>
                    <div class="account-privacy-info">{$subTitle}</div>
                </div>
                <div class="account-sub-box top-privacy-sub-box">
                    <div class="checkbox-main-privacy">
                        {$checkbox}
                        <label for="do-all">{$checkLabel}</label>
                    </div>
                </div>
        HTML;
    }

    /**
     * @param array $sections
     * @param bool $showHeader
     * @param string $identifier
     * @return string
     */
    private function buildSection(array $sections, bool $showHeader = false, string $identifier = ''): string
    {
        $body = '';

        foreach ($sections as $i => $section) {
            if ($i > 0) $body .= '<tr><td colspan="4"><div class="opt-section-divider"></div></td></tr>';
            $body .= $this->buildSubSection($section, $identifier . '-' . $i);
        }

        $header = <<<HTML
            <thead>
                <tr class="opt-channel-headers">
                    <td></td>
                    <th class="privacy-op-col">%s</th>
                    <th class="privacy-op-col">%s</th>
                    <th class="privacy-op-col">%s</th>
                </tr>
            </thead>
        HTML;

        $header = (!$showHeader) ? '' : sprintf($header, t('email'), t('sms'), t('notification'));

        return <<<HTML
        <div class="account-sub-box">
            <table class="account-privacy-table">
                $header
                <tbody>$body</tbody>
            </table>
        </div>
        HTML;
    }

    /**
     * @param array $section
     * @param string $identifier
     * @return string
     */
    private function buildSubSection(array $section, string $identifier): string
    {
        return $this->buildSectionHeader($section['config'], $identifier) .
            $this->buildSectionRows($section['rows'], $identifier);
    }

    /**
     * @param array $rows
     * @param string $identifier
     * @return string
     */
    private function buildSectionRows(array $rows, string $identifier = ''): string
    {
        $_rows = '';

        foreach ($rows as $row) {
            $_rows .= $this->buildSectionRow($row, $identifier);
        }

        return $_rows;
    }

    /**
     * Build a row of options inside the privacy dashboard / popup
     * If $row['label_alias'] is specified it is added as the category name (product) on the left
     * For each $row['options'] create a checkbox mapped to that specific setting, example; email.new.casino
     *
     * @see PrivacyDashboardBoxBase::buildCheckbox()
     *
     * @param array $row
     * @param string $identifier
     * @return string
     */
    private function buildSectionRow(array $row, string $identifier = ''): string
    {
        $identifier = (!empty($identifier)) ? 'data-group="' . $identifier . '"' : '';

        if (!empty($row['label_alias'])) {
            $html = "<tr class=\"privacy-options-group privacy-mandatory-group opt-category-row\" {$identifier}>";
            $html .= '<td class="opt-category-name">' . t($row['label_alias']) . '</td>';
        } else {
            $html = "<tr class=\"privacy-options-group privacy-mandatory-group opt-category-row opt-three-columns\" {$identifier}>";
        }

        foreach ($row['options'] as $option) $html .= $this->buildCheckbox($option);

        return $html . '</tr>';
    }

    /**
     * Creates a checkbox based on the configs specified in $option
     * Label: Translation of $option['label_alias']
     * Name: $option['setting']
     * Checked: $option['checked']
     * Tooltip Text: Translation of $option['tooltip_alias']
     *
     * @see PrivacyDashboardBoxBase::checkbox()
     *
     * @param array $option
     * @return string
     */
    private function buildCheckbox(array $option): string
    {
        $label = '';
        if (!empty($option['label_alias'])) {
            $labelTitle = t($option['label_alias']);

            if (!empty($option['tooltip_alias'])) {
                $label .= "<label for=\"{$option['setting']}\" class='with-tooltip'>" . $labelTitle;
                $tooltipBody = htmlentities(t($option['tooltip_alias']));
                $img = sprintf(
                    '<img class="privacy-moreinfo" src="%s"/>',
                    '/diamondbet/images/' . trim(brandedCss(), '/') . '/moreinfo-rtp_active.png'
                );
                $label .= "<a onclick=\"showMoreInfoBox('{$labelTitle}', '{$tooltipBody}')\">{$img}</a>";
            } else {
                $label .= "<label for=\"{$option['setting']}\">" . $labelTitle;
            }

            $label .= '</label>';
        }

        $checkbox = $this->checkbox($option['setting'], $option['checked']);

        return "<td class='privacy-op-col opt-in-check'>$checkbox $label</td>";
    }

    /**
     * @param array $config
     * @param string $identifier
     * @return string
     */
    private function buildSectionHeader(array $config, string $identifier): string
    {
        $headline = (!empty($config['headline_alias']))
            ? '<div class="account-headline">' . t($config['headline_alias']) . '</div>'
            : '';

        $subHeadline = (!empty($config['sub_headline_alias']))
            ? '<div class="account-sub-headline opt-section-description">' . t($config['sub_headline_alias']) . '</div>'
            : '';

        $subSub = (!empty($config['sub_sub_headline_alias']))
            ? '<div class="account-sub-sub-headline">' . t($config['sub_sub_headline_alias']) . '</div>'
            : '';

        $optBtn = $this->buildOptOutBtn($config);

        return <<<HTML
            <tr class="privacy-options-group privacy-mandatory-group privacy-notification-section" data-group="{$identifier}">
                <td colspan="4">
                    <div class="opt-section-header">
                        {$headline}
                        {$optBtn}
                    </div>
                    {$subHeadline}
                    {$subSub}
                </td>
            </tr>
        HTML;
    }

    /**
     * @param array $config
     * @return string
     */
    private function buildOptOutBtn(array $config): string
    {
        if (empty($config['opt_out_btn'])) return '';

        $checkbox = $this->checkbox('', $config['opt_out_all']);

        $html = <<<HTML
            <div class="opt-out-check opt-out-container">
                <span class="opt-out-text">%s</span>
                <label class="opt-toggle-switch">
                    $checkbox
                    <span class="opt-slider"></span>
                </label>
            </div>
        HTML;

        return sprintf($html, t('optout'));
    }

    /**
     * Generates HTML for a checkbox input element
     * @param string $name The name and ID attribute for the checkbox
     * @param bool|null $check Optional boolean to determine if the checkbox should be checked
     *                        If null, checkbox will be unchecked
     * @return string HTML string for the checkbox input element
     */
    private function checkbox(string $name, ?bool $check = null): string
    {
        $checked = ($check) ? 'checked="checked"' : '';
        return "<input type='checkbox' name='{$name}' id='{$name}' $checked />";
    }
}
