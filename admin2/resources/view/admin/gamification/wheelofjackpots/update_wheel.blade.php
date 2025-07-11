@extends('admin.layout')

@section('header-css') @parent
    <link rel="stylesheet" href="/phive/admin/customization/plugins/bootstrap4-editable/css/bootstrap-editable.css">
    <link rel="stylesheet" href="/phive/admin/customization/styles/css/wheel.css" type="text/css" />
    <link rel="stylesheet" href="/phive/admin/customization/styles/css/xeditableselect2.css" type="text/css" />
@endsection

@section('content')
    <div class="container-fluid">
        @include('admin.gamification.wheelofjackpots.partials.topmenu')

        @include('admin.partials.flash')

        <div class="card card-solid card-primary">
        <div class="card-header with-border">
            <h3 class="card-title">Configure a Wheel</h3>
            <div style="float: right">
                <a href="{{ $app['url_generator']->generate('wheelofjackpots') }}"><i class="fa fa-arrow-left"></i> Back to the list</a>
                </div>
        </div>
        <!-- /.card-header -->
        <div class="card-body">
            On this page you can configure a wheel of jackpots. <br>
        </div>
        <!-- /.card-body -->
        </div>


        <form id="createwheel-form" method="post">
        <div class="card card-solid card-primary">
            <div class="card-header with-border">
            <h3 class="card-title">Update Wheel data</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">

            @include('admin.gamification.wheelofjackpots.partials.form_wheel')

            <div class="form-group row">
                <div class="col-sm-5 text-right">
                    @if(p('woj.update'))
                        <button class="btn btn-primary" id="save-wheel-btn" type="submit">Update</button>
                    @endif
                </div>
            </div>

            </div>
            <!-- /.card-body -->
        </div>
        </form>


        <div class="card card-solid card-primary">
        <div class="card-header with-border">
            <h3 class="card-title">Set up Slices</h3>
        </div>
        <!-- /.card-header -->

        <div class="card-body">
            <table class=" table table-striped table-bordered" id="sliceTable">
            <tr>
                <th style="width: 100px">Order</th>
                <th style="width: 300px">Award</th>
                <th style="width: 100px">Probability</th>
                <th style="width: 100px">Action</th>
            </tr>

            <tbody class="pUpdateTable ui-sortable con" id="sliceRows">
                @foreach($slices as $key => $slice)
                    @include('admin.gamification.wheelofjackpots.partials.update_wheel_slice', ['slice' => $slice, 'awards' => $slice->awards()])
                @endforeach
            </tbody>
            </table>


            <table class=" table table-striped table-bordered">
            <tr>
                <td colspan="2" style="width:400px">Total Probability:</td>
                <td colspan="2" style="width:200px"><span id="total_probability">0</span></td>
            </tr>
            </table>

            <div class="row">&nbsp;</div>

            <div class="form-group row">
            <div class="col-sm-12 text-right">
                @if(p('woj.add.slice'))
                    <button class="btn btn-primary" onclick="addSlice( '{{$app['url_generator']->generate('wheelofjackpots-add-slice', ['wheel_id' => $wheel->id]) }}' , '{{ $app['url_generator']->generate('wheelofjackpots-getawardsforselect', ['wheel_id' => $wheel->id]) }}')">Add new slice</button>
                @endif
            </div>
            </div>
        </div>
        <!-- /.card-body -->
        </div>

        <div class="card card-solid card-primary">
        <div class="card-header with-border">
            <h3 class="card-title">Preview</h3>
        </div>
        <!-- /.card-header -->
        <div class="card-body" id="wheelExtContainer">
            @include('admin.gamification.wheelofjackpots.partials.preview_wheel')
        </div>
        <!-- /.card-body -->
        </div>

        <form id="activate-form" method="post">

        <div class="card card-solid card-primary" id="wheelactivebox" @if($wheel-> active == 1) style="display: none" @endif>
            <div class="card-header with-border">
            <h3 class="card-title">Activate the wheel</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
            Activate the wheel so it can be used on the site. <br> The total
            probability needs to be exactly 10,000,000 (100%) and for each slice an
            award needs to be selected.
            <div class="form-group row">
                <div class="col-sm-6 text-right">
                <button class="btn btn-primary" onclick="activateWheel( '{{$app['url_generator']->generate('wheelofjackpots-activatewheel', ['wheel_id' => $wheel->id])}}')" id="activate-wheel-btn" type="button"><!-- removed disabled="disabled" -->
                    Activate
                </button>
                </div>
            </div>
            </div>
        </div>

        <div class="card card-solid card-primary" id="wheeldeactivebox" @if($wheel->active == 0) style="display: none" @endif>
            <div class="card-header with-border">
            <h3 class="card-title">Deactivate the wheel</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
            Deactivate the wheel so it can be edited.
            <div class="form-group row">
                <div class="col-sm-6 col-sm-offset-3">
                <button class="btn btn-primary" onclick="activateWheel( '{{$app['url_generator']->generate('wheelofjackpots-activatewheel', ['wheel_id' => $wheel->id])}}')" id="deactivate-wheel-btn" type="button">
                    <!-- chenge to type="button" when showing flash message with ajax works -->
                    Deactivate
                </button>
                </div>
            </div>
            </div>
        </div>

        </form>
    </div>
@endsection

@section('footer-javascript')
<script src="/phive/admin/plugins/select2/js/select2.min.js"></script>
<script>
    /*
     * collision with xeditableselect2.js so if this init would be in $(function(){}) block the select from the
     * aforementioned library would be used
     */
    $('#select-input-style, .select2-multiple').select2();
</script>
@include('admin.partials.jquery-ui')
<script>
    // renaming tooltip trigger because otherwise it collide with bootstraps tooltip which is used on our page
    $.widget.bridge('uitooltip', $.ui.tooltip);
</script>

@parent
<script src="/phive/admin/customization/plugins/bootstrap4-editable/js/bootstrap-editable.min.js"></script>
<script type="text/javascript" src="/phive/admin/customization/scripts/winwheel.js"></script>
<script type="text/javascript" src="/phive/admin/customization/scripts/TweenMax.min.js"></script>
<script type="text/javascript" src="/phive/admin/customization/scripts/handlebars.min-v4.7.7.js"></script>
<script type="text/javascript" src="/phive/admin/customization/scripts/jackpotwheel.js"></script>
<script type="text/javascript" src="/phive/admin/customization/scripts/xeditableselect2.js"></script>

<!-- This script is used as a handlebar.js template to add a new row in the slices table  -->
<script id="sliceRowTemplate" type="text/x-handlebars-template">
    <tr>
	<td class="orderNum">
	    <span class="fa fa-sort"></span>&nbsp;&nbsp;
	    @{{ slice_sort_order}}
	</td>
        <td>
	    <a href="#" data-pk="@{{slice_id }}" data-name='award_id' data-value='{{ $slice->award->id }}' data-title="Select award" class="editable editable-click editable-empty pUpdateAwards" data-original-title="" title="">Select award</a>
	</td>

	<td>
        <a class="pUpdate editable editable-click probability" id="probability_slice_@{{ key}}" data-pk="@{{slice_id}}" data-name='probability' data-title='Set probability'>@{{slice_probability}}</a>
    </td>
	<td role="group" aria-haspopup="true">
            <button
                class="btn btn-sm btn-link action-set-btn p-0"
    		    id="delete_slice_@{{key}}"
    		    onclick="deleteSlice('@{{slice_id}}', 'delete_slice_@{{key}}', '{{ $app['url_generator']->generate('wheelofjackpots-delete-slice') }}?slice_id=@{{slice_id}}')"
    		    data-value="@{{slice_id}}">
                <i class="fas fa-trash"></i>
    	    </button>
	</td>
    </tr>
</script>



<script type="text/javascript">

 // Set here the URL parameters of the AJAX calls
 var urlProbability = "{{ $app['url_generator']->generate('wheelofjackpots-gettotalprobability', ['wheel_id' => $wheel->id]) }}";
 var urlUpdateSliceOrder = "{{ $app['url_generator']->generate('wheelofjackpots-orderslices', ['wheel_id' => $wheel->id]) }}";
 var posturl = "{{ $app['url_generator']->generate('wheelofjackpots-updateslice-xeditable', ['wheel_id' => $wheel->id]) }}";
 var selectposturl = "{{ $app['url_generator']->generate('wheelofjackpots-getawardsforselectwithfilter', ['wheel_id' => $wheel->id] )}}";

 $(function(){
     // -------------------------------------------------------
     // Loads the wheel the first time
     // -------------------------------------------------------

     imagePaths = [];

     @foreach($filenames as $key => $filename)
     imagePaths.push('{{$filename}}');
     @endforeach

     updateProbabilityAjax(urlProbability);
     // enableActivateButton(); // Not needed anymore, if we want to use this in the future we need to fix it passing the proper URL to check for the button status...
     //enableXeditable({{$wheel->active}});      // make wheel always editable
	 loadWheel(imagePaths, '{{$wheel_style['colors']}}');
     setTimeout(redrawWheel, 500); // this is needed as sometimes segment images do not load completely so a refresh is needed
	 $('#select-style').on('change', function() {
		var selected = $(this);
		var style_name  = selected.val();
		var style_colors = selected.find(':selected').data('colors');
		var imagePaths = theWheel.segments
			.map(segment => (segment || {}).image)
			.filter(el => el != null);
		$('#wheelContainer').css({"backgroundImage": `url('/file_uploads/wheel/bg_ui_${style_name}.jpg`});
		$('#prizePointer').attr("src",`/file_uploads/wheel/win_pointer_${style_name}.png`);
		$('#spinButton2').attr("src",`/file_uploads/wheel/Spin-Button_pressed_${style_name}.png`);
		$('#spinButton1').attr("src",`/file_uploads/wheel/Spin-Button_${style_name}.png`);
		$('#canvasContainer').css({"background": `url(/file_uploads/wheel/wheelfortune_outsiderim_${style_name}.png) center no-repeat`, "backgroundSize": "cover"});
		loadWheel(imagePaths,style_colors);
	 });
});

</script>
@include('admin.gamification.wheelofjackpots.partials.resize_wheel_logic')
@endsection
