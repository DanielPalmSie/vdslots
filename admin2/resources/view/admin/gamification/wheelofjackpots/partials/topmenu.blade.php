@section('content-header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Wheel of Jackpots Section</h1>
            </div>

            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('home') }}"><i class="fa fa-cog mr-2"></i>Admin Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('gamification-dashboard') }}">Gamification</a></li>
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('wheelofjackpots') }}">The Wheel Of Jackpots</a></li>
                    <li class="breadcrumb-item active">{{ $breadcrumb }}</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@section('header-css') @parent
<link rel="stylesheet" href="/phive/admin/customization/plugins/bootstrap4-editable/css/bootstrap-editable.css">
<link rel="stylesheet" href="/phive/admin/customization/styles/css/wheel.css" type="text/css" />
@endsection


@include('admin.gamification.partials.topmenu')

