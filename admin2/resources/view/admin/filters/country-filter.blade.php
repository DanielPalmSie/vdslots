<label for="select-country">Country </label>
<select name="country" id="select-country" class="form-control select2-class"
        style="width: 100%;" data-placeholder="Shows all countries if not selected" data-allow-clear="true">
    <option value="all">All</option>

    @foreach(\App\Helpers\DataFormatHelper::getCountryList() as $country)
        <option value="{{ $country['iso'] }}" {{$params['country'] == $country['iso'] ? "selected" : ''}}>{{ $country['printable_name'] }} ({{ $country['iso'] }})</option>
    @endforeach
</select>

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $("#select-country").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('country', 'all') }}").change();
        });
    </script>
@endsection
