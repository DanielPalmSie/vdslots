<?php
    use App\Helpers\DataFormatHelper;
    $excluded_countries = DataFormatHelper::getSelect2FormattedData(DataFormatHelper::getCountryList(), [
        "id" => 'iso',
        "text" => 'printable_name'
    ]);
    ?>

    <div class="form-group row">
        <label class="col-sm-2 col-form-label" for="name">
            Name <span data-toggle="tooltip" title="Needs to be unique." class="badge bg-lightblue">?</span>
        </label>
        <div class="col-sm-3">
            <input id="input-alias" data-uniqueid="{{ @$wheel->id }}" name="name" class="form-control" type="text" value="{{ @$wheel->name }}" maxlength="50">
        </div>
         <div class="col-sm-7">&nbsp;</div>
    </div>
    <div class="form-group row">
        <label class="col-sm-2 col-form-label" for="cost_per_spin">Cost per spin</label>
        <div class="col-sm-3">
            <input id="input-alias" data-uniqueid="{{ @$wheel->cost_per_spin }}" name="cost_per_spin" class="form-control" type="text" value="{{ @$wheel->cost_per_spin }}">
        </div>
        <div class="col-sm-7">&nbsp;</div>
    </div>
    <div class="form-group row">
        <label class="col-sm-2 col-form-label" for="cost_per_spin">Country (leave empty for a custom wheel, ISO2 or ALL for default wheels)</label>
        <div class="col-sm-3">
            <input id="input-alias" data-uniqueid="{{ @$wheel->country }}" name="country" class="form-control" type="text" value="{{ @$wheel->country }}">
        </div>
        <div class="col-sm-7">&nbsp;</div>
    </div>
    <div class="form-group row">
        <label class="col-sm-2 col-form-label" for="wh_excluded_countries">Excluded Countries</label>
        <div class="col-sm-3">
            <select id="wh_excluded_countries" name="excluded_countries[]" class="form-control select2-class select-style-type select2-multiple" style="width: 100%;" data-allow-clear="true" multiple="multiple">
                @foreach ($excluded_countries as $country)
                    <option value="{{ $country['id'] }}" {{in_array($country['id'], $wheel->excluded_countries) ? 'selected="selected"' : ''}}>{{ $country['text'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-7">&nbsp;</div>
    </div>
    <div class="form-group row">
        <label class="col-sm-2 col-form-label" for="cost_per_spin">Wheel Style</label>
        <div class="col-sm-3">
            <select id="select-input-style" name="style" class="form-control select2-class select-style-type" style="width: 100%;" data-placeholder="No style specified" data-allow-clear="true">
                @foreach ($styles as $style)
                    @if ($style['name'] === $wheel->style)
                        <option value="{{ $style['name'] }}" data-colors="{{$style['colors']}}" selected="true">{{ ucfirst($style['name']) }}</option>
                    @else
                        <option value="{{ $style['name'] }}" data-colors="{{$style['colors']}}" >{{ ucfirst($style['name']) }}</option>
                    @endif
                @endforeach
            </select>
        </div>
        <div class="col-sm-7">&nbsp;</div>
    </div>

    <input type="hidden" name="number_of_slices" value='{{ $wheel->number_of_slices }}' />
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">






