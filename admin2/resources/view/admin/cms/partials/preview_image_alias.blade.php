
@include('admin.partials.flash')

<div>
    @if(!empty($dimensions['width']) && !empty($dimensions['height']))
        <p>Please upload images with this size: <strong> {{ $dimensions['width'] }}x{{$dimensions['height']}}</strong> (width x height in pixels)</p>
    @else
        <p>The system is unable to determine what the correct size for the images needs to be</p>
    @endif

    <p>Uploaded images for this alias </p>

    @if(!empty($images))
        <div id='first-banner' class='banner-image'>
            <img src="{{getMediaServiceUrl()}}/image_uploads/{{$images[0]->filename}}"
                 width="{{$images[0]->width}}px"
                 height="{{$images[0]->height}}px"
                 >
            <br>
            <button id='seeallimages' class="btn btn-default" onclick="showAllImages()">
                See all images
            </button>
        </div>

        @include('admin.cms.partials.allimages')
    @else
        <p>No images uploaded</p>
    @endif
</div>
