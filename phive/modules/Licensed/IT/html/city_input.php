<?php
    loadJs("/phive/modules/DBUserHandler/js/registration.js");
    $residence_province_list = lic('getProvinces');
    asort($residence_province_list);
    $user = cuPl($_REQUEST['user_id']);
    if (empty($user)) {
        return;
    }
    $province_acronym = $user->getSetting('main_province');

    if (!empty($province_acronym)) {
        $cities = lic("getMunicipalityByProvinceList", [$province_acronym]);
    }
    $city_name = $user->getAttr('city');
?>
<script>
    $(document).ready(function () {
        $("#main_province").change(function () {
            regPrePops.country = 'IT';
            Registration.getMunicipalityList(this);
            $("#city").val("");
        });
        $("#main_city").change(function () {
            $("#city").val($("#main_city").val());
        });
    })
</script>
<tr>
    <td><strong><?php echo t('register.province') ?></strong></td>
    <td>
        <select id="main_province" name="main_province" class="regform-field">
            <?php foreach ($residence_province_list as $acronym => $name):?>
                <option
                    value="<?=$acronym?>"
                    <?=($acronym == $province_acronym) ? 'selected="selected"' : ''?>
                >
                    <?=$name?>
                </option>
            <?php endforeach;?>
        </select>
    </td>
</tr>
<tr>
    <td><strong><?php echo t('register.city') ?></strong></td>
    <td>
        <select id="main_city" class="regform-field">
            <option value=""><?php echo t('registration.main_city.nostar') ?></option>
            <?php foreach ($cities as $name):?>
                <option
                    value="<?=$name?>"
                    <?=($name == $city_name) ? 'selected="selected"' : ''?>
                >
                    <?=$name?>
                </option>
            <?php endforeach;?>
        </select>
        <input type="hidden" id="city" name="city" value="<?=$city_name?>">
    </td>
</tr>
