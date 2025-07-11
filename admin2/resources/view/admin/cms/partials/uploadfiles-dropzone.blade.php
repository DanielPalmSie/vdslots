<div id="dropzone_container" class="col-12 col-sm-6 col-lg-9" style="display: none">
    <div id="dropzone">
        <form action="{{ $dropzone_upload_url }}" class="dropzone" id="uploadfilesDropzone">
            <div class="dz-message">
                Drop image file here or click to select files to upload.<br/>
            </div>

            <input id="folder"        type="hidden"  name="folder"        value="{{$folder}}"/>
            <input id="subfolder"     type="hidden"  name="subfolder"     value=""/>
            <input id="image_id"      type="hidden"  name="image_id"      value="{{$image_id}}"/>
            <input id="image_alias"   type="hidden"  name="image_alias"   value=""/>
            <input id="image_height"  type="hidden"  name="image_height"  value="{{$dimensions['height']}}"/>
            <input id="image_width"   type="hidden"  name="image_width"   value="{{$dimensions['width']}}"/>
            <input id="page_alias"    type="hidden"  name="page_alias"    value=""/>
        </form>
    </div>
</div>
<div class="clear"></div>

