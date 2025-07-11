
    <input type="hidden" name="token" value="{{$_SESSION['token']}}">
    <input type="hidden" name="game_id" value="{{ $game['id'] }}">
    <input type="hidden" name="id" value="{{ $id }}">
    <div class="card card-solid card-primary">
    	<div class="card-header with-border">
    	    <h3 class="card-title">{{$action}} Game Override for: {{ $game['game_name'] }}</h3>
    	</div>
    	<!-- /.card-header -->
    	<div class="card-body">

	    <div class="form-group row">
                <label class="col-sm-2 col-form-label" for="name">
                    Jurisdiction
                </label>
                <div class="col-sm-3">
                    <select name="country" id="select-country" class="form-control select2-class"
                            style="width: 100%;" data-placeholder="Select a country or select all" data-allow-clear="true">
                        <option value="ALL">All</option>

                        @foreach(array_merge(phive('Licensed')->getSetting('licensed_countries'), phive('MicroGames')->getSetting('game_override_extra_countries', [])) as $country)
                            <option value="{{ $country }}" {{$params['country'] == $country ? "selected" : ''}}>{{ $country }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-4">
                    The jurisdiction of this override, no country base support you need to choose MT, GB, SE, DK or ALL. (MT is for all MGA countries).
                </div>
            </div>

            @foreach($prepops as $col => $val)
                <div class="form-group row">
                    <label class="col-sm-2 col-form-label" for="name">
                        {{ $labels[$col] }}
                    </label>
                    <div class="col-sm-3">
                        <input name="{{ $col }}" class="form-control" type="text" value="{{ $val }}">
                    </div>
                    <div class="col-sm-4">
                        {{ $descrs[$col] }}.
                    </div>
                </div>
            @endforeach

            <div class="col-sm-7">
                <button class="btn btn-primary" type="submit">{{$action}}</button>
            </div>
    	</div>
    </div>

    @section('footer-javascript')
        @parent
        <script src="/phive/admin/plugins/select2/js/select2.min.js"></script>
        <script type="text/javascript">
            $(document).ready(function() {
                $("#select-country").select2().val("{{ $jurisdiction }}").change();
            });
        </script>
    @endsection