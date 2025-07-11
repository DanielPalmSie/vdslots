<form method="get">
    <div class="card border-top border-top-3">
        <div class="card-body">
            <div class="row">
                <div class="col-6 col-lg-1">
                    <label>From</label>
                    <input autocomplete="off" data-date-format="yyyy-mm-dd" type="text"
                           name="start_date"
                           class="form-control daterange-picker" placeholder="Start date"
                           value="{{ empty($app['request_stack']->getCurrentRequest()->get('ext_start_date')) ? (!empty($query_data['start_date']) ? \Carbon\Carbon::parse($query_data['start_date'])->format('Y-m-d') : null) :  $app['request_stack']->getCurrentRequest()->get('ext_start_date')}}">
                </div>
                <div class="col-6 col-lg-1">
                    <label>To</label>
                    <input autocomplete="off" data-date-format="yyyy-mm-dd" type="text"
                           name="end_date"
                           class="form-control daterange-picker" placeholder="End date"
                           value="{{ empty($app['request_stack']->getCurrentRequest()->get('ext_end_date')) ? (!empty($query_data['end_date']) ? \Carbon\Carbon::parse($query_data['end_date'])->format('Y-m-d') : null) : $app['request_stack']->getCurrentRequest()->get('ext_end_date') }}">
                </div>
                <div class="col-6 col-lg-2">
                    <label for="select-bonus">Bonus Bets</label>
                    <select name="bonus" id="select-bonus" class="form-control">
                        <option {{ $query_data['bonus'] == 'all' ? 'selected' : '' }} value="all">All</option>
                        <option {{ $query_data['bonus'] == 'yes' ? 'selected' : '' }} value="yes">Yes</option>
                        <option {{ $query_data['bonus'] == 'no' ? 'selected' : '' }} value="no">No</option>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <div class="form-group">
                        <label for="select-operators">Operator</label>
                        <br>
                        <input list="select-operator" id="select-operators" name="operatort"
                               data-placeholder="Select an operator" class="form-control"
                               onclick="clearValue(this)"
                               value="{{ $app['request_stack']->getCurrentRequest()->get('operatort') }}"
                        >
                        <datalist id="select-operator">
                            @foreach(\App\Repositories\GameRepository::getOperatorNetworkList() as $operator)
                                <option value="{{ $operator->name . " via ". $operator->network }}"
                                        data-reference="{{ $operator->name . "::". $operator->network }}"></option>
                            @endforeach
                        </datalist>
                        <input type="hidden" name="operator" id="select-operators-hidden"
                               value="{{ $app['request_stack']->getCurrentRequest()->get('operator') }}">
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <div class="form-group">
                        <label for="select-games">Game</label>
                        <br>
                        <input list="select-game" id="select-games" name="gamet"
                               data-placeholder="Select a game" class="form-control" onclick="clearValue(this)"
                               value="{{ $app['request_stack']->getCurrentRequest()->get('gamet') }}"
                        >
                        <datalist id="select-game">
                            @foreach(\App\Repositories\GameRepository::getAllGameList() as $game)
                                <option value="{{ $game->game_name ." - " . $game->device_type}}"
                                        data-reference="{{ $game->ext_game_name . "|" . $game->device_type }}"></option>
                            @endforeach
                        </datalist>
                        <input type="hidden" name="game" id="select-games-hidden"
                               value="{{ $app['request_stack']->getCurrentRequest()->get('game') }}">
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <label for="select-order">Order By</label>
                    <select name="order" id="select-order" class="form-control mb-2">
                        <option {{ $query_data['order'] == 'ASC' ? 'selected' : '' }}  value="ASC">Date ascending
                        </option>
                        <option {{ $query_data['order'] == 'DESC' ? 'selected' : '' }} value="DESC">Date descending
                        </option>
                    </select>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="chrono" value="1"
                               @if(!empty($query_data['chrono'])) checked @endif>
                        <label class="form-check-label">
                            List chronologically
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="only_trans" value="1"
                               @if($query_data['only_trans']) checked @endif>
                        <label class="form-check-label">
                            Show only transactions
                        </label>
                    </div>
                </div>
                <div class="col-6 col-lg-1">
                    <label for="select-order">Vertical</label>
                    <select name="vertical" id="select-vertical" class="form-control">
                        <option {{ $query_data['vertical'] == 'all' ? 'selected' : '' }}  value="all">All</option>
                        <option {{ $query_data['vertical'] == 'casino' ? 'selected' : '' }}  value="casino">Casino
                        </option>
                        <option {{ $query_data['vertical'] == 'sportsbook' ? 'selected' : '' }}  value="sportsbook">
                            Sportsbook
                        </option>
                        <option {{ $query_data['vertical'] == 'poolx' ? 'selected' : '' }}  value="poolx">
                            Poolx
                        </option>
                        <option {{ $query_data['vertical'] == 'bingo' ? 'selected' : '' }}  value="bingo">
                            Bingo
                        </option>
                    </select>
                </div>
                <input type="hidden" name="mp" value="{{ $query_data['mp'] }}">
            </div>
        </div>
        <div class="card-footer">
            <button
                formaction="{{ $app['url_generator']->generate(!empty($form_url) ? $form_url : 'admin.user-betsandwins-all', ['user' => $user->id]) }}"
                class="btn btn-info">Search
            </button>
        </div>
    </div>
</form>

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script type="text/javascript">
        let gamesList = getGames();
        $(document).ready(function() {
            let urlParams = new URLSearchParams(window.location.search);
            changeOperator(urlParams.get('operatort'),urlParams.get('gamet'));
        })
        $(function () {
            updateValueFromLabel();
            $('')

            $('.daterange-picker').daterangepicker({
                singleDatePicker: true,
                showDropdowns: true,
                autoUpdateInput: true,
                locale: {
                    format: 'YYYY-MM-DD'
                }
            });
        })
        function updateValueFromLabel() {
            document.querySelector('#select-games').addEventListener('input', function(e) {
                var input = e.target,
                    list = input.getAttribute('list'),
                    options = document.querySelectorAll('#' + list + ' option[value="'+input.value+'"]'),
                    hiddenInput = document.getElementById(input.getAttribute('id') + '-hidden');
                    hiddenInputName = document.getElementById(input.getAttribute('id') + '-name-hidden');
                if (options.length > 0) {
                    hiddenInput.value = options[0].getAttribute('data-reference');
                }
            });

            document.querySelector('#select-operators').addEventListener('input', function(e) {
                changeOperator(e.target.value);
                var input = e.target,
                    list = input.getAttribute('list'),
                    options = document.querySelectorAll('#' + list + ' option[value="'+input.value+'"]'),
                    hiddenInput = document.getElementById(input.getAttribute('id') + '-hidden');
                if (options.length > 0) {
                    hiddenInput.value = options[0].getAttribute('data-reference');
                }
            });
            }

        function getGames(){
            return @json(\App\Repositories\GameRepository::getAllGameList());
        }

        function changeOperator(operator, defaultVal =""){
            let games = gamesList;
            let gameListHTML = document.getElementById('select-game');
            let gameInput = document.getElementById('select-games');
            let operatorInput = document.getElementById('select-operators');


            // clearValue(gameInput);
            gameListHTML.innerHTML = "";
            if(operator == "" || operator == null) {
                for (let i = 0; i < games.length; i++) {
                        let option = document.createElement('option');
                        option.value = games[i].game_name + " - " + games[i].device_type;
                        option.setAttribute('data-reference', games[i].ext_game_name + "|" + games[i].device_type);
                        gameListHTML.appendChild(option);
                }
                return;
            }
            let parts = operator.split(" via ");
            for (let i = 0; i < games.length; i++) {
                if(games[i].operator.toLowerCase() == parts[0].toLowerCase() &&
                    games[i].network.toLowerCase() == parts[1].toLowerCase()) {
                    let option = document.createElement('option');
                    option.value = games[i].game_name + " - " + games[i].device_type;
                    option.setAttribute('data-reference', games[i].ext_game_name + "|" + games[i].device_type);
                    gameListHTML.appendChild(option);
                }
            }
        }

        function clearValue(input){
            input.value = "";
            document.getElementById(input.id+"-hidden").value = "";
            let event = new Event('input');
            input.dispatchEvent(event);
        }
    </script>

@endsection
