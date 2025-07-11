@extends('admin.layout')

@section('content')
    <div class="container-fluid">

        @include('admin.gamification.trophies.partials.topmenu')

        <div class="card card-solid card-primary">
            <div class="card-header">
                <h3 class="card-title">New Trophy</h3>
                <div style="float: right">
                    <a href="{{ $app['url_generator']->generate('trophies.index') }}"><i class="fa fa-arrow-left"></i> Back to the list</a>
                </div>
            </div>
            @include('admin.gamification.trophies.partials.trophybox')
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script src="/phive/admin/customization/plugins/bootstrap-toggle/bootstrap-toggle.min.js"></script>

    @include('admin.gamification.trophies.partials.trophysharedjs')

    <script type="text/javascript">

        // TODO: Could probably be moved to the sharedjs file.
        function doUpdatedAliasUpdates() {
            var input_alias = $("#input-alias");
            var uniqueid    = input_alias.data('uniqueid');
            var new_alias   = input_alias.val();

            synchronizeLocalizedStringsWithAlias(new_alias);

            updateTrophyImages(uniqueid, new_alias);
        }

        $(document).ready(function() {

            enableSelect2Controllers();
            enableDropZone();

            $('#save-all-btn').on('click', function(e) {
                e.preventDefault();
                createTrophy($(this));
            });

            // TODO: Could probably be moved to the sharedjs file.
            $("#input-alias").keyup(function(e) {
                //var uniqueid = input.data('uniqueid');
                //var new_alias = e.target.value;
                doUpdatedAliasUpdates();
            });

            $("#input-alias").change(function() {
                doUpdatedAliasUpdates();
            });

        });
    </script>
@endsection
