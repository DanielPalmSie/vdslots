@extends('admin.layout')

@section('content')
    <div class="container-fluid">
        @include('admin.gamification.wheelofjackpots.partials.topmenu')

        <p><a href="{{ $app['url_generator']->generate('wheelofjackpots-create-wheel') }}"><i class="fa fa-asterisk"></i> Create a New Wheel</a></p>

        <form id="createwheel-form" method="post">
            <input type="hidden" name="token" value="{{$_SESSION['token']}}">

            <div class="card card-solid card-primary">
                <div class="card-header with-border">
                    <h3 class="card-title">Update Wheel Spinning Time</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label" for="name">
                            Wheel Spinning Time in seconds
                        </label>
                        <div class="col-sm-3">
                            <input id="wheel-spin-time" data-uniqueid="" name="wheel-spin-time" class="form-control" type="text" value="{{ $configVal }}">
                        </div>
                        <div class="col-sm-7">
                            <button class="btn btn-primary" id="save-wheel-btn" type="submit">Update</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="card card-solid card-primary">
            <div class="card-header">
                <h3 class="card-title">List of Wheels</h3>
                <div style="float: right">
                    <a href="{{ $app['url_generator']->generate('wheelofjackpots-create-wheel') }}"><i class="fa fa-asterisk"></i> Create a New Wheel</a>
                </div>
            </div>

            <div class="card-body">
                <table class="table table-striped table-bordered">
                    <tr>
                        <th style="width: 5%">#</th>
                        <th style="width: 15%">Name</th>
                        <th style="width: 15%">Number of slices</th>
                        <th style="width: 15%">Cost per spin</th>
                        <th style="width: 20%">Status</th>
                        <th style="width: 10%">Action</th>
                        <th style="width: 5%">Country</th>
                        <th style="width: 15%">Log</th>
                    </tr>
                    @foreach($wheels as $wheel)
                        <tr>
                            <td>{{ $wheel->id }}</td>
                            <td id="wheel_name_{{$wheel->id}}">
                            @if($wheel->deleted)
                                    {{ $wheel->name }}
                                @else
                                    <a href="{{ $app['url_generator']->generate('wheelofjackpots-update-wheel', ['wheel_id' => $wheel->id]) }}">
                                        {{ $wheel->name }}
                                    </a>
                                @endif
                            </td>
                            <td>{{ $wheel->number_of_slices }}</td>
                            <td>{{ $wheel->cost_per_spin }}</td>
                            <td id="wheel_deleted_{{$wheel->id}}">
                            @if($wheel->active)
                                    ACTIVE
                            @elseif($wheel->deleted)
                                    DELETED
                                @endif
                            </td>
                            <td role="group" aria-haspopup="true" class="wheel-list">
                                @if(!$wheel->active && !$wheel->deleted)
                                    {{-- TODO check why the popup for confirmation is not working, currently it deletes directly on click --}}
                                    <a class="far fa-trash-alt action-set-btn"
                                    id="delete_wheel_{{$wheel->id}}"
                                    data-url="{{$app['url_generator']->generate('wheelofjackpots-deletewheel',['wheel_id' => $wheel->id])}}"
                                    data-dtitle="Delete wheel"
                                    data-dbody="Are you sure you want to delete wheel <b>{{$wheel->name}}</b>?"
                                    data-value= {{$wheel->id}}
                                    > Delete</a> |
                                @endif
                                <a class="far fa-clipboard action-set-btn"
                                id="delete_wheel_{{$wheel->id}}"
                                href="{{$app['url_generator']->generate('wheelofjackpots-clone',['wheel_id' => $wheel->id])}}"
                                data-url="{{$app['url_generator']->generate('wheelofjackpots-clone',['wheel_id' => $wheel->id])}}"
                                data-dtitle="Delete wheel"
                                data-dbody="Are you sure you want to clone wheel <b>{{$wheel->name}}</b>?"
                                data-value= {{$wheel->id}}
                                > Clone
                                </a>
                            </td>
                            <td>{{ $wheel->country }}</td>
                            <td>
                                <a href="{{ $app['url_generator']->generate('wheelofjackpots-wheellog', ['wheel_id' => $wheel->id]) }}">
                                    View usage log
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script type="text/javascript" src="/phive/admin/customization/scripts/jackpotwheel.js"></script>
    <script src="/phive/admin/customization/plugins/bootstrap4-editable/js/bootstrap-editable.min.js"></script>
    <script type="text/javascript">
        // Show popup dialog for delete button
        // $('.action-set-btn').on('click', function(e) {
        //     e.preventDefault();
        //
        //     var dialogTitle = $(this).data("dtitle");
        //     var dialogMessage = $(this).data("dbody");
        //     var dialogUrl = $(this).attr('href');
        //     if($(this).data("disabled") != 1){
        //         showConfirmBtn(dialogTitle, dialogMessage, dialogUrl);
        //     }
        // });

    </script>
@endsection

