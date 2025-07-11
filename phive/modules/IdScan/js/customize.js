const tokenValue = 'FRSV2EJRLs_vfxLbeVCnIfaMPmLt9XsaYf3GgQbjynkOsNOU0ceoDv1Xpz2LP8Hqns1xW_buTdjyKDeNj3o9d7nrbXuGLvI1QP5KM0jTaQ0DIjszYCTYQ4QPfyo5tsZUVvMTPaErWep_8oG_gLM0bvja5e93aaflYPjbrev3YIGowQOuMZcItNqy-hry-nBNjYafkLKtHxLQsdVt8vZWtfVezl5AMHFCprmbS9alQAI76WBaW6QosgPUYJv67mhLGFJ8CoHUSHNiyCkvPX4tUc4__hcKppklzVCgzPHvZScWPS1ZaXwC0DiYL4I2LiNAFdyVB94H1e02jXDwt4uAzf-Gf8noyQCA8jY6rC6vf6QgoqROPyT0DOcSJ-YFPDmthst-uLmDkXxKvGorJcqPecDA1jmkSUnSBhsZ1RNREQSKIBaB2wsuFyUaUc5U-wTRDvkcMQ2UdZHvLdQUtzCepjVWFur3py0MKIMR2voutPjkFmXvAMe3QQFMEE-Zt06YzxenYVYU5yA-aIO2s3N9rckSC9Z2ZzpiflnhPfYaP1bgUH_PaChvOocUGGd_qOmu';

var journeyId;
var journeyPerson;
function onJourneyEventCallBack(event, meta, state) {
	if (state.action !== 'IDENTITY:FRONT') {
		//journeyId and JourneyPerson will come after firt upload
		journeyId = state.journey.journeyId;
		journeyPerson = state.journey.person;
		console.log('journeyId', journeyId);
		console.log('journeyPerson', journeyPerson);
	}
	switch (event) {
		case 'TRANSFER:STARTED':
		case 'JOURNEY:PROGRESS':
		case 'JOURNEY:END':
		case 'TRANSFER:PROGRESS':
		case 'TRANSFER:COMPLETE': {
			if (
				(meta.name === 'upload' && meta.status === 'complete') ||
				(meta.name === 'send' && meta.status === 'complete')
			) {
				//console.log("TRANSFER:COMPLETE");
			}
		}
	}
}

const additionalDataDictionary = [
	{
		name: 'Key1',
		value: 'Value2',
	},
	{
		name: 'Key2',
		value: 'Value2',
	},
];


const templateDictionary = {
	result: {
		type: 'function',
		processor: 'mustache',
		provider: function () {
			return resultTemplate(isLoggedIn, resultStatus, context);
		}
	},
    smartCapture: {
        type: 'function',
        processor: 'mustache',
        provider: function () {
            return smartCaptureTemplate();
        },
    }
};

function noneTemplate() {
	return null;
}
function initializingTemplate() {
	return `
			<h2 class="section-title">{{INITIALIZING_TITLE}}12</h2>
			<p>{{INITIALIZING_DESCRIPTION}}</p>
		`;
}
function cameraTemplate() {
	return `
			<h2 class="section-title">{{PROVIDER_TITLE_CAMERA}}</h2>
			<div class="info-container">
				<p class="info-item journey-state">
					<span class="info-item__name">{{INFO_JOURNEY_STATE}}</span>
					<span data-jcs-element="info__journey__state" class="info-item__value">...</span>
				</p>
				<p class="info-item journey-action">
					<span class="info-item__name">{{INFO_JOURNEY_ACTION}}</span>
					<span data-jcs-element="info__journey__action" class="info-item__value">...</span>
				</p>
				<p class="info-item journey-action-attempt">
					<span class="info-item__name">{{INFO_JOURNEY_ACTION_ATTEMPT}}</span>
					<span data-jcs-element="info__journey__action__attempt" class="info-item__value">...</span>
				</p>
			</div>
			<form class="camera-options--container">
				<p data-jcs-element="camera__status" class="camera-status"></p>
				<select data-jcs-element="camera__select" class="camera-choices"></select>
			</form>
			<div class="camera-viewfinder--container">
				<canvas data-jcs-element="camera__viewfinder" class="camera-viewfinder"></canvas>
			</div>
			<div data-jcs-element="view__status">
				<p data-jcs-element="view__status__message"></p>
			</div>
			<form class="button-container">
				<input data-jcs-element="camera__capture" class="button button--primary" type="button" value="{{CAMERA_CAPTURE}}" />
			</form>
		`;
}

function cropperTemplate() {
	return `
		<h2 class="section-title">{{PROVIDER_TITLE_CROPPER}}</h2>
		<div class="info-container">
			<p class="info-item journey-state">
				<span class="info-item__name">{{INFO_JOURNEY_STATE}}</span>
				<span data-jcs-element="info__journey__state" class="info-item__value">...</span>
			</p>
			<p class="info-item journey-action">
				<span class="info-item__name">{{INFO_JOURNEY_ACTION}}</span>
				<span data-jcs-element="info__journey__action" class="info-item__value">...</span>
			</p>
			<p class="info-item journey-action-attempt">
				<span class="info-item__name">{{INFO_JOURNEY_ACTION_ATTEMPT}}</span>
				<span data-jcs-element="info__journey__action__attempt" class="info-item__value">...</span>
			</p>
		</div>
		<div class="cropper--container">
			<canvas data-jcs-element="cropper__canvas" class="cropper"></canvas>
		</div>
		<form class="button-container">
			<input data-jcs-element="cropper__retry" class="button button--secondary" type="button" value="{{CROPPER_RETRY}}" />
			<input data-jcs-element="cropper__submit" class="button button--primary" type="button" value="{{CROPPER_UPLOAD}}" />
		</form>
		<div data-jcs-element="cropper__status">
			<p data-jcs-element="cropper__status__message"></p>
		</div>
	`;
}

function fileSystemTemplate() {
	return `
			<h2 class="section-title">{{PROVIDER_TITLE_FILESYSTEM}}</h2>
			<div class="info-container">
				<p class="info-item journey-state">
					<span class="info-item__name">{{INFO_JOURNEY_STATE}}</span>
					<span data-jcs-element="info__journey__state" class="info-item__value">...</span>
				</p>
				<p class="info-item journey-action">
					<span class="info-item__name">{{INFO_JOURNEY_ACTION}}</span>
					<span data-jcs-element="info__journey__action" class="info-item__value">...</span>
				</p>
				<p class="info-item journey-action-attempt">
					<span class="info-item__name">{{INFO_JOURNEY_ACTION_ATTEMPT}}</span>
					<span data-jcs-element="info__journey__action__attempt" class="info-item__value">...</span>
				</p>
			</div>
			<form class="file-input--container">
				<input data-jcs-element="file__input" class="file-input" id="jcs__file__input" type="file" />
			</form>
			<label data-jcs-element="file__drop__box" class="file-input--alternatives" for="jcs__file__input">
				<span class="file-input--click">{{FILESYSTEM_SELECT}}</span>
				<span data-jcs-element="file__drop__label" class="file-input--drag">{{FILESYSTEM_DROP_IMAGE}}</span>
			</label>
		`;
}

function gatewayTemplate() {
	return `
		<h2 class="section-title">{{PROVIDER_TITLE_GATEWAY}}</h2>
		<form data-jcs-element="gateway__upload" class="upload-toggle--container">
			<label data-jcs-element="gateway__upload__toggle__label" class="upload-toggle--label">
				<input data-jcs-element="gateway__upload__toggle" class="upload-toggle" type="checkbox" />
				<span class="upload-toggle--text">{{GATEWAY_CAMERA}}</span>
			</label>
			<p data-jcs-element="gateway__camera__status"></p>
		</form>
		<div data-jcs-element="provider__container" class="provider-container"></div>
		<div data-jcs-element="view__status">
			<p data-jcs-element="view__status__message"></p>
		</div>
		<form class="button-container">
			<input data-jcs-element="cancel__journey" class="button button--secondary" type="button" value="{{CANCEL_JOURNEY}}" />
		</form>
	`;
}

function livenessTemplate() {
	return `
		<h2 class="section-title">{{PROVIDER_TITLE_LIVENESS}}</h2>
		<div class="info-container">
			<p class="info-item journey-state">
				<span class="info-item__name">{{INFO_JOURNEY_STATE}}</span>
				<span data-jcs-element="info__journey__state" class="info-item__value">...</span>
			</p>
			<p class="info-item journey-action">
				<span class="info-item__name">{{INFO_JOURNEY_ACTION}}</span>
				<span data-jcs-element="info__journey__action" class="info-item__value">...</span>
			</p>
			<p class="info-item journey-action-attempt">
				<span class="info-item__name">{{INFO_JOURNEY_ACTION_ATTEMPT}}</span>
				<span data-jcs-element="info__journey__action__attempt" class="info-item__value">...</span>
			</p>
		</div>
		<form class="camera-options--container">
			<p data-jcs-element="camera__status" class="camera-status"></p>
			<select data-jcs-element="camera__select" class="camera-choices"></select>
		</form>
		<div data-jcs-element="retina__notifications" class="camera-message--container">
			<span data-jcs-element="retina__message" class="camera-message"></span>
			<span data-jcs-element="retina__timer" class=camera-countdown""></span>
		</div>
		<div class="camera-viewfinder--container" data-fullscreen="false">
			<canvas data-jcs-element="camera__viewfinder" class="camera-viewfinder"></canvas>
		</div>
		<div data-jcs-element="view__status">
			<p data-jcs-element="view__status__message"></p>
		</div>
		<form class="button-container">
			<input data-jcs-element="cancel__journey" class="button button--secondary" type="button" value="{{CANCEL_JOURNEY}}" />
			<input data-jcs-element="retina__start" class="button button--primary" type="button" value="{{LIVENESS_START}}" />
		</form>
	`;
}

function passiveLivenessTemplate() {
	return `
			<h2 class="section-title">{{PROVIDER_TITLE_PASSIVE_LIVENESS}}</h2>
			<div class="info-container">
				<p class="info-item journey-state">
					<span class="info-item__name">{{INFO_JOURNEY_STATE}}</span>
					<span data-jcs-element="info__journey__state" class="info-item__value">...</span>
				</p>
			</div>
			<form class="camera-options--container">
				<p data-jcs-element="camera__status" class="camera-status"></p>
				<select data-jcs-element="camera__select" class="camera-choices"></select>
			</form>
			<div data-jcs-element="passive-liveness__notifications" class="camera-message--container">
				<span data-jcs-element="passive-liveness__message" class="camera-message"></span>
			</div>
			<div class="camera-viewfinder--container" data-fullscreen="false">
				<canvas data-jcs-element="camera__viewfinder" class="camera-viewfinder"></canvas>
				<div data-jcs-element="passive-liveness__loader" class="loader"></div>
			</div>
			<div data-jcs-element="view__status">
				<p data-jcs-element="view__status__message"></p>
			</div>
			<form class="button-container">
				<input data-jcs-element="cancel__journey" class="button button--secondary" type="button" value="{{CANCEL_JOURNEY}}" />
				<input data-jcs-element="camera__capture" class="button button--primary" type="button" value="{{PASSIVE_LIVENESS_START}}" />
			</form>
		`;
}

function loginTemplate() {
	return `
		<h2 class="section-title">{{PROVIDER_TITLE_LOGIN}}</h2>
		<form data-jcs-element="login__form" class="login-form">
			<div class="input--container">
				<span class="input--label">{{LOGIN_USERNAME}}</span>
				<input data-jcs-element="login__username" class="input" id="login_username" type="text" />
			</div>
			<div class="input--container">
				<span class="input--label">{{LOGIN_PASSWORD}}</span>
				<input data-jcs-element="login__password" class="input" id="login_password" type="password" />
			</div>
			<div class="login-error--container">
				<p data-jcs-element="login__errors" class="login-error"></p>
			</div>
			<div class="button-container">
				<input data-jcs-element="login__submit" class="button button--primary" type="submit" value="{{LOGIN_SUBMIT}}" />
			</div>
		</form>
	`;
}

function resultTemplate(isUserLoggedIn, resultStatus, context) {
	console.log(isUserLoggedIn, resultStatus);
	const idscanId = document.getElementById('idscan');
	idscanId.innerHTML = translationDictionary.RESULT_PAGE_TITLE;

    const loaderTemplate = `
    	<div class="blue-circ-loader">
			<div class="sbl-circ" bis_skin_checked="1"></div>
		</div>
    `;

    const failedTemplate = `
        <div class="result__container">
            <img src="/phive/modules/IdScan/assets/images/result-failed.svg">
            <div class="result__content">
                <div class="result__content-text result__content-text-bold">${translationDictionary.VERIFICATION_FAILED}</div>
                <div class="result__content-text">${translationDictionary.VERIFICATION_FAILED_DESCRIPTION}</div>
            </div>
        </div>
        <form class="button-container result__page-btn">
            <input onclick="onClose()" class="button button--secondary" type="button" value="{{CLOSE}}" />
        </form>
    `;

    let successTemplate = `
        <div class="result__container">
            <img src="/phive/modules/IdScan/assets/images/result-success.svg">
            <div class="result__content">
                <div class="result__content-text result__content-text-bold">${translationDictionary.VERIFICATION_SUCCESS}</div>
                <div class="result__content-text">${translationDictionary.VERIFICATION_SUCCESS_DESCRIPTION}</div>
            </div>
        </div>
        <form class="button-container result__page-btn">
            ${isUserLoggedIn
                ? `<input onclick="onContinue()" class="button button--secondary" type="button" value="{{RESULTS_CONTINUE}}" />`
                : ''
            }
        </form>
    `;

    // if IdScan is triggered on contact info change in Edit Profile page - show loader on successful response
    if (context === 'contact-info-change') {
        successTemplate = loaderTemplate;
    }

	return `
		{{^result}}
			${loaderTemplate}
		{{/result}}
		{{#result}}
			${resultStatus === "Failed" ? failedTemplate : successTemplate}
		{{/result}}
	`;
}

function scannerTemplate() {
	return `
		<h2 class="section-title">{{PROVIDER_TITLE_SCANNER}}</h2>
		<div class="info-container">
			<p class="info-item journey-state">
				<span class="info-item__name">{{INFO_JOURNEY_STATE}}</span>
				<span data-jcs-element="info__journey__state" class="info-item__value">...</span>
			</p>
			<p class="info-item journey-action">
				<span class="info-item__name">{{INFO_JOURNEY_ACTION}}</span>
				<span data-jcs-element="info__journey__action" class="info-item__value">...</span>
			</p>
			<p class="info-item journey-action-attempt">
				<span class="info-item__name">{{INFO_JOURNEY_ACTION_ATTEMPT}}</span>
				<span data-jcs-element="info__journey__action__attempt" class="info-item__value">...</span>
			</p>
		</div>
		<div data-jcs-element="scanner__box__title" class="scanner-title">{{SCANNER_TITLE_WAITING}}</div>
		<div data-jcs-element="scanner__box__subtitle" class="scanner-description">{{SCANNER_DESC_WAITING}}</div>
		<form class="button-container">
			<input data-jcs-element="scanner__action" class="button button--primary" type="button" value="" />
		</form>
	`;
}

function viewTemplate() {
	return `
		<h2 class="section-title">{{PROVIDER_TITLE_VIEW}}</h2>
		<div class="info-container">
			<p class="info-item journey-state">
				<span class="info-item__name">{{INFO_JOURNEY_STATE}}</span>
				<span data-jcs-element="info__journey__state" class="info-item__value">...</span>
			</p>
			<p class="info-item journey-action">
				<span class="info-item__name">{{INFO_JOURNEY_ACTION}}</span>
				<span data-jcs-element="info__journey__action" class="info-item__value">...</span>
			</p>
			<p class="info-item journey-action-attempt">
				<span class="info-item__name">{{INFO_JOURNEY_ACTION_ATTEMPT}}</span>
				<span data-jcs-element="info__journey__action__attempt" class="info-item__value">...</span>
			</p>
		</div>
		<div class="image-preview--container">
			<img data-jcs-element="view__preview" class="image-preview">
		</div>
		<form class="button-container">
			<input data-jcs-element="view__retry" class="button button--secondary" type="button" value="{{VIEW_RETRY}}" />
			<input data-jcs-element="view__upload" class="button button--primary" type="button" value="{{VIEW_UPLOAD}}" />
		</form>
		<div data-jcs-element="view__status">
			<p data-jcs-element="view__status__message"></p>
		</div>
	`;
}

function smartCaptureTemplate() {
    return `<div class="smart_capture">
        <div class="dark-inner-provider-container">
            <div>
                <form class="camera-options--container">
                    <select data-jcs-element="camera__select" class="camera-choices camera-choices-half-s camera-choices-full-xs control control--select"></select>
                </form>
                <div class="help-container">
                    <div class="camera-viewfinder--container">
                        <div data-jcs-element="camera__viewfinder__inner__container" class="camera-viewfinder--inner-container" style="position: relative;">
                            <canvas data-jcs-element="camera__viewfinder" class="camera-viewfinder" style="position: relative; left: 0; top: 0; z-index: 0;"></canvas>
                            <div data-jcs-element="animation__viewfinder" class="camera-viewfinder" style="position: absolute; left: 0; top: 0; z-index: 1;"></div>
                            <div data-jcs-element="overlay_viewfinder" class="camera-viewfinder--overlay"></div>
                            <div data-jcs-element="overlay_viewfinder" class="camera-viewfinder--overlay--transparent">
                                <p class="camera-viewfinder--overlay-text">
                                    <button data-jcs-element="camera__capture" class="button button--primary camera-button">{{CAMERA_CAPTURE_START_BUTTON}}</button>
                                </p>
                            </div>
                            <svg class="canvas-overlay" data-jcs-element="canvas__condition__error" width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="24" cy="24" r="22" fill="white" stroke="#D87212" stroke-width="4" stroke-linecap="round" />
                                <path d="M24 14V26M24 34V33.8972" stroke="#D87212" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                    </div>
                    <div class="uploading--container">
                        <span data-jcs-element="camera__condition__uploading" class="camera-condition camera-condition--uploading">{{CAMERA_CONDITION_UPLOADING}}</span>
                    </div>
                    <div data-jcs-element="camera__conditions" class="camera-conditions--container">
                        <div class="camera-conditions--container-list show-desktop">
                            <div data-jcs-element="camera__condition__glare" class="camera-conditions--container-list-item camera-condition--glare">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        fill-rule="evenodd"
                                        clip-rule="evenodd"
                                        d="M6.65385 0C6.84323 0 7.01636 0.107001 7.10106 0.276393L13.2549 12.5841C13.3324 12.7391 13.3241 12.9231 13.233 13.0706C13.1419 13.218 12.981 13.3077 12.8077 13.3077H0.500001C0.326712 13.3077 0.165778 13.218 0.0746751 13.0706C-0.0164282 12.9231 -0.0247102 12.7391 0.0527869 12.5841L6.20663 0.276393C6.29133 0.107001 6.46446 0 6.65385 0ZM11.9987 12.3077L6.65385 1.61803L1.30902 12.3077H11.9987ZM6.65403 4.92308C6.93018 4.92308 7.15403 5.14693 7.15403 5.42308V7.88461C7.15403 8.16076 6.93018 8.38461 6.65403 8.38461C6.37789 8.38461 6.15403 8.16076 6.15403 7.88461V5.42308C6.15403 5.14693 6.37789 4.92308 6.65403 4.92308ZM6.65381 10.9615C6.99368 10.9615 7.26919 10.686 7.26919 10.3462C7.26919 10.0063 6.99368 9.73077 6.65381 9.73077C6.31394 9.73077 6.03842 10.0063 6.03842 10.3462C6.03842 10.686 6.31394 10.9615 6.65381 10.9615Z"
                                        fill="#F88618"
                                    />
                                </svg>
                                <span>
                                    {{CAMERA_CONDITION_GLARE}}
                                </span>
                            </div>
                            <div data-jcs-element="camera__condition__blur" class="camera-conditions--container-list-item camera-condition--blur">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        fill-rule="evenodd"
                                        clip-rule="evenodd"
                                        d="M6.65385 0C6.84323 0 7.01636 0.107001 7.10106 0.276393L13.2549 12.5841C13.3324 12.7391 13.3241 12.9231 13.233 13.0706C13.1419 13.218 12.981 13.3077 12.8077 13.3077H0.500001C0.326712 13.3077 0.165778 13.218 0.0746751 13.0706C-0.0164282 12.9231 -0.0247102 12.7391 0.0527869 12.5841L6.20663 0.276393C6.29133 0.107001 6.46446 0 6.65385 0ZM11.9987 12.3077L6.65385 1.61803L1.30902 12.3077H11.9987ZM6.65403 4.92308C6.93018 4.92308 7.15403 5.14693 7.15403 5.42308V7.88461C7.15403 8.16076 6.93018 8.38461 6.65403 8.38461C6.37789 8.38461 6.15403 8.16076 6.15403 7.88461V5.42308C6.15403 5.14693 6.37789 4.92308 6.65403 4.92308ZM6.65381 10.9615C6.99368 10.9615 7.26919 10.686 7.26919 10.3462C7.26919 10.0063 6.99368 9.73077 6.65381 9.73077C6.31394 9.73077 6.03842 10.0063 6.03842 10.3462C6.03842 10.686 6.31394 10.9615 6.65381 10.9615Z"
                                        fill="#F88618"
                                    />
                                </svg>
                                <span>
                                    {{CAMERA_CONDITION_BLUR}}
                                </span>
                            </div>
                            <div data-jcs-element="camera__condition__low__resolution" class="camera-conditions--container-list-item camera-condition--low-resolution">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        fill-rule="evenodd"
                                        clip-rule="evenodd"
                                        d="M6.65385 0C6.84323 0 7.01636 0.107001 7.10106 0.276393L13.2549 12.5841C13.3324 12.7391 13.3241 12.9231 13.233 13.0706C13.1419 13.218 12.981 13.3077 12.8077 13.3077H0.500001C0.326712 13.3077 0.165778 13.218 0.0746751 13.0706C-0.0164282 12.9231 -0.0247102 12.7391 0.0527869 12.5841L6.20663 0.276393C6.29133 0.107001 6.46446 0 6.65385 0ZM11.9987 12.3077L6.65385 1.61803L1.30902 12.3077H11.9987ZM6.65403 4.92308C6.93018 4.92308 7.15403 5.14693 7.15403 5.42308V7.88461C7.15403 8.16076 6.93018 8.38461 6.65403 8.38461C6.37789 8.38461 6.15403 8.16076 6.15403 7.88461V5.42308C6.15403 5.14693 6.37789 4.92308 6.65403 4.92308ZM6.65381 10.9615C6.99368 10.9615 7.26919 10.686 7.26919 10.3462C7.26919 10.0063 6.99368 9.73077 6.65381 9.73077C6.31394 9.73077 6.03842 10.0063 6.03842 10.3462C6.03842 10.686 6.31394 10.9615 6.65381 10.9615Z"
                                        fill="#F88618"
                                    />
                                </svg>
                                <span>
                                    {{CAMERA_CONDITION_LOW_RESOLUTION}}
                                </span>
                            </div>
                            <div data-jcs-element="camera__condition__alignment" class="camera-conditions--container-list-item camera-condition--alignment">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        fill-rule="evenodd"
                                        clip-rule="evenodd"
                                        d="M6.65385 0C6.84323 0 7.01636 0.107001 7.10106 0.276393L13.2549 12.5841C13.3324 12.7391 13.3241 12.9231 13.233 13.0706C13.1419 13.218 12.981 13.3077 12.8077 13.3077H0.500001C0.326712 13.3077 0.165778 13.218 0.0746751 13.0706C-0.0164282 12.9231 -0.0247102 12.7391 0.0527869 12.5841L6.20663 0.276393C6.29133 0.107001 6.46446 0 6.65385 0ZM11.9987 12.3077L6.65385 1.61803L1.30902 12.3077H11.9987ZM6.65403 4.92308C6.93018 4.92308 7.15403 5.14693 7.15403 5.42308V7.88461C7.15403 8.16076 6.93018 8.38461 6.65403 8.38461C6.37789 8.38461 6.15403 8.16076 6.15403 7.88461V5.42308C6.15403 5.14693 6.37789 4.92308 6.65403 4.92308ZM6.65381 10.9615C6.99368 10.9615 7.26919 10.686 7.26919 10.3462C7.26919 10.0063 6.99368 9.73077 6.65381 9.73077C6.31394 9.73077 6.03842 10.0063 6.03842 10.3462C6.03842 10.686 6.31394 10.9615 6.65381 10.9615Z"
                                        fill="#F88618"
                                    />
                                </svg>
                                <span>
                                    {{CAMERA_CONDITION_ALIGNMENT}}
                                </span>
                            </div>
                        </div>
                        <div class="camera-conditions--container-detail" data-jcs-element="camera__condition__detail">
                            <div class="camera-conditions--container-detail-animation" data-jcs-element="camera__condition__detail__animation"></div>
                            <div class="camera-conditions--container-detail-description">
                                <div class="camera-conditions--container-detail-description-title">
                                    <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            fill-rule="evenodd"
                                            clip-rule="evenodd"
                                            d="M11 0C11.3788 0 11.725 0.214002 11.8944 0.552786L21.8944 20.5528C22.0494 20.8628 22.0329 21.2309 21.8507 21.5257C21.6684 21.8205 21.3466 22 21 22H1C0.653423 22 0.331557 21.8205 0.14935 21.5257C-0.0328564 21.2309 -0.0494204 20.8628 0.105574 20.5528L10.1056 0.552786C10.275 0.214002 10.6212 0 11 0ZM19.382 20L11 3.23607L2.61804 20H19.382ZM11 8C11.5523 8 12 8.44771 12 9V13C12 13.5523 11.5523 14 11 14C10.4477 14 10 13.5523 10 13V9C10 8.44771 10.4477 8 11 8ZM11 18C11.5523 18 12 17.5523 12 17C12 16.4477 11.5523 16 11 16C10.4477 16 10 16.4477 10 17C10 17.5523 10.4477 18 11 18Z"
                                            fill="#F88618"
                                        />
                                    </svg>
                                    <span class="camera-conditions--container-detail-description-title-text" data-jcs-element="camera__condition__detail__description__title"> </span>
                                </div>
                                <div class="camera-conditions--container-detail-description-hint" data-jcs-element="camera__condition__detail__description__hint"></div>
                            </div>
                        </div>
                    </div>
                    <div data-jcs-element="info__journey__action__container" class="camera-capture--caption">
                        <p data-jcs-element="info__journey__action__text"></p>
                    </div>
                    <div></div>
                    <form class="button-camera-container button-container" data-jcs-element="camera__capture__hint">
                        <p class="camera-capture--caption">
                            {{CAMERA_CAPTURE_CAPTION}}
                        </p>
                    </form>
                    <div class="help-inner-container" data-jcs-element="capture__help__container">
                        <h2>{{HELP_MODAL_AUTOCAPTURE_TITLE}}</h2>
                        <p>{{HELP_MODAL_AUTOCAPTURE_SUBTITLE}}</p>
                        <b>{{HELP_MODAL_AUTOCAPTURE_TIPS}}</b>
                        <div class="help-images-container">
                            <div class="help-image-inner-container">
                                <div class="help-image" data-jcs-element="modal__glare__animation__container"></div>
                                <div class="help-text">
                                    <b>{{HELP_MODAL_AUTOCAPTURE_GLARE_TITLE}}</b>
                                    <p>{{HELP_MODAL_AUTOCAPTURE_GLARE_SUBTITLE}}</p>
                                </div>
                            </div>
                            <div class="help-image-inner-container">
                                <div class="help-image" data-jcs-element="modal__low__res__animation__container"></div>
                                <div class="help-text">
                                    <b>{{HELP_MODAL_AUTOCAPTURE_FAR_TITLE}}</b>
                                    <p>{{HELP_MODAL_AUTOCAPTURE_FAR_SUBTITLE}}</p>
                                </div>
                            </div>
                            <div class="help-image-inner-container">
                                <div class="help-image" data-jcs-element="modal__blur__animation__container"></div>
                                <div class="help-text">
                                    <b>{{HELP_MODAL_AUTOCAPTURE_BLUR_TITLE}}</b>
                                    <p>{{HELP_MODAL_AUTOCAPTURE_BLUR_SUBTITLE}}</p>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer modal-bottom modal-actions-container">
                            <p data-jcs-element="capture__modal__manual__capture__text">{{HELP_MODAL_AUTOCAPTURE_MANUAL_CAPTURE}}</p>
                            <div class="actions-container">
                                <div class="help-button-container" data-jcs-element="capture__modal__camera__manual__capture">
                                    <label class="control control--switch manual--mode--switch" aria-label="switch">
                                        <input type="checkbox" data-jcs-element="capture__modal__camera__manual__capture__check" />
                                        <div class="control__switch control__switch__blue">
                                            <div class="icon ico-checkmark-16 control__switchIcon text-black">
                                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon ico-checkmark-16 control__switchIcon text-black">
                                                    <path d="M13 4L6 12L3 9" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                </svg>
                                            </div>
                                        </div>
                                    </label>
                                    <p class="action-caption">{{HELP_MODAL_AUTOCAPTURE_TURN_MANUAL_CAPTURE}}</p>
                                </div>
                                <div class="spacer"></div>
                                <div class="help-button-container" data-jcs-element="smart__capture__modal__close__help">
                                    <svg width="24" height="24" class="help-button-modal-close" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="12" cy="12" r="11" stroke-width="2" stroke-linecap="round" />
                                        <path d="M16 16L8 8M8 16L16 8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <p class="action-caption">{{REQUIRED_ACTION_Close}}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="notification notification-blue" data-jcs-element="attempt__count__container">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path
                                fill-rule="evenodd"
                                clip-rule="evenodd"
                                d="M0.25 8C0.25 3.71979 3.71979 0.25 8 0.25H8.00081C12.2787 0.254627 15.7454 3.72135 15.75 7.99919L15.75 8C15.75 12.2802 12.2802 15.75 8 15.75C3.71979 15.75 0.25 12.2802 0.25 8ZM7.99958 1.75C4.54799 1.75023 1.75 4.54836 1.75 8C1.75 11.4518 4.54822 14.25 8 14.25C11.4515 14.25 14.2496 11.4522 14.25 8.00081C14.2463 4.55026 11.4501 1.75394 7.99958 1.75ZM8 5C7.44772 5 7 4.55228 7 4C7 3.44772 7.44772 3 8 3C8.55229 3 9 3.44772 9 4C9 4.55228 8.55229 5 8 5ZM7 6.25C6.58579 6.25 6.25 6.58579 6.25 7C6.25 7.41421 6.58579 7.75 7 7.75H7.25V11.25H6.5C6.08579 11.25 5.75 11.5858 5.75 12C5.75 12.4142 6.08579 12.75 6.5 12.75H8H9.5C9.91421 12.75 10.25 12.4142 10.25 12C10.25 11.5858 9.91421 11.25 9.5 11.25H8.75V7C8.75 6.58579 8.41421 6.25 8 6.25H7Z"
                                fill="#4A88C6"
                            />
                        </svg>
                        <p>{{PREVIEW_ATTEMPT_NUMBER_START}}<span data-jcs-element="attempt__counter"></span>{{PREVIEW_ATTEMPT_NUMBER_END}}</p>
                    </div>
                </div>
                <div data-jcs-element="view__status">
                    <p data-jcs-element="view__status__message"></p>
                </div>
                <div class="actions-container">
                    <div data-jcs-element="camera__manual__capture">
                        <label class="control control--switch manual--mode--switch" aria-label="switch">
                            <input type="checkbox" data-jcs-element="camera__manual__capture__check" />
                            <div class="control__switch">
                                <div class="icon ico-checkmark-16 control__switchIcon text-black">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon ico-checkmark-16 control__switchIcon text-black">
                                        <path d="M13 4L6 12L3 9" stroke="rgb(0, 13, 26)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                </div>
                            </div>
                        </label>
                        <p class="action-caption">{{HELP_MODAL_AUTOCAPTURE_TURN_MANUAL_CAPTURE}}</p>
                    </div>
                    <div class="spacer"></div>
                    <div>
                        <div class="help-button-container" data-jcs-element="smart__capture__close__help">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="11" stroke="white" stroke-width="2" stroke-linecap="round" />
                                <path d="M16 16L8 8M8 16L16 8" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <p class="action-caption">{{REQUIRED_ACTION_Close}}</p>
                        </div>
                        <div class="help-button-container" data-jcs-element="smart__capture__open__help">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    fill-rule="evenodd"
                                    clip-rule="evenodd"
                                    d="M12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2ZM0 12C0 5.37258 5.37258 0 12 0C18.6274 0 24 5.37258 24 12C24 18.6274 18.6274 24 12 24C5.37258 24 0 18.6274 0 12ZM12 7C11.0445 7 10 7.68564 10 9C10 9.55228 9.55229 10 9 10C8.44771 10 8 9.55228 8 9C8 6.31436 10.227 5 12 5C14.2091 5 16 6.79086 16 9C16 10.8638 14.7252 12.4299 13 12.874V12.8746V12.8787V12.8827V12.8868V12.8909V12.895V12.8991V12.9031V12.9072V12.9113V12.9153V12.9194V12.9234V12.9275V12.9315V12.9355V12.9396V12.9436V12.9476V12.9517V12.9557V12.9597V12.9637V12.9677V12.9717V12.9757V12.9796V12.9836V12.9876V12.9915V12.9955V12.9995V13.0034V13.0073V13.0113V13.0152V13.0191V13.023V13.0269V13.0308V13.0347V13.0386V13.0425V13.0463V13.0502V13.0541V13.0579V13.0617V13.0656V13.0694V13.0732V13.077V13.0808V13.0846V13.0884V13.0922V13.0959V13.0997V13.1034V13.1071V13.1109V13.1146V13.1183V13.122V13.1257V13.1294V13.133V13.1367V13.1403V13.144V13.1476V13.1512V13.1548V13.1584V13.162V13.1656V13.1691V13.1727V13.1762V13.1797V13.1832V13.1868V13.1902V13.1937V13.1972V13.2007V13.2041V13.2075V13.211V13.2144V13.2178V13.2211V13.2245V13.2279V13.2312V13.2345V13.2379V13.2412V13.2445V13.2477V13.251V13.2542V13.2575V13.2607V13.2639V13.2671V13.2703V13.2735V13.2766V13.2797V13.2829V13.286V13.2891V13.2921V13.2952V13.2983V13.3013V13.3043V13.3073V13.3103V13.3133V13.3162V13.3191V13.3221V13.325V13.3279V13.3307V13.3336V13.3364V13.3392V13.342V13.3448V13.3476V13.3504V13.3531V13.3558V13.3585V13.3612V13.3639V13.3665V13.3691V13.3718V13.3744V13.3769V13.3795V13.382V13.3845V13.387V13.3895V13.392V13.3944V13.3969V13.3993V13.4017V13.404V13.4064V13.4087V13.411V13.4133V13.4156V13.4178V13.42V13.4223V13.4244V13.4266V13.4288V13.4309V13.433V13.4351V13.4371V13.4392V13.4412V13.4432V13.4452V13.4471V13.4491V13.451V13.4529V13.4547V13.4566V13.4584V13.4602V13.462V13.4637V13.4655V13.4672V13.4689V13.4705V13.4722V13.4738V13.4754V13.477V13.4785V13.48V13.4815V13.483V13.4845V13.4859V13.4873V13.4887V13.49V13.4914V13.4927V13.494V13.4952V13.4965V13.4977V13.4988V13.5C13 14.0523 12.5523 14.5 12 14.5C11.4477 14.5 11 14.0523 11 13.5V13.4988V13.4977V13.4965V13.4952V13.494V13.4927V13.4914V13.49V13.4887V13.4873V13.4859V13.4845V13.483V13.4815V13.48V13.4785V13.477V13.4754V13.4738V13.4722V13.4705V13.4689V13.4672V13.4655V13.4637V13.462V13.4602V13.4584V13.4566V13.4547V13.4529V13.451V13.4491V13.4471V13.4452V13.4432V13.4412V13.4392V13.4371V13.4351V13.433V13.4309V13.4288V13.4266V13.4244V13.4223V13.42V13.4178V13.4156V13.4133V13.411V13.4087V13.4064V13.404V13.4017V13.3993V13.3969V13.3944V13.392V13.3895V13.387V13.3845V13.382V13.3795V13.3769V13.3744V13.3718V13.3691V13.3665V13.3639V13.3612V13.3585V13.3558V13.3531V13.3504V13.3476V13.3448V13.342V13.3392V13.3364V13.3336V13.3307V13.3279V13.325V13.3221V13.3191V13.3162V13.3133V13.3103V13.3073V13.3043V13.3013V13.2983V13.2952V13.2921V13.2891V13.286V13.2829V13.2797V13.2766V13.2735V13.2703V13.2671V13.2639V13.2607V13.2575V13.2542V13.251V13.2477V13.2445V13.2412V13.2379V13.2345V13.2312V13.2279V13.2245V13.2211V13.2178V13.2144V13.211V13.2075V13.2041V13.2007V13.1972V13.1937V13.1902V13.1868V13.1832V13.1797V13.1762V13.1727V13.1691V13.1656V13.162V13.1584V13.1548V13.1512V13.1476V13.144V13.1403V13.1367V13.133V13.1294V13.1257V13.122V13.1183V13.1146V13.1109V13.1071V13.1034V13.0997V13.0959V13.0922V13.0884V13.0846V13.0808V13.077V13.0732V13.0694V13.0656V13.0617V13.0579V13.0541V13.0502V13.0463V13.0425V13.0386V13.0347V13.0308V13.0269V13.023V13.0191V13.0152V13.0113V13.0073V13.0034V12.9995V12.9955V12.9915V12.9876V12.9836V12.9796V12.9757V12.9717V12.9677V12.9637V12.9597V12.9557V12.9517V12.9476V12.9436V12.9396V12.9355V12.9315V12.9275V12.9234V12.9194V12.9153V12.9113V12.9072V12.9031V12.8991V12.895V12.8909V12.8868V12.8827V12.8787V12.8746V12.8705V12.8664V12.8623V12.8582V12.8541V12.85V12.8459V12.8418V12.8376V12.8335V12.8294V12.8253V12.8212V12.8171V12.8129V12.8088V12.8047V12.8006V12.7964V12.7923V12.7882V12.784V12.7799V12.7758V12.7716V12.7675V12.7634V12.7592V12.7551V12.751V12.7469V12.7427V12.7386V12.7345V12.7303V12.7262V12.7221V12.718V12.7138V12.7097V12.7056V12.7015V12.6973V12.6932V12.6891V12.685V12.6809V12.6768V12.6727V12.6686V12.6645V12.6604V12.6563V12.6522V12.6481V12.644V12.6399V12.6358V12.6317V12.6277V12.6236V12.6195V12.6155V12.6114V12.6073V12.6033V12.5992V12.5952V12.5912V12.5871V12.5831V12.5791V12.575V12.571V12.567V12.563V12.559V12.555V12.551V12.547V12.5431V12.5391V12.5351V12.5312V12.5272V12.5233V12.5193V12.5154V12.5114V12.5075V12.5036V12.4997V12.4958V12.4919V12.488V12.4841V12.4803V12.4764V12.4725V12.4687V12.4648V12.461V12.4572V12.4534V12.4496V12.4458V12.442V12.4382V12.4344V12.4306V12.4269V12.4231V12.4194V12.4157V12.4119V12.4082V12.4045V12.4008V12.3971V12.3935V12.3898V12.3862V12.3825V12.3789V12.3753V12.3716V12.368V12.3645V12.3609V12.3573V12.3538V12.3502V12.3467V12.3432V12.3396V12.3361V12.3327V12.3292V12.3257V12.3223V12.3188V12.3154V12.312V12.3086V12.3052V12.3018V12.2984V12.2951V12.2917V12.2884V12.2851V12.2818V12.2785V12.2753V12.272V12.2687V12.2655V12.2623V12.2591V12.2559V12.2527V12.2496V12.2464V12.2433V12.2402V12.2371V12.234V12.2309V12.2279V12.2248V12.2218V12.2188V12.2158V12.2128V12.2099V12.2069V12.204V12.2011V12.1982V12.1953V12.1924V12.1896V12.1867V12.1839V12.1811V12.1784V12.1756V12.1728V12.1701V12.1674V12.1647V12.162V12.1594V12.1567V12.1541V12.1515V12.1489V12.1463V12.1438V12.1413V12.1388V12.1363V12.1338V12.1313V12.1289V12.1265V12.1241V12.1217V12.1193V12.117V12.1147V12.1124V12.1101V12.1078V12.1056V12.1034V12.1012V12.099V12.0968V12.0947V12.0926V12.0905V12.0884V12.0864V12.0843V12.0823V12.0803V12.0783V12.0764V12.0745V12.0726V12.0707V12.0688V12.067V12.0652V12.0634V12.0616V12.0599V12.0581V12.0564V12.0548V12.0531V12.0515V12.0499V12.0483V12.0467V12.0452V12.0437V12.0422V12.0407V12.0393V12.0378V12.0365V12.0351V12.0337V12.0324V12.0311V12.0298V12.0286V12.0274V12.0262V12.025V12.0239V12.0227V12.0216V12.0206V12.0195V12.0185V12.0175V12.0166V12.0156V12.0147V12.0138V12.013V12.0121V12.0113V12.0105V12.0098V12.0091V12.0084V12.0077V12.007V12.0064V12.0058V12.0053V12.0047V12.0042V12.0037V12.0033V12.0029V12.0025V12.0021V12.0018V12.0015V12.0012V12.0009V12.0007V12.0005V12.0004V12.0002V12.0001V12.0001V12L12 12H11C11 11.4477 11.4477 11 12 11C13.1046 11 14 10.1046 14 9C14 7.89543 13.1046 7 12 7ZM12 18C12.5523 18 13 17.5523 13 17C13 16.4477 12.5523 16 12 16C11.4477 16 11 16.4477 11 17C11 17.5523 11.4477 18 12 18Z"
                                    fill="white"
                                />
                            </svg>
                            <p class="action-caption">{{HELP_MODAL_NEED_HELP}}</p>
                        </div>
                    </div>
                </div>
            </div>
             ${`<div id="idscan__information">
                <div class="horizontal-separator"></div>
                <div class="content">${translationDictionary.INSTRUCTIONS}</div>
             </div>` }
        </div>
    </div>
`;
}
function smartCaptureOldTemplate() {
	return `
		<h2 class="section-title">{{PROVIDER_TITLE_SMART_CAPTURE}}</h2>
		<div class="info-container">
			<p class="info-item journey-state">
				<span class="info-item__name">{{INFO_JOURNEY_STATE}}</span>
				<span data-jcs-element="info__journey__state" class="info-item__value">...</span>
			</p>
			<p class="info-item journey-action">
				<span class="info-item__name">{{INFO_JOURNEY_ACTION}}</span>
				<span data-jcs-element="info__journey__action" class="info-item__value">...</span>
			</p>
			<p class="info-item journey-action-attempt">
				<span class="info-item__name">{{INFO_JOURNEY_ACTION_ATTEMPT}}</span>
				<span data-jcs-element="info__journey__action__attempt" class="info-item__value">...</span>
			</p>
		</div>
		<form class="camera-options--container">
			<p data-jcs-element="camera__status" class="camera-status"></p>
			<select data-jcs-element="camera__select" class="camera-choices"></select>
		</form>
		<div class="camera-viewfinder--container">
			<canvas data-jcs-element="camera__viewfinder" class="camera-viewfinder"></canvas>
		</div>
		<div class="camera-conditions--container">
			<span data-jcs-element="camera__condition__uploading" class="camera-condition camera-condition--uploading">{{CAMERA_CONDITION_UPLOADING}}</span>
			<span data-jcs-element="camera__condition__capturing" class="camera-condition camera-condition--capturing">{{CAMERA_CONDITION_CAPTURING}}</span>
			<span data-jcs-element="camera__condition__alignment" class="camera-condition camera-condition--alignment">{{CAMERA_CONDITION_ALIGNMENT}}</span>
			<span data-jcs-element="camera__condition__blur" class="camera-condition camera-condition--blur">{{CAMERA_CONDITION_BLUR}}</span>
			<span data-jcs-element="camera__condition__glare" class="camera-condition camera-condition--glare">{{CAMERA_CONDITION_GLARE}}</span>
			<span data-jcs-element="camera__condition__low__resolution" class="camera-condition camera-condition--low-resolution">{{CAMERA_CONDITION_LOW_RESOLUTION}}</span>
		</div>
		<form class="button-container">
			<input data-jcs-element="camera__capture" class="button button--primary" type="button" value="{{CAMERA_CAPTURE}}" />
		</form>
	`;
}

function journeySelectTemplate() {
	return `
		<h2 class="section-title">{{PROVIDER_TITLE_JOURNEY_SELECT}}</h2>
		<div class="journey-select--choices">
		    <select class="journey-choice" data-jcs-element="journey-select__choices"></select>
	    </div>
			<form class="button-container">
			<input data-jcs-element="journey-select__continue" class="button button--primary" type="button" value="{{JOURNEY_SELECT_CONTINUE}}" />
		</form>
	`;
}
