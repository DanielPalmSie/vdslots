<div class="card" id="banners">
    <div class="card-header">
        <h3 class="card-title">Uploaded banners for this alias: </h3>
    </div>
    <div class="card-body">

        @if(!empty($all_images['images'][0]))
            <div id='first-banner' class='banner-image'>
                <img src="{{getMediaServiceUrl()}}/image_uploads/{{$all_images['images'][0]['filename']}}"
                    width="{{$all_images['images'][0]['width']}}px"
                    height="{{$all_images['images'][0]['height']}}px"
                >
                <br>
                <button id='seeallimages' class="btn btn-default">
                    See all images
                </button>
            </div>

            @include('admin.cms.partials.allbanners')
            {{-- @include('admin.cms.partials.allimages') --}}

        @else
            <p>No banners uploaded</p>
        @endif
    </div>

    @if(!empty($all_images['leftover_images']))
    <div class="card-header">
        <h3 class='card-title'>Leftover banners for this alias (maybe they need to be deleted):</h3>
    </div>
    <div class="card-body">
        <div id='first-leftover-banner' class='banner-image'>
            <img src="{{getMediaServiceUrl()}}/image_uploads/{{$all_images['leftover_images'][0]['filename']}}"
                width="{{$all_images['leftover_images'][0]['width']}}px"
                height="{{$all_images['leftover_images'][0]['height']}}px"
            >
            <br>
            <button id='seeallleftoverimages' class="btn btn-default">
                See all leftover images
            </button>
        </div>

        @include('admin.cms.partials.leftover_banners')
    </div>
    @endif

</div>

