<div class='banner-image'>
    <img src="{{getMediaServiceUrl()}}/image_uploads/{{$image_data['filename']}}" 
         width="{{$image_data['width']}}px"
         height="{{$image_data['height']}}px"
         id="image_url_{{$image_data['id']}}"
         >
    <div class='banner-info-container'>
        <div class="banner-image-info">
            Language: {{$image_data['lang']}} <br>
            Currency: {{$image_data['currency']}} <br>
            Dimensions: {{ $image_data['width']}}x{{$image_data['height'] }}
        </div>
        {{-- Only show delete buttons on upload page --}}
        @if(strpos($_SERVER['REQUEST_URI'], 'bannertags') === false)
            @if(strpos($image_data['filename'], 'missing_banner') === false)
                <div class="delete-image">
                    <a href="{{ $app['url_generator']->generate('banner-deletefile',
                                ['file_id' => $image_data['id']]) }}"
                                class="btn btn-default action-set-btn" data-dtitle="Delete File"
                                data-dbody="Are you sure you want to delete this file</b>?"
                                id='deletefile-{{$image_data['id']}}'
                                data-dfileid="{{$image_data['id']}}"
                                >
                        Delete
                    </a>
                </div>
                <input type="checkbox" class="markfilefordelete" id='markfilefordelete-{{$image_data['id']}}' name="{{$image_data['id']}}" value="{{$image_data['id']}}">
                Mark for delete
            @endif
    </div>
    @endif
</div>

