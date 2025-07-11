@extends('admin.layout')

@section('content-header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Games Section</h1>
            </div>

            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('home') }}"><i class="fa fa-gear"></i>Admin Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('game.dashboard') }}"><i class="fa fa-gear"></i>Games</a></li>
                    <li class="breadcrumb-item active">Bulk Import</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">

        @include('admin.game.partials.topmenu')

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Games Bulk Import</h3>
            </div>

            <div class="card-body">

                <div class="card-body">
                    <p>Upload CSV file.</p>

                    <form action="{{ $app['url_generator']->generate('games.handle-bulk-import') }}" class="dropzone" id="dropzone">
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script type="text/javascript">
        Dropzone.options.dropzone = {
            filesizeBase: 1024,
            maxFiles: 1,
            headers : {
                "X-CSRF-TOKEN" : document.querySelector('meta[name="csrf_token"]').content
            },
            acceptedFiles: ".csv, text/csv, application/csv, text/x-csv, application/x-csv, text/comma-separated-values, text/x-comma-separated-values",
            addRemoveLinks: true,
            init: function () {
                this.on("sending", function() {
                });
                this.on("success", function(files, response) {
                    console.log(response)
                    showAjaxFlashMessages()
                });
                this.on("error", function(files, error) {
                    this.removeFile(files);
                    displayNotifyMessage("error", error);
                });
            }
        }
    </script>
@endsection
