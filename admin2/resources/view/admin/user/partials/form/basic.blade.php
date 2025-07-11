<div class="card border-top border-top-3">
    <div class="card-header">
        <h3 class="card-title">Basic information</h3>
    </div>
    <!-- /.box-header -->
    <!-- form start -->
    <form id="basic_user_profile_form" class="form"
          action="{{ $app['url_generator']->generate('admin.userprofile-basic-update', ['user' => $user->id]) }}"
          method="post">
         <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
        <div class="card-body">
            @if (p("users.editall"))
                <div class="form-group">
                    <label for="firstname">First name</label>
                    <input type="text" name="firstname" class="form-control" placeholder="First name"
                           value="{{ $user->firstname }}">
                </div>
                <div class="form-group">
                    <label for="lastname">Last name</label>
                    <input type="text" name="lastname" class="form-control" placeholder="Last name"
                           value="{{ $user->lastname }}">
                </div>
                <div class="form-group">
                    <label for="select-sex">Sex</label>
                    <select name="sex" id="select-sex" class="form-control select2-sex"
                            style="width: 100%;" data-placeholder="Select a sex">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="dob">Date of birth</label>
                    <input type="text" name="dob" class="form-control" placeholder="Day of birth"
                           value="{{ $user->dob }}">
                </div>
                <div class="form-group">
                    <label for="select-country">Country</label>
                    <select name="country" id="select-country" class="form-control select2-country"
                            style="width: 100%;" data-placeholder="Select a country">
                        @foreach(\App\Helpers\DataFormatHelper::getCountryList() as $country)
                            <option value="{{ $country['iso'] }}">{{ $country['printable_name'] }}
                                ({{ $country['iso'] }})
                            </option>
                        @endforeach
                    </select>
                </div>

            @if($user->getSetting('birth_country') or licSetting('show_user_extra_fields_in_admin', $user->id)['birth_country'])
                <div class="form-group">
                    <label for="select-birth_country">Country of birth</label>
                    <select name="birth_country" id="select-birth_country"
                            class="form-control select2-birth_country"
                            style="width: 100%;" data-placeholder="Select a Country of birth">
                        @php
                            $birth_country = $user->getSetting('birth_country')
                        @endphp
                        @foreach(\App\Helpers\DataFormatHelper::getCountryList() as $country)
                            <option value="{{ $country['iso'] }}" {{ ($country['iso'] == $birth_country) ? "selected" : "" }}>
                                {{ $country['printable_name'] }}
                                ({{ $country['iso'] }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if($user->getSetting('place_of_birth') or licSetting('show_user_extra_fields_in_admin', $user->id)['place_of_birth'])
                    <div class="form-group">
                        <label for="city">Place of birth</label>
                        <input type="text" class="form-control" name="place_of_birth" placeholder="place_of_birth" value="{{  $user->getSetting('place_of_birth') }}">
                    </div>
                @endif
            @if($user->getSetting('nationality') or licSetting('show_user_extra_fields_in_admin', $user->id)['nationality'])
                <div class="form-group">
                    <label for="select-nationality">Nationality</label>
                    <select name="nationality" id="select-nationality"
                            class="form-control select2-nationality"
                            style="width: 100%;" data-placeholder="Nationality">
                        @php
                            $nationality = $user->getSetting('nationality')
                        @endphp
                        @foreach(\App\Helpers\DataFormatHelper::getCountryList() as $country)
                            <option value="{{ $country['iso'] }}" {{ ($country['iso'] == $nationality) ? "selected" : "" }}>
                                {{ $country['printable_name'] }}
                                ({{ $country['iso'] }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if (licSetting('show_user_extra_fields_in_admin', $user->id)['main_province'])
                    <div class="form-group">
                        <label for="select-$main_province">Province</label>
                        <select name="main_province" id="select-main_province"
                                class="form-control select2-main_province"
                                style="width: 100%;" data-placeholder="Select a province">
                            @php
                                $user_province = $user->getSetting('main_province')
                            @endphp
                            @foreach(\App\Helpers\DataFormatHelper::getProvinces($user->country) as $iso_code => $main_province)
                                <option value="{{ $iso_code }}" {{ ($iso_code == $user_province) ? "selected" : "" }}>
                                    {{ $main_province }} ({{ $iso_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            @endif
            @if (empty(lic('getForcedLanguage', [], cu($user->id))))
                <div class="form-group">
                    <label for="select-lang">Language</label>
                    <select name="preferred_lang" id="select-lang" class="form-control select2-lang" data-val="sv" style="width: 100%;" data-placeholder="Select a language">
                        @foreach(\App\Repositories\UserRepository::getSelectedLanguages() as $language)
                            <option value="{{$language['language']}}">{{$language['title']}}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="form-group">
                <label for="address">Address</label>
                <textarea class="form-control" rows="3" name="address" placeholder="Address">{{ $user->address }}</textarea>
            </div>
            <div class="form-group">
                <label for="city">City</label>
                <input type="text" class="form-control" name="city" placeholder="City" value="{{ $user->city }}">
            </div>
            <div class="form-group">
                <label for="zipcode">Zip Code</label>
                <input type="text" class="form-control" name="zipcode" placeholder="Zip Code"
                       value="{{ $user->zipcode }}">
            </div>
            <div class="form-group">
                <label for="mobile">Mobile</label>
                <input type="text" class="form-control" name="mobile" placeholder="Mobile"
                       value="{{ $user->mobile }}">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="text" class="form-control" name="email" placeholder="Email" value="{{ $user->email }}">
            </div>
            <div class="form-group">
                <label for="alias">Battle alias</label>
                <input type="text" class="form-control" name="alias" placeholder="Battle Alias" value="{{ $user->alias }}">
            </div>
        </div>
        <!-- /.box-body -->
        <div class="card-footer">
            @if(p('change.contact.info'))
                <button type="submit" class="btn btn-info float-right" id="edit-basic">Update user information</button>
            @endif
        </div>
        <!-- /.box-footer -->
    </form>
</div>

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            var user_country = '{{ $user->country }}';

            $(".select2-country").select2().val('{{ $user->country }}').trigger("change");
            $(".select2-birth_country").select2().val('{{ $user->getSetting('birth_country') }}').trigger("change");
            $(".select2-nationality").select2().val('{{ $user->getSetting('nationality') }}').trigger("change");
            $(".select2-lang").select2().val('{{ $user->preferred_lang }}').trigger("change");
            $(".select2-sex").select2().val('{{ $user->sex }}').trigger("change");

            $('#edit-basic').click(function (e) {
                e.preventDefault();
                var dialogTitle = 'Edit basic info';
                var dialogMessage = 'Are you sure you want to edit the user basic info?';
                var form = $("#basic_user_profile_form");
                showConfirmInForm(dialogTitle, dialogMessage, form);
            });
        });
    </script>
@endsection
