<div class="card border-top border-top-3">
    <div class="card-header">
        <h3 class="card-title">Payment Solutions Info</h3>
    </div>

    <form id="payment-information" class="form"
          action="{{ $app['url_generator']->generate('admin.userprofile-payment-update', ['user' => $user->id]) }}"
          method="post">
        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
        <input type="hidden" name="form_id" id="form_id" value="payment-information">
        <div class="card-body">
            <div class="form-group">
                <label for="mb_email">Skrill (MoneyBookers)</label>
                <input type="text" name="mb_email" class="form-control" placeholder="" value="{{ $settings->mb_email }}">
            </div>
            <div class="form-group">
                <label for="net_account">Neteller</label>
                <input type="text" name="net_account" class="form-control" placeholder="" value="{{ $settings->net_account }}">
            </div>
            <div class="form-group">
                <label for="paypal_email">Paypal</label>
                <input type="text" name="paypal_email" class="form-control" placeholder="" value="{{ $settings->paypal_email }}">
            </div>
            <div class="form-group">
                <label for="paypal_payer_id">Paypal Payer id</label>
                <input type="text" name="paypal_payer_id" class="form-control"  value="{{ $settings->paypal_payer_id }}">
            </div>
        @if($settings->btc_address)
            <div class="form-group">
                <label for="btc_address">Bitcoin Address</label>
                <input type="text" name="btc_address" class="form-control" placeholder="" value="{{ $settings->btc_address }}">
            </div>
            @endif
            @if($settings->muchbetter_mobile)
                <div class="form-group">
                    <label for="btc_address">MuchBetter Number</label>
                    <input type="text" name="muchbetter_mobile" class="form-control" placeholder="" value="{{ $settings->muchbetter_mobile }}">
                </div>
            @endif
        </div>

        <div class="card-footer">
                <button id="edit-payment" value="payment_information" type="submit" class="btn btn-info pull-right">Update Payment Solutions Info</button>
        </div>

    </form>
</div>

@section('footer-javascript')
    @parent
    <script type="text/javascript">
        $(document).ready(function() {
            $('#edit-payment').click(function (e) {
                e.preventDefault();
                var dialogTitle;
                var dialogMessage;
                var form = $("#payment-information");
                var email = form[0].paypal_email.value;
                var paypal_payer_id = form[0].paypal_payer_id.value;
                if(dialogMessage = validatePayPalInputs(email,paypal_payer_id)){
                    dialogTitle = 'Payment solutions form error';
                    showValidationErrorInForm(dialogTitle, dialogMessage);

                } else {
                    dialogTitle = 'Edit payment solutions';
                    dialogMessage = 'Are you sure you want to edit the payment solutions user info?';
                    showConfirmInForm(dialogTitle, dialogMessage, form);
                }
            });
        });
    </script>
@endsection
