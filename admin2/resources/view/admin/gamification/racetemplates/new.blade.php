@extends('admin.layout')

@section('content')
    <div class="container-fluid">
        @include('admin.gamification.racetemplates.partials.topmenu')

        <div class="card card-solid card-primary">
            <div class="card-header">
                <h3 class="card-title">New Trophy</h3>
                <div style="float: right">
                    <a href="{{ $app['url_generator']->generate('racetemplates.index') }}"><i class="fa fa-arrow-left"></i> Back to the list</a>
                </div>
            </div>
            @include('admin.gamification.racetemplates.partials.racetemplatebox')
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script src="/phive/admin/customization/plugins/bootstrap-toggle/bootstrap-toggle.min.js"></script>

    @include('admin.gamification.racetemplates.partials.racetemplatesharedjs')

    <script type="text/javascript">

        $(document).ready(function() {

            enableSelect2Controllers();

            $('#save-racetemplate-btn').on('click', function(e) {
                e.preventDefault();
                createRaceTemplate($(this));
            });

        });
    </script>
@endsection
