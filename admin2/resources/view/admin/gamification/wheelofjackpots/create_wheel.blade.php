@extends('admin.layout')

@section('content')
    <div class="container-fluid">
        @include('admin.gamification.wheelofjackpots.partials.topmenu')

        @include('admin.partials.flash')
        <div class="card card-solid card-primary">
            <div class="card-header with-border">
                <h3 class="card-title">Create a new Wheel</h3>
                <div style="float: right">
                    <a href="{{ $app['url_generator']->generate('wheelofjackpots') }}"><i class="fa fa-arrow-left"></i> Back to the list</a>
                </div>
            </div><!-- /.card-header -->
            <div class="card-body">
                On this page you can create a new wheel of jackpots. <br>
                The wheel will be created after the first step, but it cannot be activated before you finish the configuration.
            </div><!-- /.card-body -->
        </div>

        <form id="createwheel-form" method="post">
            <div class="card card-solid card-primary">
                <div class="card-header with-border">
                    <h3 class="card-title">Set up Wheel data</h3>
                </div><!-- /.card-header -->
                <div class="card-body">
                    @include('admin.gamification.wheelofjackpots.partials.form_wheel')
                    <div class="form-group row">
                    <div class="col-sm-6 col-sm-offset-5">
                        <button class="btn btn-primary" id="save-wheel-btn" type="submit" >
                            Continue
                            </button>
                        </div>
                        <div class="col-sm-6"></div>
                    </div>
                </div><!-- /.card-body -->
            </div>
        </form>
    </div>
@endsection
@section('footer-javascript')
    @parent
    <script>
        $('#select-input-style, .select2-multiple').select2();
    </script>
    <script>
        $.widget.bridge('uitooltip', $.ui.tooltip);
    </script>
@endsection

