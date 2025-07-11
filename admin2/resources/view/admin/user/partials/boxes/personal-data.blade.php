@section('header-css')
    @parent
    <style>
        #personal-data-show-btn,
        .obfuscated > .handler {
            padding: 0 5px;
            font-weight: bold;
            margin-left: 5px;
            box-shadow-color: none;
        }

        #personal-data-show-btn {
            font-size: 14px;
            color: #fff;
        }

        #personal-data-box .box-title-text {
            vertical-align: middle;
        }

        .list-group-item p {
            margin-bottom: 0;
        }

        /*
            This simulates in the Personal data section the original formatting
            from the previous Admin interface
        */
        @media screen and (min-width: 892px) and (max-width: 1450px) {
            #personal-data-box .personal-data__row {
                display: block;
            }

            #personal-data-box .personal-data__column {
                float: left;
                width: 50%;
                max-width: 50%;
                padding-left: 15px;
                padding-right: 15px;
            }
        }

        @media screen and (min-width: 576px) and (max-width: 891px) {
            #personal-data-box .personal-data__row {
                display: block;
            }

            #personal-data-box .personal-data__column {
                float: left;
                width: 100%;
                max-width: 100%;
                padding-left: 15px;
                padding-right: 15px;
            }
        }

        #personal-data-box .personal-data__column b + a,
        #personal-data-box .personal-data__column b + p {
            float: right !important;
        }

        #personal-data-box .personal-data__column .d-flex {
            display: block !important;
        }
        /*
            End of the alignment with the previous Admin version
        */
    </style>
@endsection
<?php /** @var \App\Models\User $user */?>

<div class="card card-outline card-warning @if($personal_data_collapse == 1) collapsed-box @endif" id="personal-data-box">
    <div class="card-header personal-data-headers">
            <div class="personal-data-header-items">
                <h3 class="card-title text-lg text-dark">
                    Personal data
                    @if(!$user->isForgotten() && p('user.personal-details.show.all.button'))
                        <button class="btn btn-danger mb-1" data-boxname="personal-data-box-info" id="personal-data-show-btn"
                                title="Show all personal info">
                            <i class="fa fa-eye"></i>
                        </button>
                    @endif
                    @if(!empty($remote_profiles))
                        <div class="customer-profiles personal-data-header-items">
                            @foreach($remote_profiles as $profile)
                                <div class="mb-1">
                                    <a href="{{ $profile['remote_link'] }}" target="_blank">
                                        <button type="button" class="btn btn-flat btn-primary">
                                            Go to {{$profile['remote_brand_name']}}
                                        </button>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </h3>
            </div>

            <div class="card-tools personal-data-header-items">
                <button class="btn btn-tool" data-boxname="personal-data-box" id="personal-data-box-btn"
                        data-widget="collapse" data-toggle="tooltip" title="Collapse">
                    <i class="fas fa-{{ $personal_data_collapse == 1 ? 'plus' : 'minus' }}"></i>
                </button>
            </div>
    </div>
    <div class="card-body">
        <div class="row personal-data__row">
        <div class="col-12 col-sm-12 col-md-6 col-lg-4 personal-data__column">
            <ul class="list-group list-group-unbordered">
                <li class="list-group-item d-flex justify-content-between pb-2">
                    <b>Username</b>
                    <a href="javascript:void(0)" data-clipboard-text="{{ $user->obfuscated }}" class=" to-clip-placeholder obfuscated" data-key="username">{{ $user->obfuscated }}</a>
                </li>
                <li class="list-group-item d-flex justify-content-between pb-2">
                    <b>User ID</b>
                    <a href="javascript:void(0)" data-clipboard-text="{{ $user->id }}" class="to-clip">{{ $user->id }}</a>
                </li>
                <li class="list-group-item d-flex justify-content-between pb-2">
                    <b>Full name</b>
                    <p class="obfuscated" data-key="full_name">{{ $user->obfuscated }} {{ $user->obfuscated }}</p>
                </li>
                @if (licSetting('show_user_extra_fields_in_admin', $user->id)['firstname_initials'])
                    <li class="list-group-item d-flex justify-content-between pb-2">
                        <b>Initials</b>
                        <p class=" obfuscated" data-key="firstname_initials">{{ $user->obfuscated }}</p>
                    </li>
                @endif
                @if($user->repo->getSetting('fiscal_code'))
                    <li class="list-group-item d-flex justify-content-between pb-2">
                        <b>Fiscal Code</b>
                        <p class=" obfuscated" data-key="fiscal_code">{{ $user->obfuscated }}</p>
                    </li>
                @endif
                <li class="list-group-item d-flex justify-content-between pb-2">
                    <b>Battle Alias</b>
                    <p class=" obfuscated" data-key="alias">{{ $user->obfuscated }}</p>
                </li>
                <li class="list-group-item d-flex justify-content-between pb-2">
                    <b>DOB</b>
                    <p class=" obfuscated" data-key="dob">{{ $user->obfuscated }}</p>
                </li>
                @if (licSetting('show_user_extra_fields_in_admin', $user->id)['birth_place'])
                    <li class="list-group-item d-flex justify-content-between pb-2">
                        <b>Place of birth</b>
                        <p class=" obfuscated" data-key="birth_place">{{ $user->obfuscated }}</p>
                    </li>
                @endif
                <li class="list-group-item d-flex justify-content-between pb-2">
                    <b>Country</b>
                    <p class=" obfuscated" data-key="country">{{ $user->obfuscated }}</p>
                </li>
                <li class="list-group-item d-flex justify-content-between pb-2">
                    <b>Language</b>
                    <p class=" obfuscated" data-key="preferred_lang">{{ $user->obfuscated }}</p>
                </li>
                @if(!empty($user->getSetting('birth_country'))  or licSetting('show_user_extra_fields_in_admin', $user->id)['birth_country'])
                <li class="list-group-item d-flex justify-content-between pb-2">
                    <b>Country of birth</b>
                    <p class=" obfuscated" data-key="birth_country">{{ $user->obfuscated }}</p>
                </li>
                @endif
                @if(!empty($user->getSetting('place_of_birth'))  or licSetting('show_user_extra_fields_in_admin', $user->id)['place_of_birth'])
                    <li class="list-group-item d-flex justify-content-between pb-2">
                        <b>Place of Birth</b>
                        <p class=" obfuscated" data-key="place_of_birth">{{ $user->obfuscated }}</p>
                    </li>
                @endif
                <li class="list-group-item d-flex justify-content-between pb-2">
                    <b>Affiliate</b>

                    @if (!empty($user->affe_id))
                        <a href="/affiliate/account/{{ $user->obfuscated }}/profile" class=" obfuscated" data-key="affiliate_username" data-handler="affiliate_username">{{ $user->obfuscated }}</a>
                    @endif
                </li>
                <li class="list-group-item d-flex justify-content-between pb-2">
                    <b>Sex</b>
                    <p class=" obfuscated" data-key="sex">{{ $user->obfuscated }}</p>
                </li>
                @if($user->repo->hasDobCheck())
                    <li class="list-group-item d-flex justify-content-between pb-2">
                        <b>Ext. KYC Age check</b>
                        <p class=" obfuscated" data-key="ext_kyc_age_check" >{{ $user->obfuscated }}</p>
                    </li>
                @endif
            </ul>
        </div>
        <div class="col-12 col-sm-12 col-md-6 col-lg-4 personal-data__column">
            <ul class="list-group list-group-unbordered">
                @if (licSetting('show_user_extra_fields_in_admin', $user->id)['street_building_info'])
                    <li class="list-group-item d-flex justify-content-between pb-2">
                        <b>Street</b>
                        <p class=" obfuscated" data-key="address_city">{{ $user->obfuscated }}</p>
                    </li>
                    <li class="list-group-item d-flex justify-content-between pb-2">
                        <b>Building</b>
                        <p class=" obfuscated" data-key="building">{{ $user->obfuscated }}</p>
                    </li>
                    @else
                        <li class="list-group-item d-flex justify-content-between pb-2">
                            <b>Address</b>
                            <p class=" obfuscated" data-key="address_city">{{ $user->obfuscated }}</p>
                        </li>
                    @endif
                <li class="list-group-item d-flex justify-content-between pb-2">
                    <b>ZIP Code</b>
                    <p class=" obfuscated" data-key="zipcode">{{ $user->obfuscated }}</p>
                </li>
                @if (licSetting('show_user_extra_fields_in_admin', $user->id)['main_province'])
                <li class="list-group-item d-flex justify-content-between pb-2">
                        <b>Province</b>
                        <p class=" obfuscated" data-key="main_province">{{ $user->obfuscated }}</p>
                    </li>
                @endif
                <li class="list-group-item d-flex justify-content-between pb-2">
                    <b>SignUp</b>
                    <p class=" obfuscated" data-key="register_date">{{ $user->obfuscated }}</p>
                </li>
                @if(p('account.view.email'))
                    <li class="list-group-item d-flex justify-content-between pb-2">
                        <b>Email</b>
                        <p class=" obfuscated" data-key="email">{{ $user->obfuscated }}</p>
                    </li>
                @endif
                @if(p('account.view.cellphone'))
                    <li class="list-group-item d-flex justify-content-between pb-2">
                        <b>Mobile</b>
                        <p class=" obfuscated" data-key="mobile">{{ $user->obfuscated }}</p>
                    </li>
                @endif
                @if (licSetting('show_user_extra_fields_in_admin', $user->id)['citizen_service_number'])
                    <li class="list-group-item d-flex justify-content-between pb-2">
                        <b>Citizen service number</b>
                        <p class=" obfuscated" data-key="citizen_service_number">{{ $user->obfuscated }}</p>
                    </li>
                @endif
                <li class="list-group-item d-flex justify-content-between pb-2">
                    <b>Last Login</b>
                    <p class=" obfuscated" data-key="last_login">{{ $user->obfuscated }}</p>
                </li>
                <li class="list-group-item d-flex justify-content-between pb-2">
                    <b>Last Logout</b>
                    <p class=" obfuscated" data-key="last_logout">{{ $user->obfuscated }}</p>
                </li>
                @if($user->repo->hasPepCheck())
                    <li class="list-group-item d-flex justify-content-between pb-2">
                        <b>Ext. KYC PEP/SL check</b>
                        <p class=" obfuscated" data-key="ext_kyc_pep_check">{{ $user->obfuscated }}</p>
                    </li>
                @endif
                @if($user->hasNid())
                    <li class="list-group-item d-flex justify-content-between pb-2">
                        <b>Personal Number</b>
                        <p class=" obfuscated" data-key="nid">{{ $user->obfuscated }}</p>
                    </li>
                @endif
            </ul>
        </div>
        <div class="col-12 col-sm-12 col-md-6 col-lg-4 personal-data__column">
            <ul class="list-group list-group-unbordered">
                @if(!empty($user->getSetting('id_after_exclusion')))
                    <li class="list-group-item d-flex justify-content-between pb-2">
                        <b>New account after permanent self exclusion</b>
                        <p class=""><a href="{{accLinkAdmin($app, $user->getSetting('id_after_exclusion'))}}">{{$user->getSetting('id_after_exclusion')}}</a></p>
                    </li>
                @elseif(!empty($user->getSetting('id_before_exclusion')))
                    <li class="list-group-item d-flex justify-content-between pb-2">
                        <b>Old account with permanent self exclusion</b>
                        <p class=""><a href="{{accLinkAdmin($app, $user->getSetting('id_before_exclusion'))}}">{{$user->getSetting('id_before_exclusion')}}</a></p>
                    </li>
                @endif
                <li class="list-group-item d-flex justify-content-between pb-2">
                    <b>Allowed to play</b>
                    <p class=" obfuscated" data-key="play_block">{{ $user->obfuscated }}</p>
                </li>
                <li class="list-group-item d-flex justify-content-between pb-2">
                    <b>Registration IP</b>
                    <p class=" obfuscated" data-key="reg_ip">{{ $user->obfuscated }}</p>
                </li>
                <li class="list-group-item d-flex justify-content-between pb-2">
                    <b>Current IP</b>
                    <p class=" obfuscated" data-key="cur_ip">{{ $user->obfuscated }}</p>
                </li>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <b>Block Status</b>
                    </div>
                    <div>
                        <span class="text-red">{{ $user->block_repo->getBlockReasonName() }}</span>

                        @if(!empty($user->block_repo->getPlayDocumentStatus()))
                            <span class="text-red">{{ $user->block_repo->getPlayDocumentStatus() }}</span>
                        @endif

                        @if(!empty($user->block_repo->getBlockReasonName()))
                            {!! $user->block_repo->getBlockReasonDescription() !!}
                        @endif

                        @if($user->block_repo->hasIncomeDocs())
                            <span class="text-red">{{ $user->block_repo->getIncomeDocsStatus() }}</span>
                        @endif

                        @if($user->block_repo->isWithdrawalBlocked())
                            <span class="text-red">{{ $user->block_repo->getWithdrawalBlockedReason() }}</span>
                        @endif
                    </div>
                </li>
                @if(licSetting('intended_gambling', $user->id))
                    <li class="list-group-item d-flex justify-content-between pb-2">
                      <b>Intended Gambling</b>
                         <p class=" obfuscated" data-key="intended_gambling">{{ $user->obfuscated }}</p>
                    </li>
                @endif
                @if(p('user.account.test') && !empty($user->repo->getSetting('test_account')))
                    <li class="list-group-item d-flex justify-content-between pb-2"><span class="text-warning">Test account</span></li>
                @endif
                @if($user->repo->getSetting('temporal_account'))
                    <li class="list-group-item d-flex justify-content-between pb-2"><span class="text-warning">Temporal account</span></li>
                @endif
                @if($user->repo->getSetting('occupation'))
                    <li class="list-group-item d-flex justify-content-between pb-2">
                        <b>Occupation</b>
                        <p class=" obfuscated" data-key="occupation">{{ $user->obfuscated }}</p>
                    </li>
                @endif
                @if(licSetting('show_user_extra_fields_in_admin', $user->id)['industry'])
                    <li class="list-group-item d-flex justify-content-between pb-2">
                        <b>Industry</b>
                        <p class=" obfuscated" data-key="industry"> {{ $user->obfuscated }}</p>
                    </li>
                @endif
                @if($user->repo->getSetting('spending_amount'))
                    <li class="list-group-item d-flex justify-content-between pb-2">
                        <b>Spending Amount</b>
                        <p class=" obfuscated" data-key="spending_amount">{{ $user->obfuscated }}</p>
                    </li>
                @endif
                @if($user->repo->getSetting('current_status'))
                    <li class="list-group-item d-flex justify-content-between pb-2">
                        <b>User status:</b>
                        <p class=" obfuscated" data-key="current_status">{{ $user->obfuscated }}</p>
                    </li>
                @endif
                @if($user->repo->getSetting('nationality') or licSetting('show_user_extra_fields_in_admin', $user->id)['nationality'])
                    <li class="list-group-item d-flex justify-content-between pb-2">
                        <b>Nationality</b>
                        <p class=" obfuscated" data-key="nationality">{{ $user->obfuscated }}</p>
                    </li>
                @endif
                @if($user->repo->getSetting('jurisdiction'))
                    <li class="list-group-item d-flex justify-content-between pb-2">
                        <b>Jurisdiction</b>
                        <p class=" obfuscated" data-key="jurisdiction">{{ $user->obfuscated }}</p>
                    </li>
                @endif
                @if($user->repo->getSetting('residence_country'))
                    <li class="list-group-item d-flex justify-content-between pb-2">
                        <b>Country of Residence</b>
                        <p class=" obfuscated" data-key="residence_country">{{ $user->obfuscated }}</p>
                    </li>
                @endif
                @if($user->repo->getSetting('lastname_second'))
                    <li class="list-group-item d-flex justify-content-between pb-2">
                        <b>Second surname</b>
                        <p class=" obfuscated" data-key="lastname_second">{{ $user->obfuscated }}</p>
                    </li>
                @endif
                @if($user->repo->getSetting('fiscal_region'))
                    <li class="list-group-item d-flex justify-content-between pb-2">
                        <b>Fiscal Region</b>
                        <p class=" obfuscated" data-key="fiscal_region">{{ $user->obfuscated }}</p>
                    </li>
                @endif
                @if($user->repo->getSetting('citizenship'))
                    <li class="list-group-item d-flex justify-content-between pb-2">
                        <b>Citizenship</b>
                        <p class=" obfuscated" data-key="citizenship">{{ $user->obfuscated }}</p>
                    </li>
                @endif
                @if($user->repo->getSetting('company_name'))
                    <li class="list-group-item d-flex justify-content-between pb-2">
                        <b>Company Name</b>
                        <p class=" obfuscated" data-key="company_name">{{ $user->obfuscated }}</p>
                    </li>
                @endif
                @if($user->repo->getSetting('company_address'))
                    <li class="list-group-item d-flex justify-content-between pb-2">
                        <b>Company Address</b>
                        <p class=" obfuscated" data-key="company_address">{{ $user->obfuscated }}</p>
                    </li>
                @endif
                @if($user->repo->getSetting('company_phone_number'))
                    <li class="list-group-item d-flex justify-content-between pb-2">
                        <b>Company Phone Number</b>
                        <p class=" obfuscated" data-key="company_phone_number">{{ $user->obfuscated }}</p>
                    </li>
                @endif
                @if( licSetting('show_document_information_in_admin', $user->id))
                    @if($user->repo->getSetting('doc_type'))
                        <li class="list-group-item d-flex justify-content-between pb-2">
                            <b>Document Type</b>
                            <p class=" obfuscated" data-key="doc_type">{{ $user->obfuscated }}</p>
                        </li>
                    @endif
                    @if($user->repo->getSetting('doc_number'))
                        <li class="list-group-item d-flex justify-content-between pb-2">
                            <b>Document Number</b>
                            <p class=" obfuscated" data-key="doc_number">{{ $user->obfuscated }}</p>
                        </li>
                    @endif
                    @if($user->repo->getSetting('doc_year'))
                        <li class="list-group-item d-flex justify-content-between pb-2">
                            <b>Document Date of Issue</b>
                            <p class=" obfuscated" data-key="doc_date">{{ $user->obfuscated }}</p>
                        </li>
                    @endif
                    @if($user->repo->getSetting('doc_issued_by'))
                        <li class="list-group-item d-flex justify-content-between pb-2">
                            <b>Document Place of Issue</b>
                            <p class=" obfuscated" data-key="doc_issued_by">{{ $user->obfuscated }}</p>
                        </li>
                    @endif
                  @endif
            </ul>
        </div>
    </div>
    </div>
</div>

@include('admin.partials.alert', ['div_id' => 'personal-data-limit-reached', 'modal_title' => 'Alert', 'modal_message' => ''])

@if(!$user->isForgotten())
    <span class="btn btn-danger d-none handler spy-placeholder"><i class="fa fa-eye"></i></span>
@endif

@section('footer-javascript')
    @parent
    <script>
        function setTooltip(btn, message) {
            $(btn).tooltip('hide')
                .attr('data-original-title', message)
                .tooltip('show');
        }

        function hideTooltip(btn) {
            setTimeout(function() {
                $(btn).tooltip('hide');
            }, 1000);
        }

        function initTooltip(el) {
            var uid_selector = $(el);

            uid_selector.tooltip({
                trigger: 'click',
                placement: 'bottom'
            });


            new ClipboardJS(el, {
                text: function(trigger) {
                    return $(trigger).attr('data-clipboard-text');
                }
            }).on('success', function(e) {
                setTooltip(e.trigger, 'Copied!');
                hideTooltip(e.trigger);
            }).on('error', function(e) {
                console.error('Clipboard.js error: ', e);
            });
        }

        $(function () {
            initTooltip('.to-clip');
        });
    </script>
    @if(!$user->isForgotten())
        <script>
            $placeholder = $(".spy-placeholder").clone();
            $placeholder.removeClass('d-none')
                .removeClass('spy-placeholder');
            $("a").click(function (e) {
                if ($(this).hasClass('obfuscated')) {
                    e.preventDefault();
                }
            });

            function handleDataShow ($el, key, value) {
                $el.removeClass('obfuscated');
                if ($el.attr('data-key') === 'affiliate_username' && key === 'affiliate_username') {
                    $el.attr('href', "/affiliate/account/"+value+"/profile");
                }

                $el.text(value);

                if ($el.hasClass('to-clip-placeholder')) {
                    $($el).attr('data-clipboard-text', value);
                    $($el).removeClass('to-clip-placeholder');
                    $($el).addClass('to-clip');
                    initTooltip('.to-clip');
                }
            }

            $(".obfuscated").append($placeholder);
            $(".obfuscated .handler").click(function () {
                $.ajax({
                    method: "post",
                    url: "{{$app['url_generator']->generate('admin.show_personal_details_field')}}",
                    data: {
                        user_id: '{{$user->id}}',
                        field: $(this).parent().attr('data-key')
                    },
                    success: function(data) {

                        /* data: { success: true/false, key: 'the_key', value: 'the real value' } */
                        if (data.success === true) {
                            $(this).parent().attr('data-clipboard-text', data.value);

                            handleDataShow($(this).parent(), data.key, data.value);
                        } else {
                            var modal_alert = $('#personal-data-limit-reached-message');

                            modal_alert.html('<p><b>'+data.msg+'</b></p>');

                            $('#personal-data-limit-reached').modal({
                                show: 'true'
                            }).one('click','.btn-close',function() {
                                $('#personal-data-limit-reached').modal('hide');
                            }).on('hide.bs.modal', function(e){

                            });
                        }
                    }.bind(this)
                });
            });
            $("#personal-data-show-btn").click(function() {
                $.ajax({
                    method: "post",
                    url: "{{$app['url_generator']->generate('admin.show_personal_details_field')}}",
                    data: {
                        user_id: '{{$user->id}}',
                        field: $(".obfuscated .handler").map(function() {
                            return $(this).parent().attr('data-key');
                        }).toArray()
                    },
                    success: function(data) {
                        /* data: { success: true/false, fields: [{field: '', value: ''}] } */
                        if (data.success === true) {
                            data.fields.forEach(function($el) {
                                handleDataShow($("[data-key='"+$el.field+"']"), $el.field, $el.value);
                            });
                            $(this).remove();
                        }else {
                            var modal_alert = $('#personal-data-limit-reached-message');

                            modal_alert.html('<p><b>'+data.msg+'</b></p>');

                            $('#personal-data-limit-reached').modal({
                                show: 'true'
                            }).one('click','.btn-close',function() {
                                $('#personal-data-limit-reached').modal('hide');
                            }).on('hide.bs.modal', function(e){

                            });
                        }
                    }.bind(this)
                });
            });
        </script>
    @endif
@endsection
