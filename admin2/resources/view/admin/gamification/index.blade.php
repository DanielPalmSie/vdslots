@extends('admin.layout')

@section('content-header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Gamification Section</h1>
                </div>

                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('home') }}"><i class="fa fa-cog mr-2"></i>Admin Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('gamification-dashboard') }}">Gamification</a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">

        @include('admin.gamification.partials.topmenu')

        <div class="row">
            <div class="col-lg-4 col-12">
                <div class="card card-solid card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-gift"></i> Trophy Awards</h3>
                    </div>
                    <div class="card-body">
                        <p>Trophy Awards are what the award will be for a certain Trophy.</p>
                        <a href="{{ $app['url_generator']->generate('trophyawards.new') }}"><i class="fa fa-plus"></i> Create a New Trophy Award</a> or
                        <a href="{{ $app['url_generator']->generate('trophyawards.index') }}"><i class="fa fa-list"></i> List and <i class="fa fa-search"></i> Search Trophy Awards</a>.
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-12">
                <div class="card card-solid card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-trophy"></i> Trophies</h3>
                    </div>
                    <div class="card-body">
                        <p>Trophies are gifts to users, that they get at certain points when playing.</p>
                        <a id="new_trophy" href="{{ $app['url_generator']->generate('trophies.new') }}"><i class="fa fa-plus"></i> Create a New Trophy</a> or
                        <a id="trophy_list" href="{{ $app['url_generator']->generate('trophies.index') }}"><i class="fa fa-list"></i> List and <i class="fa fa-search"></i> Search Trophies</a>.
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-12">
                <div class="card card-solid card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-shield-alt"></i> Tournament Templates</h3>
                    </div>
                    <div class="card-body">
                        <p>Tournament Templates are templates for the "Battle of Slots" functionality. A template specifies the rules for a certain battle.</p>
                        <a href="{{ $app['url_generator']->generate('tournamenttemplates.new') }}"><i class="fa fa-plus"></i> Create a New Tournament Template</a> or
                        <a href="{{ $app['url_generator']->generate('tournamenttemplates.index') }}"><i class="fa fa-list"></i> List and <i class="fa fa-search"></i> Search Tournament Templates</a>.
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-4 col-12">
                <div class="card card-solid card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-shield-alt"></i> Tournaments</h3>
                    </div>
                    <div class="card-body">
                        <p>Tournaments of the "Battle of Slots" functionality.</p>
                        <a href="{{ $app['url_generator']->generate('tournaments.index') }}"><i class="fa fa-list"></i> List and <i class="fa fa-search"></i> Search Tournaments</a>.
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-12">
                <div class="card card-solid card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-gem"></i> Bonus Types</h3>
                    </div>
                    <div class="card-body">
                        <p>A Bonus Type is all types of bonuses explained.</p>
                        <a href="{{ $app['url_generator']->generate('bonustypes.wizard') }}"><i class="fa fa-magic"></i> Bonus Type Wizard</a>,
                        <a href="{{ $app['url_generator']->generate('bonustypes.new') }}"><i class="fa fa-plus"></i> Create New Bonus Type from scratch</a> or
                        <a href="{{ $app['url_generator']->generate('bonustypes.index') }}"><i class="fa fa-list"></i> List and <i class="fa fa-search"></i> Search Bonus Types</a>.
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-12">
                <div class="card card-solid card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-asterisk"></i> The Wheel Of Jackpots</h3>
                    </div>
                    <div class="card-body">
                        <p>The Wheel Of Jackpots to give awards to players</p>
                        <a href="{{ $app['url_generator']->generate('wheelofjackpots-create-wheel') }}"><i class="fa fa-plus"></i> Add New Wheel</a> or
                        <a href="{{ $app['url_generator']->generate('wheelofjackpots') }}"><i class="fa fa-list"></i> List and <i class="fa fa-search"></i> Search Wheels</a>.
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-4 col-12">
                <div class="card card-solid card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-asterisk"></i> Jackpots</h3>
                    </div>
                    <div class="card-body">
                        <p>The Wheel of Jackpots to give awards to players</p>
                        <a href="{{ $app['url_generator']->generate('jackpot.index') }}"><i class="fa fa-list"></i> List Jackpots</a>.
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-12">
                <div class="card card-solid card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-car"></i> Race Templates</h3>
                    </div>
                    <div class="card-body">
                        <p>A Race Template is a template for creating the same races repeatedly.</p>
                        <a href="{{ $app['url_generator']->generate('racetemplates.new') }}"><i class="fa fa-plus"></i> Create Race Template </a> or
                        <a href="{{ $app['url_generator']->generate('racetemplates.index') }}"><i class="fa fa-list"></i> List and <i class="fa fa-search"></i> Search Race Templates</a>.
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-xs-12">
                <div class="card card-solid card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fa fa-upload"></i> Upload CSV to Give Awards
                        </h3>
                    </div>
                    <div class="card-body table-responsive">
                        <p>Upload a CSV file to bulk award players.</p>
                        <a href="{{ $app['url_generator']->generate('award.upload') }}">
                            <i class="fa fa-upload"></i> Upload CSV
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
