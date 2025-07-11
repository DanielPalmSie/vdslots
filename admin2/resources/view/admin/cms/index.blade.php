@extends('admin.layout')

@section('content-header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    CMS Section
                </h1>
            </div>

            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('home') }}"><i class="fa fa-cog mr-2"></i>Admin Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('gamification-dashboard') }}">CMS</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">
        @include('admin.cms.partials.topmenu')

        <div class="row">
            <div class="col-lg-4 col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-tags"></i> Banners</h3>
                    </div>
                    <div class="card-body table-responsive">
                        <p>Banners to show people how much bonus they will get</p>
                        <a href="{{ $app['url_generator']->generate('banneruploads') }}"><i class="fa fa-plus"></i> Upload new banners</a> or
                        <a href="{{ $app['url_generator']->generate('bannertags') }}"><i class="fa fa-tag"></i> Connect banners to bonus codes</a>.
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-image"></i> Images and files</h3>
                    </div>
                    <div class="card-body table-responsive">
                        <p>Upload images and files</p>
                        <a href="{{ $app['url_generator']->generate('uploadimages') }}"><i class="fa fa-plus"></i>
                            Upload, <i class="fa fa-times"></i> Remove Images</a>
                            or <a href="{{ $app['url_generator']->generate('uploadfiles') }}"><i class="fa fa-plus"></i>
                            Upload, <i class="fa fa-times"></i> Remove Files</a>.
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
