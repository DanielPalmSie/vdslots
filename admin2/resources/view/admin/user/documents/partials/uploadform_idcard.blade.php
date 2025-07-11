<p><?php et($document['tag'].'.section.confirm.info') ?></p>
<div id="stepChooseIdType">

    <form action="{{ $app['url_generator']->generate('admin.user-document-add-multiple-files',
                 ['user' => $user->id]) }}"
          method="post" 
          enctype="multipart/form-data"
          id="upload_form_{{$document['id']}}"
          >
        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
        <input type="hidden" name="document_type" value="{{$document['tag']}}">
        @if(!empty($document['id']))
            <input type="hidden" name='document_id' value="{{$document['id']}}">
        @endif

        <label for="idtype"><?php et('select.id.type'); ?>
        <?php
            $idtypes = array('PASSPORT' => t('Passportidentity.card'), 'ID_CARD' => t('identity.card'), 'DRIVING_LICENSE' => t('driving.license'));
            dbSelect('idtype', $idtypes, '', array('', t('select.id.type').':'));
        ?>
        <div id="idtype_error" class="error-reg"></div></label>

        <br/>

        <div id="image-front-container" style="display:none;">
            <label for="image-front">
                <?php et('please.upload.front'); ?>
                <input type="file" id="image-front" name="image-front" disabled="disabled">
                <div id="image-front_error" class="error-reg"></div>
            </label>
        </div>

        <div id="image-back-container" style="display:none;">
            <label for="image-back">
                <?php et('please.upload.back'); ?>
                <input type="file" id="image-back" name="image-back" disabled="disabled">
                <div id="image-back_error" class="error-reg"></div>
            </label>
        </div>

        <input type="submit"
               value="{{t("submit")}}"
               class="submit upload_button"
               id="upload_button_{{$document['id']}}"
               data-ddocumentid="{{$document['id']}}"
               disabled="disabled"
               />

    </form>

</div>

