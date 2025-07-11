@extends('admin.layout')

@section('content')
    <div class="container-fluid">

        @include('admin.gamification.tournaments.partials.topmenu')

        <div class="card card-solid card-primary">
            <div class="card-header">
                <h3 class="card-title">View Tournament</h3>
                <div style="float: right">
                    <a href="{{ $app['url_generator']->generate('tournaments.index') }}"><i class="fa fa-arrow-left"></i> Back to the list</a>
                </div>
            </div>
            @include('admin.gamification.tournaments.partials.tournamentbox')
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script src="/phive/admin/customization/plugins/bootstrap-toggle/bootstrap-toggle.min.js"></script>

    @stack('extrajavascript')

    <script type="text/javascript">

    $(document).ready(function() {

        var t_id     = $('#tournament_id').val();
        var t_name   = $('#tournament_name').val();

        $("#cancel-tournament-btn").click(function(e) {
            cancelTournament(t_id, t_name);
        });

        $("#pause-tournament-btn").click(function(e) {
            pauseTournament(t_id, t_name);
        });

        $("#resume-tournament-btn").click(function(e) {
            resumeTournament(t_id, t_name);
        });

        $("#calc-prizes-btn").click(function(e) {
            calcTournament(t_id, t_name);
        });

    });

    function cancelTournament(t_id, tournament_name){
        $.ajax({
            url: "{{ $app['url_generator']->generate('tournaments.cancel')}}",
            type: "POST",
            data: { 't_id': {{ t_id }} },
            success: function (response, text_status, jqXHR) {

                if (response.success == true) {
                    displayNotifyMessage('success', 'Tournament:'+tournament_name+'['+t_id+'] is cancelled successfully.');
                } else {
                    displayNotifyMessage('error', 'Cancelling the tournament:'+tournament_name+'['+t_id+'] failed.');
                }

                setTimeout(function(){ location.reload(); }, 3000);

            },
            error: function (jqXHR, text_status, error_thrown) {
                console.log(error_thrown);

            }
        });

    }

    function pauseTournament(t_id, tournament_name){
        $.ajax({
            url: "{{ $app['url_generator']->generate('tournaments.pause')}}",
            type: "POST",
            data: { 't_id': {{ t_id }} },
            success: function (response, text_status, jqXHR) {

                if (response.success == true) {
                    displayNotifyMessage('success', 'Tournament:'+tournament_name+'['+t_id+'] is paused successfully.');
                } else {
                    displayNotifyMessage('error', 'Pausing the tournament:'+tournament_name+'['+t_id+'] failed.');
                }

                setTimeout(function(){ location.reload(); }, 3000);

            },
            error: function (jqXHR, text_status, error_thrown) {
                console.log(error_thrown);

            }
        });
    }

    function resumeTournament(t_id, tournament_name){
        $.ajax({
            url: "{{ $app['url_generator']->generate('tournaments.resume')}}",
            type: "POST",
            data: { 't_id': {{ t_id }} },
            success: function (response, text_status, jqXHR) {

                if (response.success == true) {
                    displayNotifyMessage('success', 'Tournament:'+tournament_name+'['+t_id+'] is resumed successfully.');
                } else {
                    displayNotifyMessage('error', 'Resuming the tournament:'+tournament_name+'['+t_id+'] failed.');
                }

                setTimeout(function(){ location.reload(); }, 3000);

            },
            error: function (jqXHR, text_status, error_thrown) {
                console.log(error_thrown);

            }
        });
    }

    function calcTournament(t_id, tournament_name){
        $.ajax({
            url: "{{ $app['url_generator']->generate('tournaments.calc')}}",
            type: "POST",
            data: { 't_id': {{ t_id }} },
            success: function (response, text_status, jqXHR) {

                if (response.success == true) {
                    displayNotifyMessage('success', 'Calculating prizes for tournament:'+tournament_name+'['+t_id+'] is done successfully.');
                } else {
                    displayNotifyMessage('error', 'Calculating prizes for tournament:'+tournament_name+'['+t_id+'] failed.');
                }

                setTimeout(function(){ location.reload(); }, 3000);

            },
            error: function (jqXHR, text_status, error_thrown) {
                console.log(error_thrown);

            }
        });
    }

    </script>
@endsection
