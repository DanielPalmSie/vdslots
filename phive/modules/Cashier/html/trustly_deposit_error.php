<?php
$mbox = new MboxCommon();
$u_obj = $mbox->getUserOrDie();

$box_id = $_POST['box_id'] ?? 'trustly_deposit_error';
$supplier = $_POST['supplier'] ?? '';
$reason = $_POST['reason'] ?? '';
$isMobile = phive()->isMobile();

loadCss("/diamondbet/fonts/icons.css");
?>

<style>
    #trustly_deposit_error-box {
        max-width: 100%;
    }
    .trustly__container .failed-icon {
        position: relative;
        padding-left: 5px;
    }

    .circle_icon {
        height: 23px;
        width: 23px;
        position: relative;
        border-radius: 50%;
        border: 3px solid #AE1919;
        margin-right: 5px;
    }

    .circle_icon img {
        height: 100%;
        width: auto;
    }

    .trustly__container .trustly__transaction-status {
        padding-bottom: 10px;
        border-bottom: 2px solid #F1F1F1;
        color: #AE1919;
        display: flex;
        justify-content: center;
        flex-direction: column;
    }
    .trustly__container .status {
        margin: 5px auto;
        padding: 6px 18px;
        background: #f1d3d2;
        border-radius: 8px;
        font-weight: 700;
        font-size: 18px;
        line-height: 21px;
        display: flex;
        align-items: center;
        width: 100%;
        justify-content: center;
        box-sizing: border-box;
    }

    .trustly__container .trustly__reason {
       color: #AE1919;
       font-size: 16px;
       line-height: 18px;
       text-align: justify;
       text-align-last: center;
       padding: 2px 0;
    }

    @media only screen and (min-width: 720px) {
        .trustly__container .status {
            width: auto;
        }
    }
    .trustly__container .trustly__body {
        display: flex;
        align-items: center;
        padding: 20px 0;
        width: 100%;
        gap: 10px;
        flex-direction: column;
        text-align: center;
    }
    @media only screen and (min-width: 720px) {
        .trustly__container .trustly__body {
            flex-direction: row;
            text-align: start;
        }
    }

    .trustly__body-left, .trustly__body-right {
        width: 100%;
    }

    @media only screen and (min-width: 720px) {
        .trustly__body-left, .trustly__body-right {
            width: 50%;
        }
    }

    .trustly__body-left img {
        width: 100%;
        height: auto;
    }

    .trustly__body-right {
        font-size: 16px;
        font-style: normal;
        font-weight: 400;
        line-height: 21px;
    }

    .trustly__body-right .title {
        padding-bottom: 10px;
        font-weight: 700;
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
        grid-auto-rows: auto 1fr auto;
    }

    .trustly__action {
        align-self: end;
    }

</style>

<?php if($isMobile): ?>
    <style>
        .lic-mbox-wrapper {
            padding-bottom: 40px;
            box-sizing: border-box;
        }
    </style>
<?php endif; ?>

<?php if(brandedCss() === 'mrvegas/'): ?>
    <style>
        .positive-action-btn{
            background-color: #3DB553;
        }
    </style>
<?php endif; ?>

<div class="trustly__container">
    <div class="trustly__transaction-status">
        <div class="status">
            <div class="circle_icon">
                <img src="/diamondbet/images/exclamation-solid.svg">
            </div>
            <div class="failed-icon">
                <?php echo $supplier . ": " ?><?php et('mts.transaction_failed.error') ?>
            </div>
        </div>
        <div class="trustly__reason">
            <?php echo $reason; ?>
        </div>
    </div>
    <div class="trustly__body">
        <div class="trustly__body-left">
            <img class="net-deposit-body-img" src="/diamondbet/images/trustly-fast-desktop.png">
        </div>
        <div class="trustly__body-right">
            <div class="title"><?php et('trustly.deposit.problem') ?></div>
            <div class="description"><?php et('trustly.deposit.description') ?></div>
        </div>
    </div>
    <div class="trustly__action">
        <button class="btn btn-l positive-action-btn good-green lic-mbox-container-flex__button trustly-body-btn">
            <div class="trustly-deposit-body-btn-txt" onclick="depositWithTrustly()"><?php et('trustly.deposit.button') ?></div>
        </button>
        <button class="btn btn-l btn-default-l lic-mbox-container-flex__button trustly-body-btn">
            <div class="trustly-deposit-body-btn-txt" onclick="tryAgain()"><?php et('try.again') ?></div>
        </button>
    </div>
</div>

<script>
    function depositWithTrustly() {
        const amount = getCookie('fallback_amount');

        if (amount === '') {
            closePopup('<?= $box_id ?>', false, false);
            theCashier.logoClick('bank', 'trustly');
        } else {
            theCashier.postTransaction('deposit', null, amount, 'trustly');
        }
    }

    function tryAgain() {
        closePopup('<?= $box_id ?>', false, false);
    }

    function getCookie(cname) {
        const name = cname + '=';
        const decodedCookie = decodeURIComponent(document.cookie);
        const cookiesArray = decodedCookie.split(';').map(cookie => cookie.trim());

        for (let cookie of cookiesArray) {
            if (cookie.indexOf(name) === 0) {
                return cookie.substring(name.length);
            }
        }

        return '';
    }

</script>
