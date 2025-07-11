<div style="height: 58.49px; box-sizing: border-box">
    <label for="number-range-start-{{ $name }}">{{ $label }}</label>
    <div class="input-group" >
        <input name="{{ $name }}_start" value="{{ $params[$name]['start'] }}" type="number"
               class="form-control" id="number-range-start-{{ $name }}" placeholder="start">
        <div class="input-group-prepend">
            <div class="input-group-text">-</div>
        </div>
        <input name="{{ $name }}_end" value="{{ $params[$name]['end'] }}" type="number"
               class="form-control" id="number-range-end-{{ $name }}" placeholder="end">
    </div>
</div>
