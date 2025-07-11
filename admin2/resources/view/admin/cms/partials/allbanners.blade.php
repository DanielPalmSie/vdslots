{{-- todo: do we need a form element here, like in allimages.blade.php ?  --}}
<div id="allimages" style="display: none">
    @foreach($all_images['images'] as $image_data)
    
        @include('admin.cms.partials.bannerimage')

    @endforeach

    <div class="clear"></div>

    <br>
    <div class="delete-image">
        <a href="{{$app['url_generator']->generate('delete-selected-files')}}"
                    class="btn btn-default action-delete-selected-btn" data-dtitle="Delete Selected Files"
                    data-dbody="Are you sure you want to delete all selected files</b>?"
                    id="delete-selected-files"
                    >
            Delete all selected images
        </a>
    </div>

    <div class="delete-image">
        <a id="select-all-images" class="btn btn-default">
            Select all
        </a>
    </div>

    <div class="delete-image">
        <a id="unselect-all-images" class="btn btn-default">
            Unselect all
        </a>
    </div>
</div>
