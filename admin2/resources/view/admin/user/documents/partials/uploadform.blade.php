<p>{{ t($document['tag'].'.section.confirm.info') }}</p>
Upload image
<form action="{{$app['url_generator']->generate('admin.user-document-add-multiple-files', ['user' => $user->id])}}"
      method="post" enctype="multipart/form-data" id="upload_form_{{$document['id']}}">
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
    <div>
        <div class="uploadfields">
            <input type="hidden" name="document_type" value="{{$document['tag']}}">
            <input type="file" 
                   id="upload_field_{{$document['id']}}"
                   data-ddocumentid="{{$document['id']}}"
                   class="upload_field"
                   name="file">
            @if(!empty($document['id']))
                <input type="hidden" name='document_id' value="{{$document['id']}}">
            @endif

        </div>
    </div>
</form>
    
