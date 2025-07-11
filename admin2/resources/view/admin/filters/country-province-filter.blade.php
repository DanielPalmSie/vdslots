<div class="{{ $containerClass }}">
    @include('admin.filters.country-filter')
</div>

@include('admin.filters.select2-filter', [
    'label' => 'Province',
    'name' => 'province',
    'placeholder' => '    Select a province',
    'dataCurrent' => $app['request_stack']->getCurrentRequest()->get('province'),
    'multiple' => true,
    'additionalScriptCallback' => 'setupCountryAndProvinceSelect2',
    'additionalSelectOptions' => ['All' => 'all'],
    'additionalSelectAttributes' => 'disabled',
    'options' => function() {
        $provinceOptions = \App\Helpers\DataFormatHelper::getProvinces('CA');
        $options = [];
        foreach ($provinceOptions as $iso_code => $main_province) {
            $options[] = [
                'value' => $iso_code,
                'label' => $main_province
            ];
        }
        return $options;
    }
])

@section('footer-javascript')
    @parent
    <script type="text/javascript">
        function setupCountryAndProvinceSelect2() {
            const select_province = $("#select-province");
            const current_province = <?php echo json_encode($app['request_stack']->getCurrentRequest()->get('province')) ?>;

            $("#select-country").change(function () {
                const hasProvinces = $(this).val() === 'CA';
                select_province.prop('disabled', !hasProvinces);
                select_province.val(hasProvinces ? (current_province ? current_province : 'all') : []).select2().trigger('select2:select');
            });

            select_province.on('select2:select', function (e) {
                const selected_provinces = $(this).val();

                if (selected_provinces === null) return;

                if (selected_provinces.includes('all')) {
                    $(this).val(null);
                    const all_province_codes = getOptionValues($("#select-province"), 'all', true);
                    $(this).val(all_province_codes);
                }

                $(this).change();
            });
        }

        function getOptionValues(selectElement, exclusionValue, excludeValue) {
            return selectElement.find('option').map(function() {
                return excludeValue ? ($(this).val() !== exclusionValue ? $(this).val() : null) : $(this).val();
            }).get();
        }

        function handleProvinceSelection() {
            const selectProvince = $("#select-province");
            const selectCountry = $("#select-country");

            if (selectProvince.val() === null && selectCountry.val() === 'CA') {
                $(this).val(null);
                const allProvinceCodes = getOptionValues($("#select-province"), 'all', false);
                selectProvince.val(allProvinceCodes).change();
            }
        }
    </script>
@endsection
