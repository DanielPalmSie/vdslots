@extends('admin.layout')

@section('content')
    <div class="container-fluid">
        @include('admin.gamification.tournamenttemplates.partials.topmenu')

        <div class="card card-solid card-primary">
            <div class="card-header">
                <h3 class="card-title">New Tournament Template</h3>
                <div style="float: right">
                    <a href="{{ $app['url_generator']->generate('tournamenttemplates.index') }}"><i class="fa fa-arrow-left"></i> Back to the list</a>
                </div>
            </div>
            @include('admin.gamification.tournamenttemplates.partials.tournamenttemplatebox')
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script src="/phive/admin/customization/plugins/bootstrap-toggle/bootstrap-toggle.min.js"></script>

    @include('admin.gamification.tournamenttemplates.partials.tournamenttemplatesharedjs')

    <script type="text/javascript">

        $(document).ready(function() {

            enableSelect2Controllers();
            enableDropZone();

            $('#save-tournament-template-btn').on('click', function(e) {
                e.preventDefault();
                createTournamentTemplate(getAllNonModalButtons());
            });

        });
    </script>
@endsection
