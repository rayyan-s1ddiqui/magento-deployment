/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
define([
    'jquery',
    'underscore',
    'Magento_Checkout/js/view/payment/default',
    'braintree',
    'braintreeCheckoutPayPalAdapter',
    'braintreePayPalCheckout',
    'PayPal_Braintree/js/helper/format-amount',
    'PayPal_Braintree/js/helper/remove-non-digit-characters',
    'PayPal_Braintree/js/helper/replace-unsupported-characters',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Vault/js/view/payment/vault-enabler',
    'Magento_Checkout/js/action/create-billing-address',
    'Magento_Checkout/js/action/select-billing-address',
    'Magento_CheckoutAgreements/js/view/checkout-agreements',
    'mage/translate'
], function (
    $,
    _,
    Component,
    braintree,
    Braintree,
    paypalCheckout,
    formatAmount,
    removeNonDigitCharacters,
    replaceUnsupportedCharacters,
    quote,
    fullScreenLoader,
    additionalValidators,
    stepNavigator,
    VaultEnabler,
    createBillingAddress,
    selectBillingAddress,
    checkoutAgreements,
    $t
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'PayPal_Braintree/payment/paypal',
            code: 'braintree_paypal',
            active: false,
            paypalInstance: null,
            paymentMethodNonce: null,
            grandTotalAmount: null,
            isReviewRequired: false,
            customerEmail: null,

            /**
             * Additional payment data
             *
             * {Object}
             */
            additionalData: {},

            /**
             * PayPal client configuration
             *
             * {Object}
             */
            clientConfig: {
                offerCredit: false,
                offerCreditOnly: false,
                dataCollector: {
                    paypal: true
                },

                buttonPayPalId: 'braintree_paypal_placeholder',
                buttonCreditId: 'braintree_paypal_credit_placeholder',
                buttonPayLaterId: 'braintree_paypal_paylater_placeholder',

                onDeviceDataReceived: function (deviceData) {
                    this.additionalData['device_data'] = deviceData;
                },

                /**
                 * Triggers when widget is loaded
                 */
                onReady: function () {
                    this.setupPayPal();
                },

                /**
                 * Triggers on payment nonce receive
                 *
                 * @param {Object} response
                 */
                onPaymentMethodReceived: function (response) {
                    this.beforePlaceOrder(response);
                }
            },
            imports: {
                onActiveChange: 'active'
            }
        },

        /**
         * Set list of observable attributes
         *
         * @returns {exports.initObservable}
         */
        initObservable: function () {
            let self = this;

            this._super()
                .observe(['active', 'isReviewRequired', 'customerEmail']);

            window.addEventListener('hashchange', function (e) {
                let methodCode = quote.paymentMethod();

                if (methodCode === 'braintree_paypal' || methodCode === 'braintree_paypal_vault') {
                    if (e.newURL.indexOf('payment') > 0 && self.grandTotalAmount !== null) {
                        self.reInitPayPal();
                    }
                }
            });

            quote.paymentMethod.subscribe(function (value) {
                let methodCode = value;

                if (methodCode === 'braintree_paypal' || methodCode === 'braintree_paypal_vault') {
                    self.reInitPayPal();
                }
            });

            this.vaultEnabler = new VaultEnabler();
            this.vaultEnabler.setPaymentCode(this.getVaultCode());
            this.vaultEnabler.isActivePaymentTokenEnabler.subscribe(function () {
                self.onVaultPaymentTokenEnablerChange();
            });

            this.grandTotalAmount = quote.totals()['base_grand_total'];

            quote.totals.subscribe(function () {
                if (self.grandTotalAmount !== quote.totals()['base_grand_total']) {
                    self.grandTotalAmount = quote.totals()['base_grand_total'];
                    let methodCode = quote.paymentMethod();

                    if (methodCode &&
                        (methodCode.method === 'braintree_paypal' || methodCode.method === 'braintree_paypal_vault')) {
                        self.reInitPayPal();
                    }
                }
            });

            // for each component initialization need update property
            this.isReviewRequired(false);
            this.initClientConfig();

            return this;
        },

        /**
         * Get payment name
         *
         * @returns {String}
         */
        getCode: function () {
            return this.code;
        },

        /**
         * Get payment title
         *
         * @returns {String}
         */
        getTitle: function () {
            return window.checkoutConfig.payment[this.getCode()].title;
        },

        /**
         * Check if payment is active
         *
         * @returns {Boolean}
         */
        isActive: function () {
            let active = this.getCode() === this.isChecked();

            this.active(active);

            return active;
        },

        /**
         * Triggers when payment method change
         *
         * @param {Boolean} isActive
         */
        onActiveChange: function (isActive) {
            if (!isActive) {
                return;
            }

            // need always re-init Braintree with PayPal configuration
            this.reInitPayPal();
        },

        /**
         * Init config
         */
        initClientConfig: function () {
            this.clientConfig = _.extend(this.clientConfig, this.getPayPalConfig());

            _.each(this.clientConfig, function (fn, name) {
                if (typeof fn === 'function') {
                    this.clientConfig[name] = fn.bind(this);
                }
            }, this);
        },

        /**
         * Set payment nonce
         *
         * @param {String} paymentMethodNonce
         */
        setPaymentMethodNonce: function (paymentMethodNonce) {
            this.paymentMethodNonce = paymentMethodNonce;
        },

        /**
         * Update quote billing address
         *
         * @param {Object}customer
         * @param {Object}address
         */
        setBillingAddress: function (customer, address) {
            let billingAddress = {
                street: [address.line1],
                city: address.city,
                postcode: address.postalCode,
                countryId: address.countryCode,
                email: customer.email,
                firstname: customer.firstName,
                lastname: customer.lastName,
                telephone: removeNonDigitCharacters(_.get(customer, 'phone', '00000000000'))
            };

            billingAddress['region_code'] = typeof address.state === 'string' ? address.state : '';
            billingAddress = createBillingAddress(billingAddress);
            quote.billingAddress(billingAddress);
        },

        /**
         * Prepare data to place order
         *
         * @param {Object} data
         */
        beforePlaceOrder: function (data) {
            this.setPaymentMethodNonce(data.nonce);
            this.customerEmail(data.details.email);
            if (quote.isVirtual()) {
                this.isReviewRequired(true);
            } else if (this.isRequiredBillingAddress() === '1' && quote.billingAddress() === null) {
                if (data.details?.billingAddress?.line1) {
                    this.setBillingAddress(data.details, data.details.billingAddress);
                } else {
                    this.setBillingAddress(data.details, data.details.shippingAddress);
                }
            } else if (quote.shippingAddress() === quote.billingAddress()) {
                selectBillingAddress(quote.shippingAddress());
            } else {
                selectBillingAddress(quote.billingAddress());
            }
            this.placeOrder();
        },

        /**
         * Re-init PayPal Auth Flow
         */
        reInitPayPal: function () {
            this.disableButton();
            this.clientConfig.paypal.amount = formatAmount(this.grandTotalAmount);

            if (!quote.isVirtual()) {
                this.clientConfig.paypal.enableShippingAddress = true;
                this.clientConfig.paypal.shippingAddressEditable = false;
                this.clientConfig.paypal.shippingAddressOverride = this.getShippingAddress();
            }

            Braintree.setConfig(this.clientConfig);

            if (Braintree.getPayPalInstance()) {
                Braintree.getPayPalInstance().teardown(function () {
                    Braintree.setup();
                });
                Braintree.setPayPalInstance(null);
            } else {
                Braintree.setup();
                this.enableButton();
            }
        },

        /**
         * Setup PayPal instance
         */
        setupPayPal: function () {
            if (Braintree.config.paypalInstance) {
                fullScreenLoader.stopLoader(true);
                return;
            }

            paypalCheckout.create({
                client: Braintree.clientInstance
            }, function (createErr, paypalCheckoutInstance) {
                if (createErr) {
                    Braintree.showError(
                        $t('PayPal Checkout could not be initialized. Please contact the store owner.'));
                    console.error('paypalCheckout error', createErr);
                    return;
                }
                let quoteObj = quote.totals(),
                    configSDK = {
                        components: 'buttons,messages,funding-eligibility',
                        'enable-funding': this.isCreditEnabled() ? 'credit' : 'paylater',
                        currency: quoteObj['base_currency_code'],
                        dataAttributes: {
                            'page-type': 'checkout'
                        }
                    },
                    buyerCountry = this.getMerchantCountry();

                if (Braintree.getEnvironment() === 'sandbox' && buyerCountry !== null) {
                    configSDK['buyer-country'] = buyerCountry;
                }
                paypalCheckoutInstance.loadPayPalSDK(configSDK, function () {
                    this.loadPayPalButton(paypalCheckoutInstance, 'paypal');
                    if (this.isCreditEnabled()) {
                        this.loadPayPalButton(paypalCheckoutInstance, 'credit');
                    }
                    if (this.isPayLaterEnabled()) {
                        this.loadPayPalButton(paypalCheckoutInstance, 'paylater');
                    }

                }.bind(this));
            }.bind(this));
        },

        loadPayPalButton: function (paypalCheckoutInstance, funding) {
            let paypalPayment = Braintree.config.paypal,
                onPaymentMethodReceived = Braintree.config.onPaymentMethodReceived,
                style = {
                    label: Braintree.getLabel(funding),
                    color: Braintree.getColor(funding),
                    shape: Braintree.getShape(funding)
                },
                button,
                events = Braintree.events,
                payPalButtonId,
                payPalButtonElement;

            if (funding === 'credit') {
                Braintree.config.buttonId = this.getCreditButtonId();
            } else if (funding === 'paylater') {
                Braintree.config.buttonId = this.getPayLaterButtonId();
            } else {
                Braintree.config.buttonId = this.getPayPalButtonId();
            }

            payPalButtonId = Braintree.config.buttonId;
            payPalButtonElement = $('#' + Braintree.config.buttonId);
            payPalButtonElement.html('');

            // Render
            Braintree.config.paypalInstance = paypalCheckoutInstance;

            button = window.paypal.Buttons({
                fundingSource: funding,
                env: Braintree.getEnvironment(),
                style: style,
                commit: true,
                locale: Braintree.config.paypal.locale,

                onInit: function (data, actions) {
                    let agreements = checkoutAgreements().agreements,
                        shouldDisableActions = false;

                    actions.disable();

                    _.each(agreements, function (item) {
                        if (checkoutAgreements().isAgreementRequired(item)) {
                            let paymentMethodCode = quote.paymentMethod().method,
                                inputId = '#agreement_' + paymentMethodCode + '_' + item.agreementId,
                                inputEl = document.querySelector(inputId);

                            if (!inputEl.checked) {
                                shouldDisableActions = true;
                            }

                            inputEl.addEventListener('change', function () {
                                if (additionalValidators.validate()) {
                                    actions.enable();
                                } else {
                                    actions.disable();
                                }
                            });
                        }
                    });

                    if (!shouldDisableActions) {
                        actions.enable();
                    }
                },

                createOrder: function () {
                    return paypalCheckoutInstance.createPayment(paypalPayment).catch(function (err) {
                        throw err.details.originalError.details.originalError.paymentResource;
                    });
                },

                onCancel: function (data) {
                    console.log('checkout.js payment cancelled', JSON.stringify(data, 0, 2));

                    if (typeof events.onCancel === 'function') {
                        events.onCancel();
                    }
                },

                onError: function (err) {
                    if (err.errorName === 'VALIDATION_ERROR' && err.errorMessage.indexOf('Value is invalid') !== -1) {
                        Braintree.showError(
                            $t(
                                'Address failed validation. Please check and confirm your City, State, and Postal Code'
                            )
                        );
                    } else {
                        Braintree.showError(
                            $t('PayPal Checkout could not be initialized. Please contact the store owner.'));
                    }
                    Braintree.config.paypalInstance = null;
                    console.error('Paypal checkout.js error', err);

                    if (typeof events.onError === 'function') {
                        events.onError(err);
                    }
                },

                onClick: function (data) {
                    if (!quote.isVirtual()) {
                        this.clientConfig.paypal.enableShippingAddress = true;
                        this.clientConfig.paypal.shippingAddressEditable = false;
                        this.clientConfig.paypal.shippingAddressOverride = this.getShippingAddress();
                    }

                    // To check term & conditions input checked - validate additional validators.
                    if (!additionalValidators.validate()) {
                        return false;
                    }

                    if (typeof events.onClick === 'function') {
                        events.onClick(data);
                    }
                }.bind(this),

                onApprove: function (data) {
                    return paypalCheckoutInstance.tokenizePayment(data)
                        .then(function (payload) {
                            onPaymentMethodReceived(payload);
                        });
                }
            });

            if (funding === 'paylater') {
                button.updateProps({
                    message: Braintree.getMessage(
                        funding,
                        paypalPayment.amount,
                        'checkout'
                    )
                });
            }

            if (button.isEligible() && payPalButtonElement.length) {
                button.render('#' + payPalButtonId).then(function () {
                    Braintree.enableButton();
                    if (typeof Braintree.config.onPaymentMethodError === 'function') {
                        Braintree.config.onPaymentMethodError();
                    }
                }).then(function (data) {
                    if (typeof events.onRender === 'function') {
                        events.onRender(data);
                    }
                });
            }
        },

        /**
         * Get locale
         *
         * @returns {String}
         */
        getLocale: function () {
            return window.checkoutConfig.payment[this.getCode()].locale;
        },

        /**
         * Is Billing Address required from PayPal side
         *
         * @returns {exports.isRequiredBillingAddress|(function())|boolean}
         */
        isRequiredBillingAddress: function () {
            return window.checkoutConfig.payment[this.getCode()].isRequiredBillingAddress;
        },

        /**
         * Get configuration for PayPal
         *
         * @returns {Object}
         */
        getPayPalConfig: function () {
            let totals = quote.totals(),
                config = {},
                isActiveVaultEnabler = this.isActiveVault();

            config.paypal = {
                flow: 'checkout',
                amount: formatAmount(this.grandTotalAmount),
                currency: totals['base_currency_code'],
                locale: this.getLocale(),

                /**
                 * Triggers on any Braintree error
                 */
                onError: function () {
                    this.paymentMethodNonce = null;
                },

                /**
                 * Triggers if browser doesn't support PayPal Checkout
                 */
                onUnsupported: function () {
                    this.paymentMethodNonce = null;
                }
            };

            if (isActiveVaultEnabler) {
                config.paypal.requestBillingAgreement = true;
            }

            if (!quote.isVirtual()) {
                config.paypal.enableShippingAddress = true;
                config.paypal.shippingAddressEditable = false;
                config.paypal.shippingAddressOverride = this.getShippingAddress();
            }

            if (this.getMerchantName()) {
                config.paypal.displayName = this.getMerchantName();
            }

            return config;
        },

        /**
         * Get shipping address
         *
         * @returns {Object}
         */
        getShippingAddress: function () {
            let address = quote.shippingAddress();

            return {
                recipientName: address.firstname + ' ' + address.lastname,
                line1: address.street[0],
                line2: typeof address.street[2] === 'undefined'
                    ? address.street[1] : address.street[1] + ' ' + address.street[2],
                city: address.city,
                countryCode: address.countryId,
                postalCode: address.postcode,
                state: address.regionCode
            };
        },

        /**
         * Get merchant name
         *
         * @returns {String}
         */
        getMerchantName: function () {
            return window.checkoutConfig.payment[this.getCode()]['merchantName'];
        },

        /**
         * Get data
         *
         * @returns {Object}
         */
        getData: function () {
            let data = {
                'method': this.getCode(),
                'additional_data': {
                    'payment_method_nonce': this.paymentMethodNonce
                }
            };

            data['additional_data'] = _.extend(data['additional_data'], this.additionalData);

            this.vaultEnabler.visitAdditionalData(data);

            return data;
        },

        /**
         * Returns payment acceptance mark image path
         *
         * @returns {String}
         */
        getPaymentAcceptanceMarkSrc: function () {
            return window.checkoutConfig.payment[this.getCode()]['paymentAcceptanceMarkSrc'];
        },

        /**
         * Get paypal vault payment method code
         *
         * @returns {String}
         */
        getVaultCode: function () {
            return window.checkoutConfig.payment[this.getCode()]['vaultCode'];
        },

        /**
         * Check if vault is active
         *
         * @returns {Boolean}
         */
        isActiveVault: function () {
            return this.vaultEnabler.isVaultEnabled() && this.vaultEnabler.isActivePaymentTokenEnabler();
        },

        /**
         * Re-init PayPal Auth flow to use Vault
         */
        onVaultPaymentTokenEnablerChange: function () {
            this.clientConfig.paypal.singleUse = !this.isActiveVault();
            this.reInitPayPal();
        },

        /**
         * Disable submit button
         */
        disableButton: function () {
            // stop any previous shown loaders
            fullScreenLoader.stopLoader(true);
            fullScreenLoader.startLoader();
            $('[data-button="place"]').attr('disabled', 'disabled');
        },

        /**
         * Enable submit button
         */
        enableButton: function () {
            $('[data-button="place"]').removeAttr('disabled');
            fullScreenLoader.stopLoader(true);
        },

        /**
         * Triggers when customer click "Continue to PayPal" button
         */
        payWithPayPal: function () {
            if (additionalValidators.validate()) {
                Braintree.checkout.paypal.initAuthFlow();
            }
        },

        /**
         * Get PayPal button id
         *
         * @returns {String}
         */
        getPayPalButtonId: function () {
            return this.clientConfig.buttonPayPalId;
        },

        /**
         * Get Credit button id
         *
         * @returns {String}
         */
        getCreditButtonId: function () {
            return this.clientConfig.buttonCreditId;
        },

        /**
         * Get Pay Later button id
         *
         * @returns {String}
         */
        getPayLaterButtonId: function () {
            return this.clientConfig.buttonPayLaterId;
        },

        /**
         * Check if Pay Later enabled
         *
         * @returns {*}
         */
        isPayLaterEnabled: function () {
            return window.checkoutConfig.payment['braintree_paypal_paylater']['isActive'];
        },

        /**
         * Check if Pay Later messaging enabled
         *
         * @returns {*}
         */
        isPayLaterMessageEnabled: function () {
            return window.checkoutConfig.payment['braintree_paypal_paylater']['isMessageActive'];
        },

        /**
         * Get grand total
         *
         * @returns {string}
         */
        getGrandTotalAmount: function () {
            return formatAmount(this.grandTotalAmount);
        },

        /**
         * Check if PayPal Credit enabled
         *
         * @returns {*}
         */
        isCreditEnabled: function () {
            return window.checkoutConfig.payment['braintree_paypal_credit']['isActive'];
        },

        /**
         * Get merchant country
         *
         * @returns {*}
         */
        getMerchantCountry: function () {
            return window.checkoutConfig.payment[this.getCode()]['merchantCountry'];
        },

        /**
         * Regex to replace all unsupported characters.
         *
         * @param str
         */
        replaceUnsupportedCharacters: function (str) {
            // eslint-disable-next-line no-useless-escape
            str.replace('/[^a-zA-Z0-9\s\-.\']/', '');
            return str.substr(0, 127);
        }
    });
});
