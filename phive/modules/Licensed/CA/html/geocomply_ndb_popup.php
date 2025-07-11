<div class="geocomply_ndb_popup">
    <div class="geocomply_ndb_popup__main-content">
        <div class="geocomply_ndb_popup__logo">
            <div class="geocomply_ndb_popup__logo-icon">
                <img src="/diamondbet/images/geocomply/geocomply_logo.png">
            </div>
        </div>
        <div class="geocomply_ndb_popup__description">
            <h2 class="result__content-text result__content-text-bold"><?= et('geocomply.inform.text.title'); ?></h2>
            <p>
                <?= et('geocomply.inform.text.body'); ?>
            </p>
        </div>
    </div>
    <div class="geocomply_ndb_popup__actions result__page-btn">
        <button class="btn btn-l btn-default-l lic-mbox-container-flex__button geocomply_ndb_popup__popup-btn"
                onclick="licFuncs.onGeoComplyNDBContinue()"><?= et('continue'); ?></button>
    </div>
</div>

