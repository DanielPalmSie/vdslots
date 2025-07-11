<?php

use Videoslots\User\ThirdPartyVerificationFields\Factory\TopPartFactory;

$mbox = new MboxCommon();
$u_obj = $mbox->getUserOrDie();
$box_id = 'rg-login-box';

?>
<?php if (!$u_obj->hasDeposited()): ?>
    <div class="lic-mbox-header relative">
        <?php if (phive()->isMobile()): ?>
            <div class="lic-mbox-close-box" onclick="goTo('/mobile/cashier/deposit/?no_redirect=1');">X</div>
        <?php else: ?>
            <div class="lic-mbox-close-box" onclick="mboxDeposit('/cashier/deposit/?no_redirect=1')">X</div>
        <?php endif; ?>
        <div class="lic-mbox-title">
            <?php et($box_headline_alias ?? 'message') ?>
        </div>
    </div>
<?php else: ?>
    <?php
        $top_part_data = (new TopPartFactory())->create($box_id, 'verification.box.show.headline');
        $mbox->topPart($top_part_data);
    ?>
<?php endif; ?>
<?php if (phive()->isMobile()): ?>
    <style>
        /* Leave this css here to overwrite the default width only for this case */
        #rg-login-box {
            width: 100% !important;
            left: 0;
        }
    </style>
    <div class="lic-mbox-container limits-info mobile">
            <div class="center-stuff">
                <?php et('verification.box.show.html') ?>
            </div>

            <div class="center-stuff rg-footer">
                <button class="btn btn-l positive-action-btn" onclick="goTo('<?php echo phive('Licensed')->getDocumentsUrl($u_obj); ?>')">
                    <?php et('verification.box.show.verify') ?>
                </button>
            </div>
    </div>
<?php else: // Desktop ?>
    <style>
        /* Leave this css here to overwrite the default width only for this case */
        #rg-login-box {
            width: 573px !important;
        }
    </style>
    <div class="lic-mbox-container limits-info">
        <div class="center-stuff">
            <?php et('verification.box.show.html') ?>
        </div>
        <div class="center-stuff rg-footer">
            <button class="btn btn-l positive-action-btn" onclick="goTo('<?php echo phive('Licensed')->getDocumentsUrl($u_obj); ?>')"><?php et('verification.box.show.verify') ?></button>
        </div>
    </div>
<?php endif ?>
