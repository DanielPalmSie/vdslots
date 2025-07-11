<form action="{{$app['url_generator']->generate('delete-selected-files')}}" method="post">
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
    <div id="allimages" style="display: none">
        @foreach($images as $image)

            <div class="banner-image">
                <img src="{{getMediaServiceUrl()}}/image_uploads/{{$image->filename}}"
                     width="{{$image->width}}px"
                     height="{{$image->height}}px"
                     id="image_url_{{$image->id}}"
                     >
                <div class="banner-image-info">
                    Language: {{$image->lang}} <br>
                    Currency: {{$image->currency}} <br>
                    Dimensions: {{ $image->width}}x{{$image->height}}
                </div>
                {{-- Only show delete buttons on upload page --}}
                @if(strpos($_SERVER['REQUEST_URI'], 'bannertags') === false)
                    <div class="delete-image">
                        <a href="{{ $app['url_generator']->generate('banner-deletefile',
                                    ['file_id' => $image->id]) }}"
                                    class="btn btn-default action-set-btn" data-dtitle="Delete File"
                                    data-dbody="Are you sure you want to delete this file</b>?"
                                    id='deletefile-{{$image->id}}'
                                    data-dfileid="{{$image->id}}"
                                    >
                            Delete
                        </a>
                    </div>
                    <input type="checkbox" class="markfilefordelete" id='markfilefordelete-{{$image->id}}' name="{{$image->id}}" value="{{$image->id}}">
                    Mark for delete
                @endif
            </div>

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
</form>

