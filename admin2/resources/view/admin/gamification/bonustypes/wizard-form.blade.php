<h4 id="bonus-type-name" class="text-center">{{ ucwords(str_replace("-", " ", $bonustype)) }}</h4>
<form id="bonustype-form" data-type="" method="post">
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
    @foreach ($bonustype_wizard_data[$bonustype]['bonus-types-wizard'] as $data)
        <div class="form-group row">
            <label class="col-sm-3 col-form-label" for="{{ $data['name'] }}">
                {{ ucwords(str_replace("_", " ", $data['name'])) }}
            </label>
            <div class="col-sm-9">
                @if ($data['name'] == 'ext_ids')
                    <select name="ext_ids[]" id="select-ext_ids" class="form-control select2-class" style="width: 100%;"
                            data-placeholder="Select one or multiple" data-allow-clear="true" multiple="multiple">
                        @foreach(\App\Helpers\DataFormatHelper::getExternalGameNameList() as $ext_id)
                            <option value="{{ $ext_id['ext_game_name'] }}"> {{ $ext_id['ext_game_name'] }} </option>
                        @endforeach
                    </select>
                @elseif ($data['name'] == 'game_id')
                    <select name="game_id[]" id="select-game_id" class="form-control select2-class" style="width: 100%;"
                            data-placeholder="Select one or multiple" data-allow-clear="true" multiple="multiple">
                        @foreach(\App\Helpers\DataFormatHelper::getGameIdList() as $game_id)
                            <option value="{{ $game_id['game_id'] }}"> {{ $game_id['game_id'] }} </option>
                        @endforeach
                    </select>
                @else
                    @if ($data['type'] == 'date')
                        <input id="input-{{ $data['name'] }}" name="{{ $data['name'] }}" class="form-control"
                               data-provide="datepicker" data-date-format="yyyy-mm-dd" value="{{ $data['value'] }}">
                    @else
                        <input id="input-{{ $data['name'] }}" name="{{ $data['name'] }}" class="form-control"
                               type="{{ $data['type'] }}" value="{{ $data['value'] }}">
                    @endif
                @endif
            </div>
        </div>
    @endforeach
    @if (count($bonustype_wizard_data[$bonustype]['bonus-types-wizard-groups']) > 0)
        <hr/>
        <div class="form-group row">
            <label class="col-sm-3 col-form-label" for="group"> Group </label>
            <div class="col-sm-9">
                <select name="group" id="select-group" class="form-control select2-class" style="width: 100%;"
                        data-placeholder="Select one" data-allow-clear="true">
                    @foreach($bonustype_wizard_data[$bonustype]['bonus-types-wizard-groups'] as $group)
                        <option value="{{ $group }}"> {{ $group }} </option>
                    @endforeach
                </select>
                <hr/>
            </div>
        </div>
        @foreach ($bonustype_wizard_data[$bonustype]['bonus-types-wizard-defaults-unique'] as $name)
            <div class="form-group row">
                <label class="col-sm-3 col-form-label"
                       for="{{ $name }}">{{ ucwords(str_replace("_", " ", $name)) }}</label>
                <div class="col-sm-9">
                    <input id="input-{{ $name }}" name="{{ $name }}" class="form-control" type="text" readonly="true">
                </div>
            </div>
        @endforeach
    @else
        @foreach ($bonustype_wizard_data[$bonustype]['bonus-types-wizard-static-defaults'] as $data)
            @if ($data['visibility'] == 'show')
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label"
                           for="{{ $data['name'] }}">{{ ucwords(str_replace("_", " ", $data['name'])) }}</label>
                    <div class="col-sm-9">
                        @if (count($bonustype_wizard_data[$bonustype]['bonus-types-wizard-groups']) == 0)
                            <input id="input-{{ $data['name'] }}" name="{{ $data['name'] }}" class="form-control"
                                   type="text" readonly="true">
                        @else
                            <input id="input-{{ $data['name'] }}" name="{{ $data['name'] }}" class="form-control"
                                   type="text" readonly="true" value="{{ $data['value'] }}">
                        @endif
                    </div>
                </div>
            @else
                <input id="input-{{ $data['name'] }}" name="{{ $data['name'] }}" class="form-control" type="hidden"
                       value="{{ $data['value'] }}">
            @endif
        @endforeach
    @endif
    <div class="form-group row">
        <div class="col-sm-9 pull-right">
            <span>
                <button id="create-bonus-type-btn" type="button" class="btn btn-success">
                    Create Bonus Type!
                </button>
                or
            </span>
            <span>
                <button id="create-bonus-type-trophy-award-btn" type="button" class="btn btn-primary">
                    Create Bonus Type & Trophy Award
                </button>
            </span>
        </div>
    </div>
</form>

<script type="text/javascript">

    $(document).ready(function() {
    });

</script>
