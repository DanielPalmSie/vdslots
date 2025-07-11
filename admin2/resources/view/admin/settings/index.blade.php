@extends('admin.layout')

@section('content-header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Settings Section</h1>
            </div>

            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('home') }}"><i class="fa fa-cog mr-2"></i>Admin Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('settings-dashboard') }}">Settings</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">

        @include('admin.settings.partials.topmenu')

        <div class="row">
            <div class="col-lg-4 col-12">
                <div class="card card-solid card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-cog"></i> Config</h3>
                    </div>
                    <div class="card-body">
                        <p>Config values to easily control functionality from one place.</p>
                        <a href="{{ $app['url_generator']->generate('settings.config.new') }}"><i class="fa fa-plus"></i>
                            Create a New Config</a> or
                        <a href="{{ $app['url_generator']->generate('settings.config.index') }}"><i class="fa fa-list"></i>
                            List and <i class="fa fa-search"></i> Search Configs</a>.
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-12">
                <div class="card card-solid card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-lock"></i> Permissions</h3>
                    </div>
                    <div class="card-body">
                        <p>Permission group and permission tags to limit users actions.</p>
                        <a href="{{ $app['url_generator']->generate('settings.permissions') }}"><i class="fa fa-plus"></i>
                            Add, <i class="fa fa-times"></i> Remove, <i class="fa fa-list"></i> List and <i
                                    class="fa fa-edit"></i> Edit Permission Groups</a>
                        or <a href="{{ $app['url_generator']->generate('permissions.tag-list') }}"><i
                                    class="fa fa-plus"></i>
                            Add, <i class="fa fa-times"></i> Remove, <i class="fa fa-list"></i> List and <i
                                    class="fa fa-edit"></i> Edit Permission Tags</a>.
                    </div>
                </div>
                <div class="card card-solid card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-cog"></i> AML Profile Settings</h3>
                    </div>
                    <div class="card-body">
                        <p>Edit score and configuration for AML Profile Settings</p>
                        <a href="<?php echo e($app['url_generator']->generate('settings.aml-profile.index')); ?>">
                            <i class="fa fa-list"></i>List and <i class="fa fa-edit"></i> AML Profile Settings
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-12">
                <div class="card card-solid card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-cog"></i> RG Profile Settings</h3>
                    </div>
                    <div class="card-body">
                        <p>Edit score and configuration for RG Profile Settings</p>
                        <a href="<?php echo e($app['url_generator']->generate('settings.rg-profile.index')); ?>">
                            <i class="fa fa-list"></i>List and <i class="fa fa-edit"></i> RG Profile Settings
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
