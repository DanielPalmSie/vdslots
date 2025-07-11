<?php
$default_string = 'default';
if($only_text) {
    $default_string = 'default.html';
}
?>

<div id="input_new_alias_container" style="display: none">
    <div id="input_new_alias" class="form-group col-12 col-md-12 col-lg-8 col-xlg-6 col-fhd-4">
        <form action="" method="post">
            <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
            <p>Please specify the last part of the new alias (the part that will replace 'default',
                for example type 150freespins to create the alias banner.registration.freespins.all.150freespins)
            </p>
            Create new alias: {{str_replace($default_string, '', $alias)}}
            <input name="new_image_alias" id="last_part" class="form-control alias-input" placeholder="Enter new alias" value="" type="text">
            @if($only_text)
                .html
            @endif
            <input type="hidden" name="new_alias" value="new_alias">
            <input id="base_alias" type="hidden" name="base_alias" value="{{str_replace($default_string, '', $alias)}}">
            <input type="submit" value="submit" id="submit_last_part">
        </form>
        <span class="help-block"></span>
    </div>
</div>

