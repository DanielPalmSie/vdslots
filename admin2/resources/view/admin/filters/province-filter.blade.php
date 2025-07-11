<label for="select-$main_province">Provinces</label>
<select multiple data-allow-clear="true" name="provinces[]" id="select-main_province"
        @if ($country != "CA")
            disabled
        @endif
        class="form-control select2-multiple" style="width: 100%;"
        data-placeholder="Select a province" tabindex="-1" aria-hidden="true">
    @if ($country == "CA")
        <option value="all"> All </option>
    @endif
    @foreach(\App\Helpers\DataFormatHelper::getProvinces($country) as $iso_code => $main_province)
        <option value="{{ $iso_code }}"> {{ $main_province }} ({{ $iso_code }})</option>
    @endforeach
</select>

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function () {
            $('#select-main_province').select2();
        });
    </script>
@endsection
