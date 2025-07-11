const GooglePay = (() => {
    const CONFIG = {
        apiVersion: 2,
        apiVersionMinor: 0,
        environment: '',
        merchantId: '',
        merchantName: 'worldpay',
        allowedCardNetworks: ["MASTERCARD", "VISA"],
        allowedCardAuthMethods: ["PAN_ONLY", "CRYPTOGRAM_3DS"],
        allowCreditCards: false
    };

    let paymentsClient = null;

    function init(environment, merchantId, allowCreditCards) {
        if (environment) CONFIG.environment = environment;
        if (merchantId) CONFIG.merchantId = merchantId;
        if (allowCreditCards) CONFIG.allowCreditCards = allowCreditCards;

        paymentsClient = null;
    }

    function getGooglePaymentsClient() {
        if (!paymentsClient) {
            paymentsClient = new google.payments.api.PaymentsClient({ environment: CONFIG.environment });
        }
        return paymentsClient;
    }

    function getBaseRequest() {
        return {
            apiVersion: CONFIG.apiVersion,
            apiVersionMinor: CONFIG.apiVersionMinor
        };
    }

    function getBaseCardPaymentMethod() {
        return {
            type: 'CARD',
            parameters: {
                allowedAuthMethods: CONFIG.allowedCardAuthMethods,
                allowedCardNetworks: CONFIG.allowedCardNetworks,
                allowCreditCards: CONFIG.allowCreditCards
            }
        };
    }

    function getGoogleIsReadyToPayRequest() {
        return {
            ...getBaseRequest(),
            allowedPaymentMethods: [getBaseCardPaymentMethod()]
        };
    }

    function getGoogleTransactionInfo(countryCode, currencyCode, amount) {
        if (!amount) {
            throw new Error("Amount is required");
        }

        return {
            countryCode,
            currencyCode,
            totalPriceStatus: 'FINAL',
            totalPrice: amount
        };
    }

    function getGooglePaymentDataRequest(countryCode, currencyCode, amount, domain) {
        if (!amount) {
            throw new Error("Amount is required");
        }

        return {
            ...getBaseRequest(),
            allowedPaymentMethods: [{
                ...getBaseCardPaymentMethod(),
                tokenizationSpecification: {
                    type: 'PAYMENT_GATEWAY',
                    parameters: {
                        'gateway': CONFIG.merchantName,
                        'gatewayMerchantId': CONFIG.merchantId
                    }
                }
            }],
            transactionInfo: getGoogleTransactionInfo(countryCode, currencyCode, amount),
            merchantInfo: {
                merchantName: domain,
                merchantId: CONFIG.merchantId
            }
        };
    }

    function initializeDeposit(countryCode, currencyCode, amount, domain) {
        return new Promise((resolve, reject) => {
            if (!amount) {
                return reject(new Error("Amount is required"));
            }

            const paymentsClient = getGooglePaymentsClient();
            const isReadyToPayRequest = getGoogleIsReadyToPayRequest();

            paymentsClient.isReadyToPay(isReadyToPayRequest)
                .then(response => {
                    if (response.result) {
                        startTransaction(countryCode, currencyCode, amount, domain)
                            .then(resolve)
                            .catch(reject);
                    } else {
                        reject(new Error("Google Pay is not available"));
                    }
                })
                .catch(reject);
        });
    }

    function startTransaction(countryCode, currencyCode, amount, domain) {
        return new Promise((resolve, reject) => {
            if (!amount) {
                return reject(new Error("Amount is required"));
            }

            const paymentDataRequest = getGooglePaymentDataRequest(countryCode, currencyCode, amount, domain);
            const paymentsClient = getGooglePaymentsClient();

            paymentsClient.loadPaymentData(paymentDataRequest)
                .then(paymentData => {
                    let paymentToken = paymentData.paymentMethodData.tokenizationData.token;
                    resolve(paymentToken);
                })
                .catch(reject);
        });
    }

    return {
        init,
        initializeDeposit
    };
})();
