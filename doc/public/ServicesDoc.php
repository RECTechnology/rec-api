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
 *     "code": 503,
 *     "message": "Service temporarily unavailable, please try again in a few minutes"
 * }
 */



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
 * @apiSuccess {Integer} message The message about the result of the request
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
 * @api {get} /services/v1/btc_pay/:id Bitcoin check
 * @apiName BtcPayCheck
 * @apiDescription Checks a Bitcoin transaction
 * @apiVersion 0.1.0
 * @apiGroup Services
 * @apiUse OAuth2Header
 * @apiParam {Integer} id The transaction <code>id</code>
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
 *
 * @api {post} /services/v1/fac_pay Faircoin pay
 * @apiName FacPayCreate
 * @apiDescription Creates an new FairCoin payment
 * @apiVersion 0.1.0
 * @apiGroup Services
 * @apiUse OAuth2Header
 * @apiParam {Integer} amount The amount in <code>satoshis</code>
 * @apiParam {Integer} confirmations The minimum confirmations to validate the payment
 * @apiParam {Integer} [notification_url] The notification <code>url</code> to notify about the payment result
 * @apiSuccess {Integer} code The HTTP code of the result
 * @apiSuccess {Integer} message The message about the result of the request
 * @apiSuccess {Object} data The data of the request
 * @apiSuccess {Integer} data.id The transaction <code>id</code>
 * @apiSuccess {Integer} data.amount The amount to send in <code>satoshis</code>
 * @apiSuccess {String} data.address The bitcoin address to send the money
 * @apiSuccess {String} data.address The bitcoin address to send the money
 * @apiSuccess {Integer} data.expires_in The timeout to expire the transaction if no paid is detected before
 * @apiUse NotAuthenticated
 *
 */


/**
 *
 * @api {post} /services/v1/pagofacil Pago Facil
 * @apiName PagoFacil
 * @apiDescription Creates an new transaction with Pagofacil MXN provider
 * @apiVersion 1.0.0
 * @apiGroup Services
 *
 *
 *
 */


/**
 *
 * @api {post} /services/v1/toditocash ToditoCash
 * @apiName ToditoCash
 * @apiDescription Creates an new transaction with ToditoCash MXN provider
 * @apiVersion 1.0.0
 * @apiGroup Services
 *
 *
 *
 */


/**
 *
 * @api {post} /services/v1/halcash_send HalCash send
 * @apiName HalcashSend
 * @apiDescription Sends money via HalCash to any number in Spain and Mexico.
 * @apiVersion 1.0.0
 * @apiGroup Services
 *
 *
 *
 */


/**
 *
 * @api {post} /services/v1/halcash_pay Halcash payment
 * @apiName HalcashPay
 * @apiDescription Pay with HalCash in Mexico shops.
 * @apiVersion 1.0.0
 * @apiGroup Services
 *
 *
 *
 */