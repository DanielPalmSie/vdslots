<div class="card-body">

@if ($app)

    @if ($buttons['close-battle'])
    <div class="modal fade" id="confirm-close" tabindex="-1" role="dialog" aria-labelledby="myModalCloseLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="myModalCloseLabel">Close Battle</h4>
                </div>

                <div class="modal-body">
                    <div class="col-12 col-sm-12 col-lg-12">
                        <form id="close-battle-form" role="form">
                            <div class="form-group row">
                                <p>Are you sure you want to close the battle?</p>
                                @if(!empty($unclosed_battles))
                                    <div class="form-group row">
                                        <label class="col-sm-5 col-form-label" style="text-align:right" for="tournament_id">{{ $unclosed_battles['tournament_name'] }}<br>({{ $unclosed_battles['id'] }})</label>
                                        <div class="col-sm-7">
                                            <button type="button" id="close-tournament-btn" class="btn btn-warning" onclick="closeBattle({{ $unclosed_battles['id'] }}, '{{ $unclosed_battles['tournament_name'] }}')" data-toggle="tooltip" title="Close the tournament and pay out prizes">
                                                {{ $buttons['close-battle'] }}
                                            </button>
                                        </div>
                                    </div>
                                @else
                                <p>Tounament: <b>{{ $tournament->id }}</b> is not eligible for close battles <span data-toggle="tooltip" title="Only tournament data with status=finished, win_amount>0 and result_place=0 are eligible" style="opacity:0.3;" class="badge bg-lightblue">?</span> </p>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="col-12 col-sm-6 col-lg-6">
        <form id="tournament-form" class="" method="post">
            <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
            @if($tournament)
            <input id="tournament_id" class="form-control" type="hidden" value="{{ $tournament->id }}">
        @endif
        <div class="form-group row">
            <label class="col-sm-4 col-form-label" for="tournament_name">Tournament Name <span data-toggle="tooltip" title="Easy to read and sumarized tournament name that users will see." style="opacity:0.3;" class="badge bg-lightblue">?</span></label>
            <div class="col-sm-8">
                <input id="tournament_name" name="tournament_name" readonly class="form-control" type="text" value="{{ $tournament->tournament_name }}">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-4 col-form-label" for="game_ref">Game Ref <span data-toggle="tooltip" title="The game this tournament references." style="opacity:0.3;" class="badge bg-lightblue">?</span></label>
            <div class="col-sm-8">
                <div class="row">
                    <div class="col-sm-12">
                        <input id="game_ref" name="game_ref" readonly class="form-control" type="text" value="{{ $tournament->game_ref }}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                    Game Name: <span id="span-game_ref_game_name">@if($game){{ $game->game_name }}@endif</span>
                    </div>
                </div>
            </div>
        </div>

        @foreach ($columns_order as $co)
            <div class="form-group row">
                <label class="col-sm-4 col-form-label" for="{{ $co['column'] }}">{{ ucwords(str_replace('_', ' ', $co['column'])) }} @if(isset($co['tooltip']))<span data-html="true" data-toggle="tooltip" title="{{ $co['tooltip'] }}" style="opacity:0.3;" class="badge bg-lightblue">?</span>@endif
                    @if(isset($co['help-block']))
                        <button class="btn btn-xs help-btn">Learn more...</button>
                    @endif
                </label>
                <div class="col-sm-8">
                    <input id="input-{{ $co['column'] }}"
                    data-d="{{ $co['readonly'] }}"
                    @if($co['readonly'])
                        readonly
                    @endif
                    @if($co['placeholder'])
                        placeholder="{{ $co['placeholder'] }}"
                    @endif
                        name="{{ $co['column'] }}" class="form-control" type="{{ $co['type'] }}"
                    @if ($tournament)
                        value="{{ $tournament->{$co['column']} }}"
                    @else
                        value=""
                    @endif
                    >

                    @if(isset($co['help-block']))
                        <p class="collapse help-block">{!! nl2br($co['help-block']) !!}</p>
                    @endif
                </div>
            </div>
        @endforeach

        </form>
    </div>

    <div id="button-footer">
        <div class="form-group row">
            <div class="col-sm-12 text-center">
                @if ($buttons['cancel'])
                    <button id="cancel-tournament-btn" class="btn btn-info">
                        {{ $buttons['cancel'] }}
                    </button>
                @endif
                @if ($buttons['pause'])
                    &nbsp; | &nbsp;
                    <button id="pause-tournament-btn" class="btn btn-info">
                        {{ $buttons['pause'] }}
                    </button>
                @endif
                @if ($buttons['resume'])
                    &nbsp; | &nbsp;
                    <button id="resume-tournament-btn" class="btn btn-info">
                        {{ $buttons['resume'] }}
                    </button>
                @endif
                @if ($buttons['calc_prizes'])
                    &nbsp; | &nbsp;
                    <button id="calc-prizes-btn" class="btn btn-primary">
                        {{ $buttons['calc_prizes'] }}
                    </button>
                @endif
                @if ($buttons['close-battle'])
                    &nbsp; | &nbsp;
                    <button id="view-close-tournaments-btn" class="btn btn-warning" data-toggle="modal" data-target="#confirm-close">
                        {{ $buttons['close-battle'] }}
                    </button>
                @endif
            </div>
        </div>
    </div>

    @push('extrajavascript')
    <script type="text/javascript" src="/phive/js/mg_casino.js"></script>
    <script type="text/javascript">

        $(document).ready(function() {

            $('.help-btn').on('click', function(e) {
                e.preventDefault();
                var $this = $(this);
                var $collapse = $this.closest('.form-group').find('.help-block');
                $collapse.collapse('toggle');
            });

        });

        function closeBattle(t_id, tournament_name){
            $.ajax({
                url: "{{ $app['url_generator']->generate('tournaments.close')}}",
                type: "POST",
                data: { 't_id': {{ t_id }} },
                success: function (response, text_status, jqXHR) {

                    $('#confirm-close').modal('hide');

                    if (response.success == true) {
                        displayNotifyMessage('success', 'Tournament:'+tournament_name+'['+t_id+'] is closed successfully.');
                    } else {
                        displayNotifyMessage('error', 'Closing the tournament:'+tournament_name+'['+t_id+'] failed.');
                    }

                    setTimeout(function(){ location.reload(); }, 3000);

                },
                error: function (jqXHR, text_status, error_thrown) {
                    console.log(error_thrown);

                }
            });

        }

    </script>
    @endpush

@endif

</div>
