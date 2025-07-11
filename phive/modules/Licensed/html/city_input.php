<?php
    $user = cuPl($_REQUEST['user_id']);
    if (empty($user)) {
        return;
    }
?>
<tr>
    <td><strong><?php echo t('register.city') ?></strong></td>
    <td>
        <?php $attr = $user->getAttr('city');
        dbInput('city', phive()->html_entity_decode_wq($attr) , 'text') //using html_entity_decode_wq here to decode city field for edit account ?>
    </td>
</tr>
