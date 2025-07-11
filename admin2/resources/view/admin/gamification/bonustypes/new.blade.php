@extends('admin.layout')

@section('content')
    <div class="container-fluid">

        @include('admin.gamification.bonustypes.partials.topmenu')

        <div class="card card-solid card-primary">
            <div class="card-header">
                <h3 class="card-title">New Bonus Type</h3>
                <div style="float: right">
                    <a href="{{ $app['url_generator']->generate('bonustypes.index') }}"><i class="fa fa-arrow-left"></i> Back to the list</a>
                </div>
            </div>
            @include('admin.gamification.bonustypes.partials.bonustypebox')
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script src="/phive/admin/customization/plugins/bootstrap-toggle/bootstrap-toggle.min.js"></script>

    @include('admin.gamification.bonustypes.partials.bonustypesharedjs')

    <script type="text/javascript">

        $(document).ready(function() {

            enableSelect2Controllers();
            $('.timepicker').datetimepicker({
                format: 'HH:mm',
                icons: {
                    time: 'fa fa-clock',
                    date: 'fa fa-calendar',
                    up: 'fa fa-chevron-up',
                    down: 'fa fa-chevron-down',
                }
            });

            $('#save-bonustype-btn').on('click', function(e) {
                e.preventDefault();
                createBonusType($(this));
            });

        });
    </script>
@endsection
