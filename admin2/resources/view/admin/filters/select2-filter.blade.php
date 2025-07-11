<div class="{{ $containerClass }}">
    <label for="{{ $name }}">{{ $label }}</label>
    <select
        name="{{ $multiple ? $name . '[]' : $name }}"
        id="select-{{ $name }}"
        class="form-control select2-class"
        style="width: 100%;"
        data-placeholder="{{ $placeholder ?? '' }}"
        data-allow-clear="{{ $allowClear ?? 'true' }}"
        @if($multiple) multiple="multiple" @endif
        @if($dataCurrent) data-current="{{ $dataCurrent}}" @endif
        @if($additionalSelectAttributes) {{ $additionalSelectAttributes }} @endif
    >
        @if(!$multiple)
            <option></option>
        @endif

        @foreach($additionalSelectOptions as $key => $value)
                <option value="{{ $value }}">{{ $key }}</option>
        @endforeach

        @if(isset($options) && is_callable($options))
            @foreach($options() as $option)
                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
            @endforeach
        @endif
    </select>
</div>

@section('footer-javascript')
    @parent
    <script type="text/javascript">
        /*common function*/
        function initializeSelect2(element, customOptions, templateCallback) {
            const defaultOptions = {
                templateResult: templateCallback,
                templateSelection: templateCallback,
                escapeMarkup: function (m) {
                    return m;
                }
            };

            const mergedOptions = { ...defaultOptions, ...customOptions };
            element.select2(mergedOptions).trigger('select2:select');
        }

        $(document).ready(function() {
            $("#select-{{ $name }}").select2().val("{{ $app['request_stack']->getCurrentRequest()->get($name) }}").change();

            @isset($additionalScriptCallback)
                {{ $additionalScriptCallback }}();
            @endisset
        });
    </script>
@endsection
