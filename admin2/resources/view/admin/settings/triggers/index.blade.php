@extends('admin.layout')

@section('content-header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Trigger Section</h1>
            </div>

            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('home') }}"><i class="fa fa-cog mr-2"></i>Admin Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('settings-dashboard') }}">Settings</a></li>
                    <li class="breadcrumb-item active">Triggers</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">
        <!--@include('admin.fraud.partials.topmenu')-->

        <div class="card card-solid card-primary">
            <div class="card-header">
                <h3 class="card-title">Triggers</h3>
            </div><!-- /.card-header -->
            <div class="card-body">
                <form method='post' id="trigger-form">
                    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                    <table class="fraud-section-datatable table table-striped table-bordered dt-responsive"
                        cellspacing="0" width="100%">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Indicator Name</th>
                            <th>Description</th>
                            <th>Color</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($data as $element)
                            <tr>
                                <td>{{ $element->name }}</td>
                                <td>{{ $element->indicator_name }}</td>
                                <td>{{ $element->description }}</td>
                                <td class="form-control my-colorpicker1 colorpicker-element" style="width:90%">
                                    <input type="color" name="color-{{$element->name}}" id="color-{{$element->name}}" value="{{ $element->color ? $element->color :  '#ffffff'}}" style="width:85%;">
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </form>
                <div class="row">
                    <div class="col-6 col-md-1 col-lg-1">
                        <div class="input-group">
                            <span class="input-group-btn">
                                <button type="button" id="triggers-form-submit" class="btn btn-danger btn-flat">Update</button>
                            </span>
                        </div>
                    </div>
                    <div class="col-6 col-md-1 col-lg-1">
                        <span id="msg" class=""></span>
                    </div>
                </div>
            </div><!-- /.card-body -->
        </div>
    </div>

    <script>
        $(document).ready(function() {

             $('#triggers-form-submit').on('click', function () {
                $.ajax({
                    url: "{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}",
                    type: "POST",
                    data: {'colors' : $('#trigger-form').serialize()},
                    success: function (data, textStatus, jqXHR) {
                        $('#msg').removeClass();
                        $('#msg').html(data['message']);
                        $('#msg').addClass('alert alert-' + data['status']);
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert('AJAX ERROR');
                    }
                });
            });
        });

    </script>
@endsection

@include('admin.fraud.partials.fraud-footer')
