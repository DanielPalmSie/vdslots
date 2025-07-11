@extends('admin.layout')

@section('content-header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    Fraud Section
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#"><i class="fa fa-cog mr-2"></i>Admin Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Fraud</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @include('admin.fraud.partials.topmenu')
    <? $enable_multi_select = true ?>
    @include('admin.fraud.partials.bigplayersfilter')
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">{{ $title }}</h3>
            @if(false && p($permission))
                <a class="float-right" id="bigplayers-download-link" href="#"><i class="fa fa-download"></i> Download</a>
            @endif
        </div><!-- /.card-header -->
        <div class="card-body">
            <table class="fraud-section-datatable table table-striped table-bordered dt-responsive w-100 border-collapse">
                <thead>
                <tr>
                    <th>User ID</th>
                    <th>Verified</th>
                    <th>Country</th>
                    <th>Currency</th>
                    <th>Deposit Sum</th>
                    <th>Deposit Sum: EUR</th>
                </tr>
                </thead>
                <tbody>
                @foreach($big_players as $element)
                    <tr>
                        <td class="align-middle">
                            <a target="_blank" href="{{ \App\Helpers\URLHelper::generateUserProfileLink($app, $element->user_id) }}">{{ htmlspecialchars($element->user_id) }}</a>
                        </td>
                        <td>{{ $element->verified == 1 ? 'Yes' : 'No'}}</td>
                        <td>{{ $element->country }}</td>
                        <td>{{ $element->currency }}</td>
                        <td>{{ $element->deposits/100 }}</td>
                        <td>{{ \App\Helpers\DataFormatHelper::nf($element->deposits/$element->multiplier) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div><!-- /.card-body -->
    </div>

@endsection


@include('admin.fraud.partials.fraud-footer')


@section('footer-javascript')
    @parent
    <script>
        $(function () {
            $('#bigplayers-download-link').on( 'click', function (e) {
                e.preventDefault();
                var form = $('#bigplayers-filter-form');
                form.append('<input type="hidden" name="export" value="1" /> ');
                form.submit();
            });
        });
    </script>
@endsection
