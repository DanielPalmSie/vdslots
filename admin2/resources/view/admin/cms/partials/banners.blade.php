<?php // When selecting an alias, show the banners belonging to this alias  ?>
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
        @else
            <p>No banners uploaded</p>
        @endif
    </div>
</div>
