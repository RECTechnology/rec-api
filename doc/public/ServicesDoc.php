<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/4/15
 * Time: 2:15 AM
 */

/**
 * @apiDefine Error503
 * @apiErrorExample {json}
 * Error 503: Service Unavailable
 * {
 *     "status": "error",
 *     "message": "Service temporarily unavailable, please try again in a few minutes"
 * }
 */

/**
 * @api {get} /exchange/v1/ticker/:currency Ticker by currency
 * @apiName TickerByCurrency
 * @apiDescription Ticker by currency
 * @apiVersion 0.1.0
 * @apiGroup Public
 * @apiParam {String} currency The <code>currency</code>
 * @apiSuccess {String} status The resulting status of the request
 * @apiSuccess {String} message The message about the result of the request
 * @apiSuccess {String} data Data with the exchange values
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "ok",
 *          "message": "Exchange info got successful",
 *          "data":
 *                  {
 *                  "BTCxEUR": 333.94,
 *                  "USDxEUR": 0.92,
 *                  "FACxEUR": 0,
 *                  "MXNxEUR": 0.06,
 *                  "PLNxEUR": 0.24
 *                  }
 *    }
 *
 */

/**
 * @api {get} /exchange/v1/currencies Get currencies
 * @apiName GetCurrencies
 * @apiDescription Get available currencies
 * @apiVersion 0.1.0
 * @apiGroup Public
 * @apiSuccess {String} status The resulting status of the request
 * @apiSuccess {String} message The message about the result of the request
 * @apiSuccess {String} data Data with the available currencies
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "ok",
 *          "message": "Currency info got successful",
 *          "data":
 *                  {
 *                  "EUR",
 *                  "BTC",
 *                  "FAC",
 *                  "MXN",
 *                  "PLN",
 *                  "USD"
 *                  }
 *    }
 *
 */


//##################################### BTC_PAY ###################################

/**
 *
 * @api {post} /methods/v1/in/btc Bitcoin payment
 * @apiName Btc-in
 * @apiDescription Creates an new Bitcoin payment
 * @apiVersion 0.2.0
 * @apiGroup Methods
 * @apiUse OAuth2Header
 * @apiParam {Integer} amount The amount in <code>satoshis</code>
 * @apiParam {Integer} confirmations The minimum confirmations to validate the payment
 * @apiParam {Integer} expires_in The timeout (in seconds) of the payment
 * @apiParam {Text} concept Transaction concept sedn by user
 * @apiParam {Text} [notification_url] The notification <code>url</code> to notify about the payment result
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} message The message about the result of the request
 * @apiSuccess {String} id The ID of the transaction
 * @apiSuccess {Integer} amount The amount in <code>satoshis</code> of the transaction
 * @apiSuccess {Integer=8} scale The number of decimals to represent the amount
 * @apiSuccess {String="BTC"} currency The currency
 * @apiSuccess {Object} pay_in_info The bitcoin-payment related data of the transaction
 * @apiUse NotAuthenticated
 * @apiUse Error503
 *
 */

/**
 * @api {get} /methods/v1/in/btc/:id Bitcoin check payment
 * @apiName Btc-in_Check
 * @apiDescription Checks a Bitcoin transaction
 * @apiVersion 0.2.0
 * @apiGroup Methods
 * @apiUse OAuth2Header
 * @apiParam {String} id The transaction <code>id</code>
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} message The message about the result of the request
 * @apiSuccess {String} id The ID of the transaction
 * @apiSuccess {Integer} amount The amount in <code>satoshis</code> of the transaction
 * @apiSuccess {Integer=8} scale The number of decimals to represent the amount
 * @apiSuccess {String="BTC"} currency The currency
 * @apiSuccess {Object} pay_in_info The bitcoin-payment related data of the transaction
 * @apiUse NotAuthenticated
 *
 */

//##################################### FAC_PAY ###################################

/**
 *
 * @api {post} /methods/v1/in/fac Faircoin payment
 * @apiName Fac-in
 * @apiDescription Creates an new Faircoin payment
 * @apiVersion 0.2.0
 * @apiGroup Methods
 * @apiUse OAuth2Header
 * @apiParam {Integer} amount The amount in <code>satoshis</code>
 * @apiParam {Integer} confirmations The minimum confirmations to validate the payment
 * @apiParam {Integer} expires_in The timeout (in seconds) of the payment
 * @apiParam {Text} concept Transaction concept sedn by user
 * @apiParam {Text} [notification_url] The notification <code>url</code> to notify about the payment result
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} message The message about the result of the request
 * @apiSuccess {String} id The ID of the transaction
 * @apiSuccess {Integer} amount The amount in <code>satoshis</code> of the transaction
 * @apiSuccess {Integer=8} scale The number of decimals to represent the amount
 * @apiSuccess {String="BTC"} currency The currency
 * @apiSuccess {Object} pay_in_info The faircoin-payment related data of the transaction
 * @apiUse NotAuthenticated
 * @apiUse Error503
 *
 */

/**
 * @api {get} /methods/v1/in/fac/:id Faircoin check
 * @apiName Fac-in_Check
 * @apiDescription Checks a Faircoin transaction
 * @apiVersion 0.2.0
 * @apiGroup Methods
 * @apiUse OAuth2Header
 * @apiParam {String} id The transaction <code>id</code>
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} message The message about the result of the request
 * @apiSuccess {String} id The ID of the transaction
 * @apiSuccess {Integer} amount The amount in <code>satoshis</code> of the transaction
 * @apiSuccess {Integer=8} scale The number of decimals to represent the amount
 * @apiSuccess {String="BTC"} currency The currency
 * @apiSuccess {Object} pay_in_info The faircoin-payment related data of the transaction
 * @apiUse NotAuthenticated
 *
 */


//##################################### HALCASH_SEND ###################################

/**
 *
 * @api {post} /methods/v1/out/halcash_es HalCash send (Spain)
 * @apiName Halcash_es-out
 * @apiDescription Sends money via HalCash to any number in <code>Spain</code>.
 * @apiVersion 0.2.0
 * @apiGroup Methods
 * @apiUse OAuth2Header
 * @apiParam {Integer} amount The desired amount in <code>EUR</code> <code>cents</code>
 * @apiParam {String} prefix="+34" The prefix of the target phone
 * @apiParam {Integer} phone The target phone number
 * @apiParam {Integer} pin The issuer pin (must have 4 digits)
 * @apiParam {String} concept The issuer text to include in the SMS (max length: 17 chars)
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} message The message about the result of the request
 * @apiSuccess {String} id The ID of the transaction
 * @apiSuccess {Integer} amount The amount in <code>cents</code> of the transaction
 * @apiSuccess {Integer=2} scale The number of decimals to represent the amount
 * @apiSuccess {String="EUR"} currency The currency
 * @apiSuccess {Object} pay_out_info The halcash related data of the transaction
 * @apiUse NotAuthenticated
 *
 */

/**
 *
 * @api {get} /methods/v1/out/halcash_es HalCash check
 * @apiName Halcash_es-out_Check
 * @apiDescription Checks the status of the halcash transaction
 * @apiVersion 0.2.0
 * @apiGroup Methods
 * @apiUse OAuth2Header
 * @apiParam {String} id The transaction <code>id</code>
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} message The message about the result of the request
 * @apiSuccess {String} id The ID of the transaction
 * @apiSuccess {Integer} amount The amount in <code>cents</code> of the transaction
 * @apiSuccess {Integer=2} scale The number of decimals to represent the amount
 * @apiSuccess {String="EUR"} currency The currency
 * @apiSuccess {Object} pay_out_info The halcash related data of the transaction
 * @apiUse NotAuthenticated
 *
 */


//##################################### PAYNET_REFERENCE ###################################

/**
 *
 * @api {post} /methods/v1/in/paynet_reference Paynet Reference
 * @apiName PaynetReference-in
 * @apiDescription Receive payments with Paynet.
 * @apiVersion 0.1.0
 * @apiGroup Methods
 * @apiUse OAuth2Header
 * @apiParam {Integer} amount The desired amount in <code>MXN</code> <code>cents</code>
 * @apiParam {String} concept The issuer text to include in the transaction description
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} message The message about the result of the request
 * @apiSuccess {String} id The ID of the transaction
 * @apiSuccess {Integer} amount The amount in <code>cents</code> of the transaction
 * @apiSuccess {Integer=2} scale The number of decimals to represent the amount
 * @apiSuccess {String="MXN"} currency The currency
 * @apiSuccess {Object} pay_in_info The paynet reference related data of the transaction
 * @apiUse NotAuthenticated
 *
 */

/**
 *
 * @api {get} /services/v1/paynet_reference/:id Paynet Reference Check
 * @apiName PaynetReferenceCheck
 * @apiDescription Checks the status of the paynet reference transaction
 * @apiVersion 0.1.0
 * @apiGroup Services
 * @apiUse OAuth2Header
 * @apiParam {String} id The transaction <code>id</code>
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} message The message about the result of the request
 * @apiSuccess {String} id The ID of the transaction
 * @apiSuccess {Integer} amount The amount in <code>cents</code> of the transaction
 * @apiSuccess {Integer=2} scale The number of decimals to represent the amount
 * @apiSuccess {String="MXN"} currency The currency
 * @apiSuccess {Object} pay_in_info The paynet reference related data of the transaction
 * @apiUse NotAuthenticated
 *
 */

//##################################### SAFETYPAY ###################################

///**
// *
// * @api {post} /services/v1/safetypay SafetyPay
// * @apiName Safetypay
// * @apiDescription Receive payments with safety gateway.
// * @apiVersion 0.1.0
// * @apiGroup Services
// * @apiUse OAuth2Header
// * @apiParam {String="EUR,USD,MXN"} currency The transaction currency ISO-8601
// * @apiParam {String} amount The desired amount with 2 decimals.
// * @apiParam {String} url_success The url to redirect the client when the transaction is successfull
// * @apiParam {String} url_fail The url to redirect the client when the transaction is unsuccesfull
// * @apiSuccess {String} status The resulting status of the transaction
// * @apiSuccess {String} message The message about the result of the request
// * @apiSuccess {String} id The ID of the transaction
// * @apiSuccess {Integer} amount The total amount to pay in <code>cents</code>
// * @apiSuccess {Integer=2} scale The number of decimals to represent the amount
// * @apiSuccess {String="EUR,USD,MXN"} currency The currency
// * @apiSuccess {Object} data The data of the request
// * @apiSuccess {Integer} data.error_number The error number. (<code>0</code> means that all goes fine)
// * @apiSuccess {String} data.url Url to redirect the client to finish the payment.
// * @apiSuccess {String} data.signature This parameter must be sent by <code>POST</code> to the obtained url to validate the sesion.
// * @apiUse NotAuthenticated
// *
// */

//##################################### PADEMOBILE ###################################

///**
// * @apiIgnore Not finished method
// * @api {post} /services/v1/pademobile Pademobile
// * @apiName Pademobile
// * @apiDescription Receive payments with pademobile gateway.
// * @apiVersion 0.1.0
// * @apiGroup Services
// * @apiUse OAuth2Header
// * @apiParam {String} country The country.
// * @apiParam {String} url Url When we will notificate the transaction result.
// * @apiParam {String} description A simple product description.
// * @apiParam {Integer} amount Transaction amount in <code>cents</code>
// * @apiSuccess {String} status The resulting status of the transaction
// * @apiSuccess {String} message The message about the result of the request
// * @apiSuccess {String} id The ID of the transaction
// * @apiSuccess {Integer} amount The total amount to pay in <code>cents</code>
// * @apiSuccess {Integer=2} scale The number of decimals to represent the amount
// * @apiSuccess {String="EUR,USD,MXN"} currency The currency
// * @apiSuccess {Object} data The data of the request
// * @apiSuccess {String} data.url Url to redirect the client to finish the payment.
// * @apiUse NotAuthenticated
// *
// */

//##################################### MULTIVA ###################################

///**
// * @apiIgnore Not finished method
// * @api {post} /services/v1/multiva Multiva
// * @apiName Multiva
// * @apiDescription Receive payments with Multiva TPV.
// * @apiVersion 0.1.0
// * @apiGroup Services
// * @apiUse OAuth2Header
// * @apiParam {Integer} amount Transaction amount in <code>cents</code>
// * @apiParam {String} url_notification Url When we will notificate the transaction result.
// * @apiParam {String} description A simple product description.
// * @apiParam {String} amount Transaction amount in <code>cents</code>
// * @apiSuccess {String} status The resulting status of the transaction
// * @apiSuccess {String} message The message about the result of the request
// * @apiSuccess {String} id The ID of the transaction
// * @apiSuccess {Integer} amount The total amount to pay in <code>cents</code>
// * @apiSuccess {Integer=2} scale The number of decimals to represent the amount
// * @apiSuccess {String="MXN"} currency The currency
// * @apiSuccess {Object} data The data for the form to generate the TPV.
// * @apiSuccess {String} data.comtotal Total Transaction.
// * @apiSuccess {Integer} data.comcurrency Currency code.
// * @apiSuccess {String} data.comaddress PROSA.
// * @apiSuccess {String} data.comorder_id {{ name }} id.
// * @apiSuccess {Integer} data.commerchant Merchant id.
// * @apiSuccess {Integer} data.comstore Store reference asigned by {{ name }}.
// * @apiSuccess {Integer} data.comterm Terminal code asigned by {{ name }}.
// * @apiSuccess {String} data.comdigest Multiva digest.
// * @apiSuccess {String} data.comaction Form action url.
// * @apiSuccess {String} data.comurlback {{ name }} notification url.
// * @apiUse NotAuthenticated
// *
// */

//##################################### SABADELL ###################################

///**
// * @api {post} /services/v2/sabadell Sabadell
// * @apiName Sabadell
// * @apiDescription Receive payments with Sabadell TPV.
// * @apiVersion 0.2.0
// * @apiGroup Services
// * @apiUse OAuth2Header
// * @apiParam {Integer} amount Transaction amount in <code>cents</code>
// * @apiParam {String} description A simple product description.
// * @apiParam {String} url_notification Url When we will notificate the transaction result.
// * @apiParam {String} url_ok Url When we will redirect the user if the transaction was successful.
// * @apiParam {String} url_ko Url When we will redirect the user if the transaction was unsuccessful.
// * @apiSuccess {String} status The resulting status of the transaction
// * @apiSuccess {String} message The message about the result of the request
// * @apiSuccess {String} id The ID of the transaction
// * @apiSuccess {Integer} amount The total amount to pay in <code>cents</code>
// * @apiSuccess {Integer=2} scale The number of decimals to represent the amount
// * @apiSuccess {String="EUR"} currency The currency
// * @apiSuccess {Object} data The data for the form to generate the TPV.
// * @apiSuccess {String} data.Ds_Merchant_Amount Total transaction amount.
// * @apiSuccess {Integer} data.Ds_Merchant_Currency Currency code ISO-8601.
// * @apiSuccess {String} data.Ds_Merchant_Order {{ name }} transaction id.
// * @apiSuccess {Integer} data.Ds_Merchant_MerchantCode {{ name }} merchant code.
// * @apiSuccess {Integer} data.Ds_Merchant_Terminal {{ name }} terminal.
// * @apiSuccess {Integer} data.Ds_Merchant_TransactionType Transaction type.
// * @apiSuccess {String} data.Ds_Merchant_MerchantURL {{ name }} url notification.
// * @apiSuccess {String} data.Ds_Merchant_UrlOK Url to redirect the client when the transaction was successful.
// * @apiSuccess {String} data.Ds_Merchant_UrlKO Url to redirect the client when the transaction was unsuccessful.
// * @apiSuccess {String} data.Ds_Merchant_Signature Url to redirect the client to finish the payment.
// * @apiSuccess {String} data.Ds_Merchant_TpvV Url to redirect the client to finish the payment.
// * @apiUse NotAuthenticated
// *
// */

//##################################### PAGOFACIL ###################################

///**
// *
// * @api {post} /services/v1/pagofacil Pagofacil
// * @apiName Pagofacil
// * @apiDescription Receive payments with Pagofacil Gateway.
// * @apiVersion 0.1.0
// * @apiGroup Services
// * @apiUse OAuth2Header
// * @apiParam {String} name Cardholder name.
// * @apiParam {String} surname Cardholder surname.
// * @apiParam {String} card_number Card number.
// * @apiParam {String} cvv Verificator digit.
// * @apiParam {String} cp Postal Code.
// * @apiParam {String} expiration_month Card expiration month.
// * @apiParam {String} expiration_year Card espiration year.
// * @apiParam {String} amount Transaction amount in <code>cents</code>.
// * @apiParam {String} email E-mail.
// * @apiParam {String} phone Phone number.
// * @apiParam {String} mobile_phone Mobile phone number.
// * @apiParam {String} street_number Cardholder street number.
// * @apiParam {String} colony Colony.
// * @apiParam {String} city City.
// * @apiParam {String} quarter Quarter.
// * @apiParam {String} country Country.
// * @apiSuccess {String} status The resulting status of the transaction
// * @apiSuccess {String} message The message about the result of the request
// * @apiSuccess {String} id The ID of the transaction
// * @apiSuccess {Integer} amount The total amount to pay in <code>cents</code>
// * @apiSuccess {Integer=2} scale The number of decimals to represent the amount
// * @apiSuccess {String="MXN"} currency The currency
// * @apiSuccess {Object} data The data to the request.
// * @apiSuccess {String} data.authorization Authorization flag.
// * @apiSuccess {Integer} data.authorization_id Authorization id.
// * @apiSuccess {String} data.transaction_id Transaction id.
// * @apiSuccess {String} data.text Transacition description.
// * @apiSuccess {String} data.mode Transaction mode.
// * @apiSuccess {String} data.type_card Credit card type.
// * @apiUse NotAuthenticated
// *
// */

//##################################### SABADELL ###################################

///**
// * @api {post} /services/v1/abanca Abanca
// * @apiName Abanca
// * @apiDescription Receive payments with Abanca TPV.
// * @apiVersion 0.2.0
// * @apiGroup Services
// * @apiUse OAuth2Header
// * @apiParam {Integer} amount Transaction amount in <code>cents</code>
// * @apiParam {String} description A simple product description.
// * @apiParam {String} url_notification Url When we will notificate the transaction result.
// * @apiParam {String} url_ok Url When we will redirect the user if the transaction was successful.
// * @apiParam {String} url_ko Url When we will redirect the user if the transaction was unsuccessful.
// * @apiSuccess {String} status The resulting status of the transaction
// * @apiSuccess {String} message The message about the result of the request
// * @apiSuccess {String} id The ID of the transaction
// * @apiSuccess {Integer} amount The total amount to pay in <code>cents</code>
// * @apiSuccess {Integer=2} scale The number of decimals to represent the amount
// * @apiSuccess {String="EUR"} currency The currency
// * @apiSuccess {Object} data The data for the form to generate the TPV.
// * @apiSuccess {String} data.Ds_Merchant_Amount Total transaction amount.
// * @apiSuccess {Integer} data.Ds_Merchant_Currency Currency code ISO-8601.
// * @apiSuccess {String} data.Ds_Merchant_Order {{ name }} transaction id.
// * @apiSuccess {Integer} data.Ds_Merchant_MerchantCode {{ name }} merchant code.
// * @apiSuccess {Integer} data.Ds_Merchant_Terminal {{ name }} terminal.
// * @apiSuccess {Integer} data.Ds_Merchant_TransactionType Transaction type.
// * @apiSuccess {String} data.Ds_Merchant_MerchantURL {{ name }} url notification.
// * @apiSuccess {String} data.Ds_Merchant_UrlOK Url to redirect the client when the transaction was successful.
// * @apiSuccess {String} data.Ds_Merchant_UrlKO Url to redirect the client when the transaction was unsuccessful.
// * @apiSuccess {String} data.Ds_Merchant_Signature Url to redirect the client to finish the payment.
// * @apiSuccess {String} data.Ds_Merchant_TpvV Url to redirect the client to finish the payment.
// * @apiUse NotAuthenticated
// *
// */

//##################################### POS ###################################

///**
// * @api {post} /pos/v1/transaction/:id POS
// * @apiName POS
// * @apiDescription Receive payments with a POS.
// * @apiVersion 0.2.0
// * @apiGroup Services
// * @apiUse OAuth2Header
// * @apiParam {Integer} amount Transaction amount in <code>cents</code>
// * @apiParam {String} description A simple product description.
// * @apiParam {String} currency Currency.
// * @apiParam {String} url_notification Url When we will notificate the transaction result.
// * @apiParam {String} url_ok Url When we will redirect the user if the transaction was successful.
// * @apiParam {String} url_ko Url When we will redirect the user if the transaction was unsuccessful.
// * @apiParam {String} order_id Merchant transaction ID.
// * @apiSuccess {String} status The resulting status of the transaction
// * @apiSuccess {String} message The message about the result of the request
// * @apiSuccess {String} id The ID of the transaction
// * @apiSuccess {Integer} amount The total amount to pay in <code>cents</code>
// * @apiSuccess {Integer=2} scale The number of decimals to represent the amount
// * @apiSuccess {String="EUR"} currency The currency
// * @apiSuccess {DateTime} updated Last update
// * @apiSuccess {Object} data The data for the form to generate the POS.
// * @apiSuccess {String} data.transaction_pos_id POS id.
// * @apiSuccess {Integer} data.url_notification Url for notifications.
// * @apiUse NotAuthenticated
// *
// */

