<?php
$mbox = new MboxCommon();
$u_obj = $mbox->getUserOrDie();
$u_obj->setSetting('trustly_deposit_popup_shown', 1);

$box_id = $_POST['box_id'] ?? 'trustly_deposit_popup';
$amount = $_POST['amount'] ?? 0;

loadCss("/diamondbet/fonts/icons.css");
?>

<style>

    .trustly__container .trustly__body {
        display: flex;
        justify-content: center;
        width: 100%;
        text-align: center;
        box-sizing: border-box;
    }

    .trustly__container .trustly__body__content {
        width: 100%;
        justify-content: center;
        padding: 0 0 20px 0;
        box-sizing: border-box;
    }
    @media only screen and (min-width: 720px) {
        .trustly__container .trustly__body__content {
            width: 72%;
        }
    }

    .trustly__body-image, .trustly__body-description {
        width: 100%;
    }

    .trustly__body-image img {
        width: 100%;
        height: auto;
    }

    .trustly__body-description {
        font-size: 16px;
        font-style: normal;
        font-weight: 400;
        line-height: 21px;
    }

    .trustly__container .trustly__action {
        display: flex;
        flex-direction: column;
    }
    @media only screen and (min-width: 720px) {
        .trustly__container .trustly__action {
            flex-direction: row;
            gap: 10px;
        }
    }

    .trustly__container .lic-mbox-container-flex__button {
        border-radius: 6px;
    }

    .trustly__container .trustly-body-btn {
        margin-top: 10px;
    }

    .trustly-deposit-body-btn-txt {
        font-weight: 700;
        font-size: 16px;
        line-height: 21px;
    }

    /*new css to make space between the element */

    .lic-mbox-wrapper {
        display: flex;
        flex-direction: column;
        flex: 1;
    }

    .lic-mbox-container {
        height: 100%;
    }

    .trustly__container {
        height: 100%;
        display: grid;
    }

    .trustly__action {
        align-self: end;
    }



</style>

<?php if(brandedCss() === 'mrvegas/'): ?>
    <style>
        .positive-action-btn{
            background-color: #3DB553;
        }
    </style>
<?php endif; ?>

<div class="trustly__container">
    <div class="trustly__body">
        <div class="trustly__body__content">
            <div class="trustly__body-image" onclick="depositWithTrustly()">
                <img class="net-deposit-body-img" src="/diamondbet/images/trustly-fast-desktop.png">
            </div>
            <div class="trustly__body-description">
                <div class="description"><?php et('deposit.with.trustly.description')?></div>
            </div>
        </div>
    </div>
    <div class="trustly__action">
        <button class="btn btn-l positive-action-btn good-green lic-mbox-container-flex__button trustly-body-btn">
            <div class="trustly-deposit-body-btn-txt" onclick="depositWithTrustly()"><?php et('trustly.deposit.button') ?></div>
        </button>
        <button class="btn btn-l btn-default-l lic-mbox-container-flex__button trustly-body-btn">
            <div class="trustly-deposit-body-btn-txt" onclick="continueWithPaypal()"><?php et('continue.with.paypal') ?></div>
        </button>
    </div>
</div>

<script>

    function depositWithTrustly() {
        mboxClose('<?php echo $box_id ?>');
        this.cashier.deposit.postTransaction('deposit', null, <?php echo $amount ?>, 'trustly', null, null, {paypal_to_trustly: 1});
    }

    function continueWithPaypal() {
        mboxClose('<?php echo $box_id ?>');
        this.cashier.deposit.postTransaction('deposit', null, <?php echo $amount ?>, 'paypal');
    }

    function closePopup() {
        mboxClose('<?php echo $box_id ?>');
        this.cashier.deposit.postTransaction('deposit', null, <?php echo $amount ?>, 'paypal');
    }
</script>
