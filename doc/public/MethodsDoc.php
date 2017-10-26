<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/4/15
 * Time: 2:15 AM
 */

/**
 * @apiDefine Forbidden
 * @apiErrorExample {json} Forbidden
 * HTTP/1.1 403 Forbidden
 *{
 *    "status": "error",
 *    "message": "Method temporally unavailable."
 * }
 */

/**
 * @api {get} /exchange/v1/ticker/:currency Ticker by currency
 * @apiName TickerByCurrency
 * @apiDescription Ticker by currency
 * @apiVersion 1.0.0
 * @apiGroup Public
 *
 * @apiParam {String} currency The cryptocurrency
 *
 * @apiSuccess {String} status The resulting status of the request
 * @apiSuccess {String} message The message about the result of the request
 * @apiSuccess {String} data Data with the exchange values
 *
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
 *                  "PLNxEUR": 0.24,
 *                  "ETHxEUR": 253.351,
 *                  "CREAxEUR": 0.131
 *                  }
 *    }
 *
 */

/**
 * @api {get} /exchange/v1/currencies Get currencies
 * @apiName GetCurrencies
 * @apiDescription Get available currencies
 * @apiVersion 1.0.0
 * @apiGroup Public
 *
 * @apiSuccess {String} status The resulting status of the request
 * @apiSuccess {String} message The message about the result of the request
 * @apiSuccess {String} data Data with the available currencies
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "ok",
 *          "message": "Currency information",
 *          "data":
 *                  {
 *                  "EUR",
 *                  "BTC",
 *                  "FAC",
 *                  "MXN",
 *                  "PLN",
 *                  "USD",
 *                  "CREA",
 *                  "ETH"
 *                  }
 *    }
 *
 */


//##################################### CRYPTOCURRENCY PAYMENT ###################################

/**
 *
 * @api {post} /methods/v1/in/:currency Cryptocurrency payment
 * @apiName CryptoIn
 * @apiDescription Creates an new Cryptocurrency payment
 * @apiVersion 1.0.0
 * @apiGroup Methods
 * @apiUse OAuth2Header
 *
 * @apiParam {String=btc,fac,eth,crea} currency The cryptocurrency
 * @apiParam {Integer} amount The amount in <code>satoshis</code>
 * @apiParam {Integer} confirmations The minimum confirmations to validate the payment
 * @apiParam {Integer} expires_in The timeout (in seconds) of the payment
 * @apiParam {Text} concept Transaction concept send by user
 * @apiParam {Text} [notification_url] The notification <code>url</code> to notify about the payment result
 * @apiParam {String=BTC,EUR,FAC,PLN,MXN,ETH,CREA} [currency_in The currency of the <code>amount</code> parameter
 * @apiParam {String=BTC,EUR,FAC,PLN,MXN,ETH,CREA} [currency_out] After payment, the amount is automatically changed to this currency in your account.
 *
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} message The message about the result of the request
 * @apiSuccess {String} id The ID of the transaction
 * @apiSuccess {Integer} amount The amount in <code>satoshis</code> of the transaction
 * @apiSuccess {Integer=8} scale The number of decimals to represent the amount
 * @apiSuccess {String="BTC"} currency The currency
 * @apiSuccess {String} created The datetime when the transaction was created
 * @apiSuccess {String} updated The datetime when the transaction was updated
 * @apiSuccess {Object} pay_in_info The cryptocurrency payment related data of the transaction
 *
 * @apiUse NotAuthenticated
 * @apiUse Forbidden
 *
 */

/**
 * @api {get} /methods/v1/in/:currency/:id Cryptocurrency check payment
 * @apiName CryptoInCheck
 * @apiDescription Checks a Cryptocurrency payment
 * @apiVersion 1.0.0
 * @apiGroup Methods
 * @apiUse OAuth2Header
 *
 * @apiParam {String=btc,fac,eth,crea} currency The cryptocurrency
 * @apiParam {String} id The transaction <code>id</code>
 *
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} message The message about the result of the request
 * @apiSuccess {String} id The ID of the transaction
 * @apiSuccess {Integer} amount The amount in <code>satoshis</code> of the transaction
 * @apiSuccess {Integer=8} scale The number of decimals to represent the amount
 * @apiSuccess {String=BTC} currency The currency
 * @apiSuccess {DateTime} created The datetime when the transaction was created
 * @apiSuccess {DateTime} updated The datetime when the transaction was updated
 * @apiSuccess {Object} pay_in_info The cryptocurrency payment related data of the transaction
 *
 * @apiUse NotAuthenticated
 *
 */


//##################################### HALCASH_SEND ###################################

/**
 *
 * @api {post} /methods/v1/out/halcash_es HalCash send (Spain)
 * @apiName HalcashEsOut
 * @apiDescription Sends money via HalCash to any number in <code>Spain</code>.
 * @apiVersion 1.0.0
 * @apiGroup Methods
 * @apiUse OAuth2Header
 *
 * @apiParam {Integer} amount The desired amount in <code>EUR</code> <code>cents</code>
 * @apiParam {String} prefix="+34" The prefix of the target phone
 * @apiParam {Integer} phone The target phone number
 * @apiParam {Integer} pin The issuer pin (must have 4 digits)
 * @apiParam {String} concept The issuer text to include in the SMS (max length: 17 chars)
 *
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} message The message about the result of the request
 * @apiSuccess {String} id The ID of the transaction
 * @apiSuccess {Integer} amount The amount in <code>cents</code> of the transaction
 * @apiSuccess {Integer=2} scale The number of decimals to represent the amount
 * @apiSuccess {String=EUR} currency The currency
 * @apiSuccess {Object} pay_out_info The halcash related data of the transaction
 *
 * @apiUse NotAuthenticated
 * @apiUse Forbidden
 *
 */

/**
 *
 * @api {get} /methods/v1/out/halcash_es HalCash check
 * @apiName HalcashEsOutCheck
 * @apiDescription Checks the status of the halcash transaction
 * @apiVersion 1.0.0
 * @apiGroup Methods
 * @apiUse OAuth2Header
 *
 * @apiParam {String} id The transaction <code>id</code>
 *
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} message The message about the result of the request
 * @apiSuccess {String} id The ID of the transaction
 * @apiSuccess {Integer} amount The amount in <code>cents</code> of the transaction
 * @apiSuccess {Integer=2} scale The number of decimals to represent the amount
 * @apiSuccess {String=EUR} currency The currency
 * @apiSuccess {Object} pay_out_info The halcash related data of the transaction
 *
 * @apiUse NotAuthenticated
 *
 */

//##################################### PAYNET_REFERENCE ###################################

/**
 *
 * @api {post} /methods/v1/in/paynet_reference Paynet Reference
 * @apiName PaynetReferenceIn
 * @apiDescription Receive payments with Paynet.
 * @apiVersion 1.0.0
 * @apiGroup Methods
 * @apiUse OAuth2Header
 *
 * @apiParam {Integer} amount The desired amount in <code>MXN</code> <code>cents</code>
 * @apiParam {String} concept The issuer text to include in the transaction description
 *
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} message The message about the result of the request
 * @apiSuccess {String} id The ID of the transaction
 * @apiSuccess {String} amount The amount in <code>cents</code> of the transaction
 * @apiSuccess {Integer=2} scale The number of decimals to represent the amount
 * @apiSuccess {String=MXN} currency The currency
 * @apiSuccess {Object} pay_in_info The paynet reference related data of the transaction
 *
 * @apiUse NotAuthenticated
 * @apiUse Forbidden
 *
 */

/**
 *
 * @api {get} /services/v1/paynet_reference/:id Paynet Reference Check
 * @apiName PaynetReferenceCheck
 * @apiDescription Checks the status of the paynet reference transaction
 * @apiVersion 1.0.0
 * @apiGroup Methods
 * @apiUse OAuth2Header
 *
 * @apiParam {String} id The transaction <code>id</code>
 *
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} message The message about the result of the request
 * @apiSuccess {String} id The ID of the transaction
 * @apiSuccess {Integer} amount The amount in <code>cents</code> of the transaction
 * @apiSuccess {Integer=2} scale The number of decimals to represent the amount
 * @apiSuccess {String=MXN} currency The currency
 * @apiSuccess {Object} pay_in_info The paynet reference related data of the transaction
 *
 * @apiUse NotAuthenticated
 *
 */






