@php
/**
 * This slider needs a jquery ui library in order to work
 **/
    $profile_rating_start = \App\Helpers\GrsHelper::getRatingFilterEntrypointByTag($app, $start);
    $profile_rating_end = \App\Helpers\GrsHelper::getRatingFilterEntrypointByTag($app, $end);
@endphp
<div style="height: 58.49px; box-sizing: border-box">
    <label for="slider-range-profile-rating-{{$type}}">{{ $label }}</label>
    <input type="text" id="slider-text-profile-rating-{{$type}}" readonly style="width:100%; text-align:center; border:0; font-weight:bold;">
    <input type="hidden"
           id="slider-start-profile-rating-{{$type}}"
           name="{{$type}}_profile_rating_start"
           value="{{ $profile_rating_start }}"
           class="form-control">
    <input type="hidden"
           id="slider-end-profile-rating-{{$type}}"
           name="{{$type}}_profile_rating_end"
           value="{{ $profile_rating_end }}"
           class="form-control">
    <div id="slider-range-profile-rating-{{$type}}"></div>
</div>

@section('footer-javascript')
    @parent
    <script>
        function convertScoreToTag(score){
            // abstract score delimiter. Has nothing to do with actual rating score range
            let rating_range = [
                {'start': 0, 'end': 25, 'tag': 'Social Gambler'},
                {'start': 26, 'end': 50, 'tag': 'Low Risk'},
                {'start': 51, 'end': 75, 'tag': 'Medium Risk'},
                {'start': 76, 'end': 100, 'tag': 'High Risk'},
            ];
            for(i = 0; i < rating_range.length; i++ ){
                if(score >= rating_range[i]['start'] && score <= rating_range[i]['end']){
                    return rating_range[i]['tag'];
                }
            }

            return 'Social Gambler';
        }

        $( function() {
            let slider_selector = "#slider-range-profile-rating-{{$type}}";
            let start_selector = "#slider-start-profile-rating-{{$type}}";
            let end_selector = "#slider-end-profile-rating-{{$type}}";
            let text_selector = "#slider-text-profile-rating-{{$type}}";
            let slider_start_value = $( start_selector ).val();
            let slider_end_value = $( end_selector ).val();
            let min = 0;
            let max = 100;
            $( slider_selector ).slider({
                range: true,
                min: min,
                max: max,
                values: [ slider_start_value, slider_end_value ],
                slide: function( event, ui ) {
                    let slider_start_tag = convertScoreToTag(ui.values[ 0 ]);
                    let slider_end_tag = convertScoreToTag(ui.values[ 1 ]);

                    $( text_selector ).val( slider_start_tag + " - " + slider_end_tag );
                    $( start_selector ).val( slider_start_tag );
                    $( end_selector ).val( slider_end_tag );
                }
            });
            let slider_start_tag = convertScoreToTag($( slider_selector ).slider( "values", 0 ));
            let slider_end_tag = convertScoreToTag($( slider_selector ).slider( "values", 1 ));

            $( text_selector ).val( slider_start_tag + " - " + slider_end_tag );
            $( start_selector ).val( slider_start_tag );
            $( end_selector ).val( slider_end_tag );
        } );
    </script>
@endsection
@section('header-css')
    @parent
    <style>

        .ui-slider {
            position: relative;
            text-align: left;
            border: 1px solid #c5c5c5;
            border-radius: 3px;
        }
        .ui-slider .ui-slider-handle {
            position: absolute;
            z-index: 2;
            width: 1.2em;
            height: 1.2em;
            cursor: default;
            -ms-touch-action: none;
            touch-action: none;
            border: 1px solid #c5c5c5;
            background: #f6f6f6;
            font-weight: normal;
            color: #454545;
            border-radius: 3px;
        }
        .ui-slider .ui-slider-range {
            position: absolute;
            z-index: 1;
            font-size: .7em;
            display: block;
            border: 0;
            background-position: 0 0;
            border: 1px solid #ddd;
            background: #e9e9e9;
            color: #333;
            font-weight: bold;
        }

        /* support: IE8 - See #6727 */
        .ui-slider.ui-state-disabled .ui-slider-handle,
        .ui-slider.ui-state-disabled .ui-slider-range {
            filter: inherit;
        }

        .ui-slider-horizontal {
            height: .8em;
        }
        .ui-slider-horizontal .ui-slider-handle {
            top: -.3em;
            margin-left: -.6em;
        }
        .ui-slider-horizontal .ui-slider-range {
            top: 0;
            height: 100%;
        }
        .ui-slider-horizontal .ui-slider-range-min {
            left: 0;
        }
        .ui-slider-horizontal .ui-slider-range-max {
            right: 0;
        }

        .ui-slider-vertical {
            width: .8em;
            height: 100px;
        }
        .ui-slider-vertical .ui-slider-handle {
            left: -.3em;
            margin-left: 0;
            margin-bottom: -.6em;
        }
        .ui-slider-vertical .ui-slider-range {
            left: 0;
            width: 100%;
        }
        .ui-slider-vertical .ui-slider-range-min {
            bottom: 0;
        }
        .ui-slider-vertical .ui-slider-range-max {
            top: 0;
        }
    </style>
@show