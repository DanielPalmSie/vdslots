<?php
$user = cuPl($_REQUEST['user_id']);
if (empty($user)) {
    return;
}
?>
<tr>
    <td><strong><?php echo t('register.province') ?></strong></td>
    <td>
        <?php
        $provinces = lic('getProvinces', [], $user);
        dbSelect('main_province', $provinces, $user->getSetting('main_province'), [], 'regform-field');
        ?>
    </td>
</tr>
