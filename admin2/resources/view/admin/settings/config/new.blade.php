@extends('admin.layout')

@section('content')
    <div class="container-fluid">

        @include('admin.settings.config.partials.topmenu')

        <div class="card card-solid card-primary">
            <div class="card-header">
                <h3 class="card-title">New Config</h3>
                <div class="float-right">
                    <a href="{{ $app['url_generator']->generate('settings.config.index') }}"><i class="fa fa-arrow-left"></i> Back to the list</a>
                </div>
            </div>
            <div class="card-body">
                <p>
                    Specify <b>name</b>, <b>tag</b> and <b>type</b>, and you can edit the <b>value</b> after pressing the "Create New Config" button. This way, the appropriate way to edit the value will be shown.
                </p>
            </div>
            @include('admin.settings.config.partials.configbox')
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent

    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>

    @include('admin.settings.config.partials.configsharedjs')

    <script type="text/javascript">

        $(document).ready(function() {

            $('#save-config-btn').on('click', function(e) {
                e.preventDefault();
                createConfig(getAllNonModalButtons());
            });
        });
    </script>
@endsection
