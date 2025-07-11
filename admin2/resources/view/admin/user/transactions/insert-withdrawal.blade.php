<?php
/**
 * @param array $form_data
 */
?>
@extends('admin.layout')

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    <form id="insert-withdrawal-form" method="post"
          action="{{ $app['url_generator']->generate('admin.user-insert-withdrawal', ['user' => $user->id]) }}">
        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
        <div class="row">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card card-info">
                    <div class="card-header">
                        <h5 class="card-title">Create a new withdrawal for: {{ $user->id }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="amount">Amount in cents</label>
                            <div class="input-group">
                                <input type="text" name="amount" class="form-control" placeholder="Amount">
                                <div class="input-group-append">
                                    <span class="input-group-text text-sm"><b>{{ $user->currency }}</b></span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="payment_method">Payment method</label>
                            <select name="payment_method" id="payment_method" class="form-control"
                                    style="width: 100%;" data-placeholder="Select one from the list"
                                    data-allow-clear="true" data-target="#partial_withdrawal_form">
                                <option></option>
                                @foreach(array_keys($form_data) as $method)
                                    <option value="{{ $method }}">{{ ucwords($method) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="partial_withdrawal_form"></div>
                    </div>
                    <div class="card-footer">
                        <button disabled name="insert-withdrawal" class="btn btn-info" type="submit">Please select a payment method</button>
                        @if(p('user.create.withdrawal.no.docs.verified'))
                            <button style="float: right; display: none" name="insert-withdrawal-no-docs" class="btn btn-info" type="submit">Insert without verification</button>
                        @endif
                    </div>
                </div>
                @if($app['env'] === 'dev')
                <div class="card card-info" id="debug-box" style="display: none">
                    <div class="card-header"><h5 class="card-title">Developer mode box</h5></div>
                    <div class="card-body">
                        <button name="insert-withdrawal-test-dev" class="btn btn-sm btn-info" type="submit">Insert as a normal withdrawal without docs verification (Not as a manual)</button>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </form>

@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script>
        $(function () {
            var method_sel = $('#payment_method');
            var form = $('#insert-withdrawal-form');

            method_sel.select2().on('change', function (e) {$.ajax({
                    url: form.attr('action'),
                    type: "POST",
                    data: {render_subform: 1, payment_method: method_sel.val()},
                    success: function (response) {
                        $(method_sel.data('target')).html(response['html']);
                        let provider_list = $('#provider_list');
                        $(provider_list).select2();
                        $('[name="insert-withdrawal"]').prop("disabled", false).html('Insert withdrawal');
                        @if(p('user.create.withdrawal.no.docs.verified'))
                            $('[name="insert-withdrawal-no-docs"]').show();
                        @endif
                        @if($app['env'] === 'dev')
                            $('#debug-box').show();
                        @endif
                        var card_elem = $('#card_id');
                        if ($(card_elem).length) {
                            $(card_elem).select2();
                        }

                        initExtraFields(method_sel.val());
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert('AJAX ERROR');
                    }
                });
            });
        });

        /**
         * Initializes payment method's extra fields handling.
         *
         * @param string $paymentMethod The payment method.
         *
         * @return void
         */
        function initExtraFields(paymentMethod)
        {
            switch (paymentMethod) {
                case 'bank': {
                    initBankExtraFields();

                    return;
                }

                default: return;
            }
        }

        /**
         * Initializes bank's extra fields handling.
         *
         * @return void
         */
        function initBankExtraFields()
        {
            let user_country = '{{ $user->country }}';
            let user_currency = '{{ $user->currency }}';
            let provider_sel = $('#provider_list');
            let scheme_input = $('#scheme-no-space');
            let swift_bic_input = $('#swift_bic-no-space');
            let iban_input = $('#iban-no-space');

            provider_sel.add(scheme_input).add(iban_input).on('change input', function() {
                scheme_input.val(scheme_input.val().toLowerCase());
                if (provider_sel.val() === 'mifinity' && scheme_input.val().replace(/\s/g, '') === 'payanybank') {
                    initPayAnyBankExtraFields(user_country, user_currency, swift_bic_input, iban_input);
                } else {
                    enableFormField(swift_bic_input, false)
                }
            });
        }

        /**
         * Disables, hides and resets input.
         *
         * @param $field The input field to disable.
         *
         * @return void
         */
        function disableFormField(field)
        {
            field.hide();
            field.val('');
            field.attr('disabled', true);
            field.attr('required', false);

            let fieldLabel = $('label[for=' + field.attr('name') + ']');
            fieldLabel.hide();
        }

        /**
         * Enables and shows input. Optionally makes the input required.
         *
         * @param $field The input field to enable.
         * @param bool $required If the input field should be required.
         *
         * @return void
         */
        function enableFormField(field, required = true)
        {
            field.show();
            field.attr('disabled', false);

            if (required === true) {
                field.attr('required', 'required');
            }

            if (required === false) {
                field.attr('required', false);
            }

            let fieldLabel = $('label[for=' + field.attr('name') + ']');
            fieldLabel.show();
        }

        /**
         * Initializes payanybank extra fields.
         *
         * @param string $user_country Player country.
         * @param string $user_currency Player currency.
         * @param $swift_bic_input SWIFT / BIC input field.
         * @param $swift_bic_input IBAN input field.
         *
         * @return void
         */
        function initPayAnyBankExtraFields(user_country, user_currency, swift_bic_input, iban_input)
        {
            let swift_bic_countries = @json($payAnyBankExtraFieldsConfig)['swift_bic']['countries'];
            let ibanCountryCode = iban_input.val().replace(/\s/g, '').slice(0,2).toUpperCase();

            if (swift_bic_countries.includes(user_country) === false) {
                disableFormField(swift_bic_input);
            }

            if (ibanCountryCode === 'GB' && user_currency === 'EUR') {
                enableFormField(swift_bic_input);
            }
        }
    </script>
@endsection

