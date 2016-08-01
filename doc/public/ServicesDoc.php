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






