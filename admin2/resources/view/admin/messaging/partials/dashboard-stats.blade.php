<div class="col-12 col-sm-6 col-md-6 col-lg-3">
    <div class="info-box">
        <span class="info-box-icon bg-info"><i class="fa fa-mobile-alt"></i></span>

        <div class="info-box-content">
            <span class="info-box-text">Total SMS sent</span>
            <span class="info-box-number">{{ number_format($data['total_sms']) }}</span>
        </div>
    </div>
</div>
<div class="col-12 col-sm-6 col-md-6 col-lg-3">
    <div class="info-box">
        <span class="info-box-icon bg-info"><i class="far fa-envelope"></i></span>

        <div class="info-box-content">
            <span class="info-box-text">Total Emails sent</span>
            <span class="info-box-number">{{ number_format($data['total_email']) }}</span>
        </div>
    </div>
</div>