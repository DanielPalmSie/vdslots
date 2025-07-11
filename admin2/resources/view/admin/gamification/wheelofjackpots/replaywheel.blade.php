@extends('admin.layout')
<?php
$u = cu($user->username);
?>
@section('header-css')
    @parent
    <style>
        .trophy-description > img {
            height: 20px;
        }
    </style>
@endsection
@section('content')
    <div class="container-fluid">

        @include('admin.user.partials.header.actions')
        @include('admin.user.partials.header.main-info')

        <div class="card card-solid card-primary">
            <div class="card-header with-border">
                <h3 class="card-title">Replay The Wheel Of Jackpots - {{$spin_time}}</h3>
                <div style="float: right">
                    @php
                        $back_link = empty($referer) ?
                            $app['url_generator']->generate('wheelofjackpots-wheellog', ['wheel_id' => $_REQUEST['wheel_id']]) :
                            $referer
                        ;
                    @endphp
                        <a href="{{ $back_link }}"><i class="fa fa-arrow-left"></i> Back to the list</a>
                </div>
            </div>
            <!-- /.card-header -->
            <div class="card-body" id="wheelExtContainer">
                @include('admin.gamification.wheelofjackpots.partials.replay_wheel')
            </div>
            <!-- /.card-body -->
        </div>
    </div>
@endsection

@section('footer-javascript')
    @include('admin.partials.jquery-ui')
    <script>
        // renaming tooltip trigger because otherwise it collide with bootstraps tooltip which is used on our page
        $.widget.bridge('uitooltip', $.ui.tooltip);
    </script>
    @parent
    <script src="/phive/admin/customization/plugins/bootstrap4-editable/js/bootstrap-editable.min.js"></script>
    <script type="text/javascript" src="/phive/admin/customization/scripts/winwheel.js"></script>
    <script type="text/javascript" src="/phive/admin/customization/scripts/TweenMax.min.js"></script>
    <script type="text/javascript" src="/phive/admin/customization/scripts/jackpotwheel.js"></script>

    <script type="text/javascript">
        $(function(){

            // -------------------------------------------------------
            // Loads the wheel the first time
            // -------------------------------------------------------

            image_paths = [];

    		@foreach($filenames as $key => $filename)
                image_paths.push('{{$filename}}');
         	@endforeach

                loadWheel(image_paths, '{{ $wheel_style['colors'] }}');
         		setTimeout(redrawWheel, 500); // this is needed as sometimes segment images do not load completely so a refresh is needed
        });

    </script>
    @include('admin.gamification.wheelofjackpots.partials.resize_wheel_logic')
@endsection

