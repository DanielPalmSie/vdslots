<div class="form-group row">
    <label class="col-sm-3 col-form-label" for="award_type">Award type</label>
    <div class="col-sm-9">
        <div class="form-group col-12 col-md-12 col-lg-8 col-xlg-6 col-fhd-4">
            <select id="select-award-type" name="award_type" class="form-control select2-class" style="width: 100%;" data-placeholder="Select award type" data-allow-clear="true">
                <option></option>
                @foreach($award_types as $award_type)
                    <option value="{{$award_type['name']}}">{{$award_type['name']}}</option>
                @endforeach
            </select>
      	 </div>
    </div>
</div>

<div id="regular_award_area" style="display: none">
    <div class="form-group row">
        <label class="col-sm-3 col-form-label" for="prize">Prize</label>
        <div class="col-sm-9">
            @if($award)
                <input id="input-alias" data-uniqueid="{{ $award->id }}" name="prize" class="form-control" type="text" value="{{ $award->prize }}">
            @else
                <input id="input-alias" data-uniqueid="" name="prize" class="form-control" type="text" value="">
            @endif
        </div>
    </div>
</div>

<div id="jackpot_area" style="display: none">
    <div class="form-group row">
        <label class="col-sm-3 col-form-label" for="jackpot">Jackpot</label>
        <div class="col-sm-9">
            <div class="form-group col-12 col-md-12 col-lg-8 col-xlg-6 col-fhd-4">
                <select id="select-jackpot" name="jackpot_id" class="form-control select2-class" style="width: 100%;" data-placeholder="Select jackpot" data-allow-clear="true">
                    <option></option>
                    @foreach($jackpots as $jackpot)
                        <option value="{{$jackpot['id']}}">{{$jackpot['name']}}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>




