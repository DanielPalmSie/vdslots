<form id="search-form-34" action="{{ $app['url_generator']->generate('user.search') }}" method="post" class="">
    <input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Users filter</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="form-group">
                        <label for="user[id]" class="control-label">User ID</label>
                        <input type="text" name="user[id]" class="form-control" id="user-id" placeholder="User ID, comma-separated for multiple values"
                               value="{{ $app['request_stack']->getCurrentRequest()->get('user')['id'] }}">
                    </div>
                    <div class="form-group">
                        <label for="user[email]">Email</label>
                        <input type="text" name="user[email]" class="form-control" placeholder="Part of email"
                               value="{{ $app['request_stack']->getCurrentRequest()->get('user')['email'] }}">
                    </div>
                    <div class="form-group">
                        <label for="user[username]">Username</label>
                        <input type="text" name="user[username]" class="form-control" placeholder="Part of username"
                               value="{{ $app['request_stack']->getCurrentRequest()->get('user')['username']  }}">
                    </div>
                    <div class="form-group">
                        <label for="user[firstname]">Firstname</label>
                        <input type="text" name="user[firstname]" class="form-control" placeholder="Part of firstname"
                               value="{{ $app['request_stack']->getCurrentRequest()->get('user')['firstname'] }}">
                    </div>
                    <div class="form-group">
                        <label for="user[lastname]">Lastname</label>
                        <input type="text" name="user[lastname]" class="form-control" placeholder="Part of lastname"
                               value="{{ $app['request_stack']->getCurrentRequest()->get('user')['lastname'] }}">
                    </div>
                    <div class="form-group">
                        <label for="user[bonus_code]">Bonus code</label>
                        <input type="text" name="user[bonus_code]" class="form-control" placeholder="Part of bonus code"
                               value="{{ $app['request_stack']->getCurrentRequest()->get('user')['bonus_code'] }}">
                    </div>
                    <div class="form-group">
                        <label for="user[preferred_lang]">Language</label>
                        <input type="text" name="user[preferred_lang]" class="form-control" placeholder="Language (en,sv)"
                               value="{{ $app['request_stack']->getCurrentRequest()->get('user')['preferred_lang'] }}">
                    </div>
                    <div class="form-group">
                        @include('admin.filters.slider-range-filter', [
                            'start' => $app['request_stack']->getCurrentRequest()->get('rg_profile_rating_start', \App\Repositories\RiskProfileRatingRepository::PROFILE_RATING_MIN_TAG),
                            'end' => $app['request_stack']->getCurrentRequest()->get('rg_profile_rating_end', \App\Repositories\RiskProfileRatingRepository::PROFILE_RATING_MAX_TAG),
                            'label' => 'RG Profile Rating',
                            'type' => 'rg',
                        ])
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="form-group">
                        <label for="user[alias]">Alias</label>
                        <input type="text" name="user[alias]" class="form-control" placeholder="Part of alias"
                               value="{{ $app['request_stack']->getCurrentRequest()->get('user')['alias'] }}">
                    </div>
                    <div class="form-group">
                        <label for="select-currency">Currency</label>
                        <select name="user[currency][]" id="select-currency" class="form-control select2-class" multiple="multiple"
                                style="width: 100%;" data-placeholder="Select a currency" data-allow-clear="true">
                            @foreach(\App\Helpers\DataFormatHelper::getCurrencyList() as $currency)
                                <option value="{{ $currency->code }}">{{ $currency->symbol }} {{ $currency->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="select-country">Country</label>
                        <select name="user[country][]" id="select-country" class="form-control select2-class" data-allow-clear="true" multiple="multiple"
                                style="width: 100%;" data-placeholder="Select a country" data-current="{{ $app['request_stack']->getCurrentRequest()->get('user')['country'] }}">
                            @foreach(\App\Helpers\DataFormatHelper::getCountryList() as $country)
                                <option value="{{ $country['iso'] }}">{{ $country['printable_name'] }} ({{ $country['iso'] }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="select-province">Province</label>
                        <select name="province[]" id="select-province" class="form-control select2-class" data-allow-clear="true" multiple="multiple" disabled
                                style="width: 100%;" data-placeholder="Select a province" data-current="{{ $app['request_stack']->getCurrentRequest()->get('province') }}">
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="select-not-country">Not in country</label>
                        <select name="other[not-country]" id="select-not-country" class="form-control select2-class"
                                style="width: 100%;" data-placeholder="Select a country" data-allow-clear="true">
                            <option></option>
                            @foreach(\App\Helpers\DataFormatHelper::getCountryList() as $country)
                                <option value="{{ $country['iso'] }}">{{ $country['printable_name'] }} ({{ $country['iso'] }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="since[register_date]">Registered since</label>
                        <input autocomplete="off" type="text" name="since[register_date]" class="form-control daterange-picker" placeholder="Select a date" value="{{ $app['request_stack']->getCurrentRequest()->get('since')['register_date'] }}">
                    </div>
                    <div class="form-group">
                        <label for="before[register_date]">Registered before</label>
                        <input autocomplete="off" type="text" name="before[register_date]"
                               class="form-control daterange-picker" placeholder="Select a date" value="{{ $app['request_stack']->getCurrentRequest()->get('before')['register_date'] }}">
                    </div>
                    <div class="form-group">
                        <label for="since[last_login]">Active since</label>
                        <input autocomplete="off"  name="since[last_login]"
                               class="form-control daterange-picker" placeholder="Select a date" value="{{ $app['request_stack']->getCurrentRequest()->get('since')['last_login'] }}">
                    </div>
                    <div class="form-group">
                        @include('admin.filters.slider-range-filter', [
                            'start' => $app['request_stack']->getCurrentRequest()->get('aml_profile_rating_start', \App\Repositories\RiskProfileRatingRepository::PROFILE_RATING_MIN_TAG),
                            'end' => $app['request_stack']->getCurrentRequest()->get('aml_profile_rating_end', \App\Repositories\RiskProfileRatingRepository::PROFILE_RATING_MAX_TAG),
                            'label' => 'AML Profile Rating',
                            'type' => 'aml',
                        ])
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="form-group">
                        <label for="deposit[amount]">Deposit amount</label>
                        <input type="text" name="deposit[amount]" class="form-control" placeholder="Amount in cents, 0 if not deposits"
                               value="{{ $app['request_stack']->getCurrentRequest()->get('deposit')['amount'] }}">
                    </div>
                    <div class="form-group">
                        <label for="deposit[since]">Deposit since</label>
                        <input autocomplete="off" type="text" name="deposit[since]"
                               class="form-control daterange-picker" placeholder="Select a date" value="{{ $app['request_stack']->getCurrentRequest()->get('deposit')['since'] }}">
                    </div>
                    <div class="form-group">
                        <label for="withdraw[amount]">Withdraw amount</label>
                        <input type="text" name="withdraw[amount]" class="form-control" placeholder="Amount in cents"
                               value="{{ $app['request_stack']->getCurrentRequest()->get('withdraw')['amount'] }}">
                    </div>
                    <div class="form-group">
                        <label for="withdraw[since]">Withdraw since</label>
                        <input autocomplete="off" type="text" name="withdraw[since]"
                               class="form-control daterange-picker" placeholder="Select a date" value="{{ $app['request_stack']->getCurrentRequest()->get('withdraw')['since'] }}">
                    </div>
                    <div class="form-group">
                        <label for="user[mobile]">Mobile number</label>
                        <input type="text" name="user[mobile]" class="form-control" placeholder="Part of mobile number"
                               value="{{ $app['request_stack']->getCurrentRequest()->get('user')['mobile'] }}">
                    </div>
                    <div class="form-group">
                        <label for="select-verified">Verified</label>
                        <select name="other[verified]" id="select-verified" class="form-control select2-class"
                                style="width: 100%;" data-placeholder="Select an option" data-allow-clear="true">
                            <option></option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="select-newsletter">Wants newsletter</label>
                        <select name="other[newsletter]" id="select-newsletter" class="form-control select2-class"
                                style="width: 100%;" data-placeholder="Select an option" data-allow-clear="true">
                            <option></option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="select-user-blockType">Blocks</label>
                        <select name="blockType[name]" id="select-blockType" class="form-control select2-class" style="width: 100%;" data-placeholder="Select an option" data-allow-clear="true">
                            <option></option>
                            @foreach(\App\Helpers\DataFormatHelper::getBlockTypes() as $blockType=>$value)
                                <option value="{{ $blockType }}">{{ $value }} </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="form-group">
                        <label for="select-phone-calls">Wants phone calls</label>
                        <select name="other[phone_calls]" id="select-phone-calls" class="form-control select2-class"
                                style="width: 100%;" data-placeholder="Select an option" data-allow-clear="true">
                            <option></option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="select-settings-name">Setting name</label>
                        <select name="settings[name]" id="select-settings-name" class="form-control select2-class" data-allow-clear="true"
                                style="width: 100%;" data-placeholder="Select a setting" data-current="{{ $app['request_stack']->getCurrentRequest()->get('settings')['name'] }}">
                            <option></option>
                            @foreach(\App\Repositories\UserSettingsRepository::getSettingsNames() as $settingsName)
                                <option value="{{ $settingsName->setting }}">{{ $settingsName->setting }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="select-settings-comparator">Setting comparator</label>
                        <select name="settings[comparator]" id="select-settings-comparator" class="form-control select2-class"
                                style="width: 100%;" data-placeholder="Select an option" data-allow-clear="true">
                            <option></option>
                            <option value=">">Greater than (>)</option>
                            <option value="<">Lower than (<)</option>
                            <option value="=">Equal (=)</option>
                            <option value="!=">Not equal (!=)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="settings[value]">Setting value</label>
                        <input type="text" name="settings[value]" class="form-control" placeholder="Empty = all with missing chosen setting"
                               value="{{ $app['request_stack']->getCurrentRequest()->get('settings')['value'] }}">
                        <span class="help-block" id="setting-help"></span>
                    </div>
                    <div class="form-group">
                        <label for="select-user-column-name">User column</label>
                        <select name="userColumn[name]" id="select-user-column-name" class="form-control select2-class"
                                style="width: 100%;" data-placeholder="Select a user column name" data-allow-clear="true">
                            <option></option>
                            @foreach($user_table_columns as $table_column)
                                <option value="{{ $table_column }}">{{ $table_column }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="select-user-comparator">User column comparator</label>
                        <select name="userColumn[comparator]" id="select-user-comparator" class="form-control select2-class"
                                style="width: 100%;" data-placeholder="Select an option" data-allow-clear="true">
                            <option></option>
                            <option value=">">Greater than (>)</option>
                            <option value="<">Lower than (<)</option>
                            <option value="=">Equal (=)</option>
                            <option value="!=">Not equal (!=)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="userColumn[value]">User column value</label>
                        <input type="text" name="userColumn[value]" class="form-control" placeholder="E.g. se, Male/Female, stockholm, code, 0/1"
                               value="{{ $app['request_stack']->getCurrentRequest()->get('userColumn')['value'] }}">
                        <span class="help-block" id="column-help"></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white">
            <button id="user-search-btn" class="btn btn-info">Search</button>
        </div>
    </div>
</form>

{{--todo this one is also at main-info.blade.php, will be moved to layout--}}
@section('header-css')
    @parent
    <style>
        body { opacity: 0}
    </style>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script type="text/javascript">
        $(window).on('load', function () {
            $('body').animate({'opacity':'1'},200);
            $('.daterange-picker').daterangepicker({
                singleDatePicker: true,
                showDropdowns: true,
                autoUpdateInput: false,
                locale: {
                    format: 'YYYY-MM-DD'
                }
            });
            $('.daterange-picker').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD'));
            });
        });
        $(document).ready(function() {

            var select_user_column_name = $("#select-user-column-name");
            var select_user_column_comparator = $("#select-user-comparator");
            var select_settings_name = $("#select-settings-name");
            var select_settings_comparator = $("#select-settings-comparator");
            var select_country = $("#select-country");
            var select_province = $("#select-province");
            var block_type = $("#select-blockType");
            var column_input = $("input[name='userColumn[value]']");
            var setting_input = $("input[name='settings[value]']");

            function decodeHtml(html) {
                var txt = document.createElement("textarea");
                txt.innerHTML = html;
                return txt.value;
            }

            select_country.on('change', function () {
                let selected_country_codes = $(this).val();
                if (selected_country_codes === null) {
                    return;
                }

                let is_province_select_disabled = select_province.prop('disabled');

                if (!selected_country_codes.includes('CA') && !is_province_select_disabled) {
                    select_province.val(null).prop('disabled', true).trigger('change');
                    return;
                }

                if (selected_country_codes.includes('CA') && !is_province_select_disabled) {
                    return;
                }

                if (selected_country_codes.includes('CA')) {
                    let provinces = JSON.parse("{{ json_encode(\App\Helpers\DataFormatHelper::getProvinces('CA')) }}".replace(/&quot;/g,'"'));
                    provinces = select_province.find('option[value=ALL]').length === 0 ? {ALL: 'All', ... provinces} : provinces;

                    for (const [code, province] of Object.entries(provinces)) {
                        if (select_province.find('option[value=' + code + ']').length === 0) {
                            let new_option = new Option(province, code, false, false);
                            select_province.append(new_option);
                        }
                    }
                    select_province.val('ALL').prop('disabled', false).trigger('change');
                }
            });

            select_province.on('select2:select', function (e) {
                let selected_provinces = $(this).val();
                if (selected_provinces === null) {
                    return;
                }

                let last_selected_province_code = e.params.data.id;
                if (last_selected_province_code === 'ALL') {
                    $(this).val(null);
                    let all_province_options = select_province.find('option');
                    let all_province_codes = [];
                    all_province_options.each(function () {
                        if ($(this).val() !== 'ALL') {
                            all_province_codes.push($(this).val());
                        }
                    })
                    $(this).val(all_province_codes);
                } else if (selected_provinces.includes('ALL')) {
                    $(this).val(selected_provinces.filter((code) => code !== 'ALL'));
                }

                $(this).change();
            });

            $("#select-currency").select2().val(<?php echo json_encode($app['request_stack']->getCurrentRequest()->get('user')['currency']) ?>).change();
            select_country.select2().val(<?php echo json_encode($app['request_stack']->getCurrentRequest()->get('user')['country']) ?>).change();
            select_province.select2().val(<?php echo json_encode($app['request_stack']->getCurrentRequest()->get('province')) ?>).change();
            $("#select-not-country").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('other')['not-country'] }}").change();
            $("#select-verified").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('other')['verified'] }}").change();
            $("#select-blockType").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('blockType')['name'] }}").change();
            $("#select-newsletter").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('other')['newsletter'] }}").change();
            $("#select-phone-calls").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('other')['phone_calls'] }}").change();
            $("#select-abuser").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('other')['abuser'] }}").change();
            select_settings_name.select2().val("{{ $app['request_stack']->getCurrentRequest()->get('settings')['name'] }}").change();
            select_settings_comparator.select2().val(decodeHtml("{{ $app['request_stack']->getCurrentRequest()->get('settings')['comparator'] }}")).change();
            select_user_column_name.select2().val("{{ $app['request_stack']->getCurrentRequest()->get('userColumn')['name'] }}").change();
            select_user_column_comparator.select2().val(decodeHtml("{{ $app['request_stack']->getCurrentRequest()->get('userColumn')['comparator'] }}")).change();

            $('#user-search-btn').on( 'click', function (e) {
                e.preventDefault();

                let form = $('#search-form-34');

                let sliders_default_value = {
                    'slider-start-profile-rating-rg': "{{ \App\Repositories\RiskProfileRatingRepository::PROFILE_RATING_MIN_TAG }}",
                    'slider-end-profile-rating-rg': "{{ \App\Repositories\RiskProfileRatingRepository::PROFILE_RATING_MAX_TAG }}",
                    'slider-start-profile-rating-aml': "{{ \App\Repositories\RiskProfileRatingRepository::PROFILE_RATING_MIN_TAG }}",
                    'slider-end-profile-rating-aml': "{{ \App\Repositories\RiskProfileRatingRepository::PROFILE_RATING_MAX_TAG }}",
                };

                let has_criteria = $('.form-control').is(function(i, e) {
                    if($(e).is('[name="user-search-list-datatable_length"]')) {
                        return false;
                    }
                    for (const slider_id in sliders_default_value) {
                        if($(e).is('#' + slider_id)) {
                            let default_value = sliders_default_value[slider_id];
                            delete sliders_default_value[slider_id];
                            return e.value != default_value;
                        }
                    }
                    return e.value != '';
                });

                if(has_criteria === false) {
                    return;
                }

                if (column_input.val().length > 0 && (select_user_column_name.val().length == 0 || select_user_column_comparator.val().length == 0)) {
                    $("#column-help").html('You need to fill all the columns related fields.')
                } else if (setting_input.val().length > 0 && (select_settings_name.val().length == 0 || select_settings_comparator.val().length == 0)) {
                    $("#setting-help").html('You need to fill all the settings related fields.')
                } else {
                    form.attr('action', "{{ $app['url_generator']->generate('user.search') }}");
                    form.submit();
                }
            });

            column_input.keyup(function() {
                if (!this.value) {
                    $("#column-help").html('');
                }
            });

            setting_input.keyup(function () {
                if (!this.value) {
                    $("#setting-help").html('');
                }
            });

        });
    </script>
@endsection
