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


//##################################### BTC_PAY ###################################

/**
 *
 * @api {post} /services/v1/btc_pay Bitcoin pay
 * @apiName BtcPayCreate
 * @apiDescription Creates an new Bitcoin payment
 * @apiVersion 0.1.0
 * @apiGroup Services
 * @apiUse OAuth2Header
 * @apiParam {Integer} amount The amount in <code>satoshis</code>
 * @apiParam {Integer} confirmations The minimum confirmations to validate the payment
 * @apiParam {Integer} [notification_url] The notification <code>url</code> to notify about the payment result
 * @apiSuccess {Integer} code The HTTP code of the result
 * @apiSuccess {String} message The message about the result of the request
 * @apiSuccess {Object} data The data of the request
 * @apiSuccess {Integer} data.id The transaction <code>id</code>
 * @apiSuccess {Integer} data.amount The amount to send in <code>satoshis</code>
 * @apiSuccess {String} data.address The bitcoin address to send the money
 * @apiSuccess {Integer} data.min_confirmations The minimum confirmations to validate the payment
 * @apiSuccess {Integer} data.expires_in The timeout to expire the transaction if no paid is detected before
 * @apiUse NotAuthenticated
 * @apiUse Error503
 *
 */

/**
 *
 * @api {post} /services/v2/btc_pay Bitcoin pay
 * @apiName BtcPayCreate
 * @apiDescription Creates an new Bitcoin payment
 * @apiVersion 0.2.0
 * @apiGroup Services
 * @apiUse OAuth2Header
 * @apiParam {Integer} amount The amount in <code>satoshis</code>
 * @apiParam {Integer} confirmations The minimum confirmations to validate the payment
 * @apiParam {Integer} expires_in The timeout (in seconds) of the payment
 * @apiParam {Integer} [notification_url] The notification <code>url</code> to notify about the payment result
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} message The message about the result of the request
 * @apiSuccess {String} id The ID of the transaction
 * @apiSuccess {Integer} amount The amount in <code>satoshis</code> of the transaction
 * @apiSuccess {Integer=8} scale The number of decimals to represent the amount
 * @apiSuccess {String="BTC"} currency The currency
 * @apiSuccess {Object} data The bitcoin-payment related data of the transaction
 * @apiSuccess {String} data.address The bitcoin address to send the money
 * @apiSuccess {Integer} data.min_confirmations The minimum confirmations to validate the payment
 * @apiSuccess {Integer} data.expires_in The timeout to expire the transaction if no paid is detected before
 * @apiUse NotAuthenticated
 * @apiUse Error503
 *
 */

/**
 * @api {get} /services/v1/btc_pay/:id Bitcoin check
 * @apiName BtcPayCheck
 * @apiDescription Checks a Bitcoin transaction
 * @apiVersion 0.1.0
 * @apiGroup Services
 * @apiUse OAuth2Header
 * @apiParam {String} id The transaction <code>id</code>
 * @apiSuccess {Integer} code The HTTP code of the result
 * @apiSuccess {Integer} message The message about the result of the request
 * @apiSuccess {Object} data The data of the request
 * @apiSuccess {Integer} data.id The transaction <code>id</code>
 * @apiSuccess {Integer} data.amount The amount to send in <code>satoshis</code>
 * @apiSuccess {Integer} data.received The received <code>satoshis</code>
 * @apiSuccess {String} data.address The bitcoin address to send the money
 * @apiSuccess {Integer} data.min_confirmations The minimum confirmations to validate the payment
 * @apiSuccess {Integer} data.confirmations The confirmations seen in the payment, if the payment has not seen yet,
 * it returns <code>-1</code>
 * @apiSuccess {Integer} data.expires_in The timeout to expire the transaction if no paid is detected before
 * @apiUse NotAuthenticated
 *
 *
 */

/**
 * @api {get} /services/v2/btc_pay/:id Bitcoin check
 * @apiName BtcPayCheck
 * @apiDescription Checks a Bitcoin transaction
 * @apiVersion 0.2.0
 * @apiGroup Services
 * @apiUse OAuth2Header
 * @apiParam {String} id The transaction <code>id</code>
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} message The message about the result of the request
 * @apiSuccess {String} id The ID of the transaction
 * @apiSuccess {Integer} amount The total amount to send in <code>satoshis</code>
 * @apiSuccess {Integer=8} scale The number of decimals to represent the amount
 * @apiSuccess {String="BTC"} currency The currency
 * @apiSuccess {Object} data The data of the request
 * @apiSuccess {Integer} data.received The received money in <code>satoshis</code>
 * @apiSuccess {String} data.address The bitcoin address to send the money
 * @apiSuccess {Integer} data.min_confirmations The minimum confirmations to validate the payment
 * @apiSuccess {Integer} [data.confirmations] The confirmations seen in the payment, if the payment has not seen yet,
 * it is not returned
 * @apiSuccess {Integer} data.expires_in The timeout to expire the transaction if no payment is detected before
 * @apiUse NotAuthenticated
 *
 */

//##################################### FAC_PAY ###################################
/**
 *
 * @api {post} /services/v1/fac_pay Faircoin pay
 * @apiName FacPayCreate
 * @apiDescription Creates an new Faircoin payment
 * @apiVersion 0.1.0
 * @apiGroup Services
 * @apiUse OAuth2Header
 * @apiParam {Integer} amount The amount in <code>satoshis</code>
 * @apiParam {Integer} confirmations The minimum confirmations to validate the payment
 * @apiParam {Integer} [notification_url] The notification <code>url</code> to notify about the payment result
 * @apiSuccess {Integer} code The HTTP code of the result
 * @apiSuccess {String} message The message about the result of the request
 * @apiSuccess {Object} data The data of the request
 * @apiSuccess {Integer} data.id The transaction <code>id</code>
 * @apiSuccess {Integer} data.amount The amount to send in <code>satoshis</code>
 * @apiSuccess {String} data.address The faircoin address to send the money
 * @apiSuccess {Integer} data.min_confirmations The minimum confirmations to validate the payment
 * @apiSuccess {Integer} data.expires_in The timeout to expire the transaction if no paid is detected before
 * @apiUse NotAuthenticated
 * @apiUse Error503
 *
 */

/**
 *
 * @api {post} /services/v2/fac_pay Faircoin pay
 * @apiName FacPayCreate
 * @apiDescription Creates an new Faircoin payment
 * @apiVersion 0.2.0
 * @apiGroup Services
 * @apiUse OAuth2Header
 * @apiParam {Integer} amount The amount in <code>satoshis</code>
 * @apiParam {Integer} confirmations The minimum confirmations to validate the payment
 * @apiParam {Integer} expires_in The timeout (in seconds) of the payment
 * @apiParam {Integer} [notification_url] The notification <code>url</code> to notify about the payment result
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} message The message about the result of the request
 * @apiSuccess {String} id The ID of the transaction
 * @apiSuccess {Integer} amount The amount in <code>satoshis</code> of the transaction
 * @apiSuccess {Integer=8} scale The number of decimals to represent the amount
 * @apiSuccess {String="FAC"} currency The currency
 * @apiSuccess {Object} data The faircoin-payment related data of the transaction
 * @apiSuccess {String} data.address The faircoin address to send the money
 * @apiSuccess {Integer} data.min_confirmations The minimum confirmations to validate the payment
 * @apiSuccess {Integer} data.expires_in The timeout to expire the transaction if no paid is detected before
 * @apiUse NotAuthenticated
 * @apiUse Error503
 *
 */

/**
 * @api {get} /services/v1/fac_pay/:id Faircoin check
 * @apiName FacPayCheck
 * @apiDescription Checks a Faircoin transaction
 * @apiVersion 0.1.0
 * @apiGroup Services
 * @apiUse OAuth2Header
 * @apiParam {String} id The transaction <code>id</code>
 * @apiSuccess {Integer} code The HTTP code of the result
 * @apiSuccess {Integer} message The message about the result of the request
 * @apiSuccess {Object} data The data of the request
 * @apiSuccess {Integer} data.id The transaction <code>id</code>
 * @apiSuccess {Integer} data.amount The amount to send in <code>satoshis</code>
 * @apiSuccess {Integer} data.received The received <code>satoshis</code>
 * @apiSuccess {String} data.address The faircoin address to send the money
 * @apiSuccess {Integer} data.min_confirmations The minimum confirmations to validate the payment
 * @apiSuccess {Integer} data.confirmations The confirmations seen in the payment, if the payment has not seen yet,
 * it returns <code>-1</code>
 * @apiSuccess {Integer} data.expires_in The timeout to expire the transaction if no paid is detected before
 * @apiUse NotAuthenticated
 *
 *
 */

/**
 * @api {get} /services/v2/fac_pay/:id Faircoin check
 * @apiName FacPayCheck
 * @apiDescription Checks a Faircoin transaction
 * @apiVersion 0.2.0
 * @apiGroup Services
 * @apiUse OAuth2Header
 * @apiParam {String} id The transaction <code>id</code>
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} message The message about the result of the request
 * @apiSuccess {String} id The ID of the transaction
 * @apiSuccess {Integer} amount The total amount to send in <code>satoshis</code>
 * @apiSuccess {Integer=8} scale The number of decimals to represent the amount
 * @apiSuccess {String="FAC"} currency The currency
 * @apiSuccess {Object} data The data of the request
 * @apiSuccess {Integer} data.received The received money in <code>satoshis</code>
 * @apiSuccess {String} data.address The faircoin address to send the money
 * @apiSuccess {Integer} data.min_confirmations The minimum confirmations to validate the payment
 * @apiSuccess {Integer} [data.confirmations] The confirmations seen in the payment, if the payment has not seen yet,
 * it is not returned
 * @apiSuccess {Integer} data.expires_in The timeout to expire the transaction if no payment is detected before
 * @apiUse NotAuthenticated
 *
 */


/**
 *
 * @api {post} /services/v3/halcash_send HalCash send
 * @apiName HalcashSend
 * @apiDescription Sends money via HalCash to any number in Spain and Poland.
 * @apiVersion 0.3.0
 * @apiGroup Services
 * @apiUse OAuth2Header
 * @apiParam {Integer} amount The desired amount in <code>EUR</code> or <code>PLN</code> <code>cents</code>
 * @apiParam {String="ES, PL"} country The withdrawal country
 * @apiParam {String} prefix="+34" The prefix of the target phone
 * @apiParam {Integer} phone_number The target phone number
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} message The message about the result of the request
 * @apiSuccess {String} id The ID of the transaction
 * @apiSuccess {Integer} amount The total amount to send in <code>EUR</code> or <code>PLN</code> <code>cents</code>
 * @apiSuccess {Integer=2} scale The number of decimals to represent the amount
 * @apiSuccess {String="EUR, PLN"} currency The currency
 * @apiSuccess {Object} data The data of the request
 * @apiSuccess {Integer} data.errorcode=0 The received halcash error
 * @apiSuccess {String} data.halcashticket The received halcash ticket
 * @apiUse NotAuthenticated
 *
 */

/**
 *
 * @api {get} /services/v3/halcash_send HalCash check
 * @apiName HalcashCheck
 * @apiDescription Checks the status of the halcash transaction
 * @apiVersion 0.3.0
 * @apiGroup Services
 * @apiUse OAuth2Header
 * @apiParam {String} id The transaction <code>id</code>
 * @apiSuccess {String="sent, consumed, cancelled, error"} status The status of the transaction
 * @apiSuccess {String} message The message about the result of the request
 * @apiSuccess {String} id The ID of the transaction
 * @apiSuccess {Integer} amount The total amount to send in <code>EUR</code> or <code>PLN</code> <code>cents</code>
 * @apiSuccess {Integer=2} scale The number of decimals to represent the amount
 * @apiSuccess {String="EUR, PLN"} currency The currency
 * @apiSuccess {Object} data The data of the request
 * @apiSuccess {Integer} data.errorcode=0 The received halcash error
 * @apiSuccess {String} data.halcashticket The received halcash ticket
 * @apiUse NotAuthenticated
 *
 */
