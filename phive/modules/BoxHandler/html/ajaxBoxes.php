<?php

use Videoslots\User\ThirdPartyVerificationFields\Factory\TopPartFactory;

require_once __DIR__ . '/../../../phive.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';
require_once __DIR__ . '/../../../html/common.php';


phive('Localizer')->setFromReq();
$cur_player = cuPl();
$mbox       = new MboxCommon();

// NOTE: no more actions are to be added directly to this file, use get_raw_html or get_html_popup to route
// via the moduleHtml() logic instead, otherwise it will become gigantic. /Henrik

phive('Licensed')->forceCountry($_REQUEST['country']);

$top_part_factory = new TopPartFactory();

switch($_REQUEST['box_action']){
    case 'get_raw_html':
        if($_REQUEST['mbox_type'] == 'iframe'){
            require_once __DIR__ . '/../../../../diamondbet/multibox.php';
            exit;
        }
        moduleHtml($_REQUEST['module'], $_REQUEST['file'], false, phive('Licensed')->getLicCountryProvince($cur_player));
        break;
    case 'get_html_popup':
        ?>
        <div class="lic-mbox-wrapper <?= $_POST['extra_css'] ?: '' ?>">
            <?php
            if ($_POST['show_header'] != 'no'){
                $top_part_data = $top_part_factory->create(
                    $_POST['box_id'] ?? 'mbox-msg',
                    $_POST['boxtitle'] ?? 'msg.title',
                    $_POST['closebtn'] == 'no',
                    $_POST['redirect_on_mobile'] !== 'no',
                    'window',
                    false,
                    $_POST['top_left_icon'] == true
                );
                $mbox->topPart($top_part_data);
            }
            ?>
            <div class="lic-mbox-container">
                <?php moduleHtml($_POST['module'], $_POST['file'], false, phive('Licensed')->getLicCountryProvince($cur_player)) ?>
            </div>
        </div>
        <?php
        break;
    // TODO improve the params handling from the FE + check if some logic can be moved here. /Paolo
    case 'get_html_rg_popup_dialog':
        $action = $_POST['action'];
        /**
         * default title based on action if no match we fallback to "msg.title"
         * when $_POST['boxtitle'] is passed it will always override the value
         */
        $map_action_title = [
            'force_max_bet_protection' => 'max.bet.protection.title',
            'force_login_limit' => 'login.limit.title',
        ];
        $title = $map_action_title[$action] ?: 'msg.title';
        $title = $_POST['boxtitle'] ?: $title;

        $hide_close_btn = $_POST['closebtn'] === 'no';
        $fullsize_popup = $_POST['boxtype'] === 'set_limit'; // TODO fullsize may be misleading.. "big sized" popup instead? /Paolo
        $is_mobile = phive()->isMobile();

        $module = 'DBUserHandler';
        $file = 'rg_popups/'.$_POST['action'];
        // TODO add a security check if the file doesn't exist /Paolo

        // TODO instead of passing "btntype" i should define different "boxtype" and assign the correct button directly to them with the right partials /Paolo

        ?>
        <div class="lic-mbox-wrapper dialog <?= $is_mobile ? 'mobile' : ''?> <?= $fullsize_popup ? 'limits-deposit-set' : '' /* TODO rename this CSS*/ ?>">
            <?php
                $top_part_data = $top_part_factory->create('mbox-msg', $title, $hide_close_btn);
                $mbox->topPart($top_part_data);
            ?>
            <div class="lic-mbox-container">
                <?php moduleHtml($module, $file) ?>
            </div>
            <?php if($_POST['btntype'] !== 'none'): // TODO check if the buttons logic should be moved inside ask_common (+make 2 with ask_common_dialog & ask_common_ok) ??? /Paolo?>
            <div class="lic-mbox-actions">
                <?php switch($_POST['btntype']):
                    case 'ok': ?>
                        <div id="dialog__button--ok"><?= t('ok');?></div>
                <?php
                        break;
                    case 'yes_no':
                    default: ?>
                        <div id="dialog__button--yes"><?= t('yes');?></div>
                        <div id="dialog__button--no"><?= t('no');?></div>
                <?php
                        break;
                endswitch; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        break;

    case 'get_login_rg_info':
        if($_REQUEST['rg_login_info'] == 'deposit'){
            moduleHtml('Licensed', 'dep_lim_info_box');
            exit;
        }
        if($_REQUEST['rg_login_info'] == 'login'){
            moduleHtml('Licensed', 'login_limit_info_box');
            exit;
        }
        if($_REQUEST['rg_login_info'] == 'change-deposit-before-play'){
            moduleHtml('Licensed', 'change_deposit_limit_before_gameplay_popup');
            exit;
        }
        if($_REQUEST['rg_login_info'] == 'verify'){
            moduleHtml('Licensed', 'verification_box');
            exit;
        }
        ?>
        <div class="lic-mbox-wrapper">
            <?php moduleHtml('Licensed', 'rg_info_box'); ?>
        </div>
        <?php
        break;

     case 'iframe_with_headline':

        $style = '';
        if(!empty($_POST['style'])){
            $style = phive()->toDualStr(json_decode($_POST['style'], true), ';', ':').';';
        }

        ?>
        <div class="lic-mbox-wrapper">
            <?php
                $top_part_data = $top_part_factory->create(
                    $_POST['box_id'] ?? "",
                    $_POST['headline_alias'] ?? ""
                );
                $mbox->topPart($top_part_data);
            ?>
            <iframe style="<?php echo $style ?> border: 0; overflow: hidden;" src="<?php echo $_POST['iframe_src'] ?>"></iframe>
        </div>
        <?php
        break;

    case 'restricted-popup':
        // Messages and priority to display are defined on CH19669 for further info.
        $msg = $cur_player->getDocumentRestrictionType();
        $title_alias = $_POST['msg_title'] ?? 'msg.title';
        $page = $_POST['page'];
        $hide_close_btn = true;

        if ($page == 'documents') {
            $hide_close_btn = false;
        }

        ?>
        <div class="lic-mbox-wrapper restricted-popup">
            <?php
                $box_id = $_POST['box_id'] ?? "";

                $top_part_data = $top_part_factory->create(
                    $box_id,
                    $title_alias,
                    $hide_close_btn
                );
                $mbox->topPart($top_part_data);

                $on_click = $page !== 'documents'
                    ? "goTo('".phive('UserHandler')->getUserAccountUrl('documents')."')"
                    : "mboxClose('".$box_id."')";
            ?>
            <div class="lic-mbox-container">
                <p><?= t($msg) ?></p>
                <br>
                <?php btnDefaultXl(t('restrict.msg.expired.documents.btn'), '', $on_click); ?>
            </div>
        </div>
        <?php
        break;
}
