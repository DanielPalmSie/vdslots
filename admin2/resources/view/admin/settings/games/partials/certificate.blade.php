<?php
$errors = $session_errors[$country];
$data = $session_data[$country];
?>

<div class="card card-solid card-primary game-certificate {{$country}}">
    <div class="card-header">
        <h3 class="card-title">Game certificate for {{$country}}</h3>
    </div>
    <div class="card-body">
        <div class="col-md-6">
            <div class="form-group">
                <label for="{{$country}}-i-certificate">Certificate</label>
                <select name="{{$country}}[i-certificate]" id="{{$country}}-i-certificate">
                    <option value="">Select certificate</option>
                    <option value="new">Upload new certificate</option>
                    @foreach($certificates as $cert)
                        <option value="{{$cert->game_certificate_ref}}">{{ $cert->game_certificate_ref }}</option>
                    @endforeach
                </select>
                <div class="new_file"></div>
                <i class="error-message help-block" id="certificate_error">{{$errors['certificate'][0]}}</i>
            </div>
            <div class="form-group new-file-attribute">
                <label for="{{$country}}-i-certificate_ref">Ref</label>
                <input type="text" name="{{$country}}[i-certificate_ref]" class="form-control" value="{{$data['i-certificate_ref']}}" id="{{$country}}-i-certificate_ref">
                <i class="error-message help-block" id="certificate_ref_error">{{$errors['certificate_ref'][0]}}</i>
            </div>
            <div class="form-group new-file-attribute">
                <label for="{{$country}}-i-certificate_tag">Tag</label>
                <input type="text" name="{{$country}}[i-certificate_tag]" class="form-control" value="{{$data['i-certificate_tag']}}" id="{{$country}}-i-certificate_tag">
                <i class="error-message help-block" id="certificate_tag_error">{{$errors['certificate_tag'][0]}}</i>
            </div>
            <div class="form-group new-file-attribute">
                <label for="{{$country}}-i-certificate_subtag">Subtag</label>
                <input type="text" name="{{$country}}[i-certificate_subtag]" class="form-control" value="{{$data['i-certificate_subtag']}}" id="{{$country}}-i-certificate_subtag">
                <i class="error-message help-block" id="certificate_subtag_error">{{$errors['certificate_subtag'][0]}}</i>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="{{$country}}-i-certificate_version">Version</label>
                <input type="text" name="{{$country}}[i-certificate_version]" class="form-control" value="{{$data['i-certificate_version']}}" id="{{$country}}-i-certificate_version">
                <i class="error-message help-block" id="certificate_version_error">{{$errors['certificate_version'][0]}}</i>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $("#{{$country}}-i-certificate").select2().change(function() {
            if ($(this).val() === 'new') {
                $(".{{$country}} .new_file").append('<input type="file" name="{{$country}}" class="form-control" >');
                $(".{{$country}} .new-file-attribute").show();
            } else {
                $(".{{$country}} .new_file").html('');
                $(".{{$country}} .new-file-attribute").hide();
            }
        }).val('{{$data['i-certificate']}}');
        $("#{{$country}}-i-certificate").change();
    })
</script>
