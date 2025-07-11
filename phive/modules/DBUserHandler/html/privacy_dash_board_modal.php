<?php
$box_id = $_POST['box_id'] ?? 'privacy-dash-board-modal';
$mobile = phive()->isMobile() ? 'mobile' : '';
$user = cu() ?? null;
$uid = $user ? $user->getId() : false;
$lang_tag = phive('Localizer')->getCurNonSubLang();
?>
<div class=" <?= $_POST['extra_css'] ?: '' ?>">
        <?php
        $privacy_box = phive('BoxHandler')->getRawBox('PrivacyDashboardBox');
        $privacy_box->printHTML($user);
        ?>
</div>
