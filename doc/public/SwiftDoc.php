<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/4/15
 * Time: 2:37 AM
 */


//##################################### HELLO BTC ######################################

/**
 * @api {get} /api/hello Read configuration
 * @apiName GetConfiguration
 * @apiDescription Read configuration service
 * @apiVersion 1.0.0
 * @apiGroup Swift
 * @apiSuccess price
 * @apiSuccess variable_fee
 * @apiSuccess fixed_fee
 * @apiSuccess timeout
 * @apiSuccess daily_limit
 * @apiSuccess monthly_limit
 * @apiSuccess confirmations
 * @apiSuccess terms
 * @apiSuccess title
 *
 */

/**
 * @api {get} /api/v2/hello Read configuration
 * @apiName GetConfiguration
 * @apiDescription Read configuration service
 * @apiVersion 2.0.0
 * @apiGroup Swift
 * @apiSuccess price
 * @apiSuccess price.EUR
 * @apiSuccess price.PLN
 * @apiSuccess variable_fee
 * @apiSuccess fixed_fee
 * @apiSuccess timeout
 * @apiSuccess daily_limit
 * @apiSuccess monthly_limit
 * @apiSuccess confirmations
 * @apiSuccess terms
 * @apiSuccess title
 *
 */

/**
 * @api {get} /api/v3/hello Read configuration
 * @apiName GetConfiguration
 * @apiDescription Read configuration service
 * @apiVersion 3.0.0
 * @apiGroup Swift
 * @apiSuccess price
 * @apiSuccess price.eur
 * @apiSuccess price.pln
 * @apiSuccess price.mxn
 * @apiSuccess limits
 * @apiSuccess limits.day
 * @apiSuccess limits.day.EUR
 * @apiSuccess limits.day.PLN
 * @apiSuccess limits.month
 * @apiSuccess limits.month.EUR
 * @apiSuccess limits.month.PLN
 * @apiSuccess values
 * @apiSuccess values.EUR
 * @apiSuccess values.PLN
 * @apiSuccess values.MXN
 * @apiSuccess fees
 * @apiSuccess fees.fixed
 * @apiSuccess fees.fixed.EUR
 * @apiSuccess fees.fixed.PLN
 * @apiSuccess fees.fixed.MXN
 * @apiSuccess fees.variable
 * @apiSuccess fees.variable.EUR
 * @apiSuccess fees.variable.PLN
 * @apiSuccess fees.variable.MXN
 * @apiSuccess timeout
 * @apiSuccess confirmations
 * @apiSuccess terms
 * @apiSuccess title
 *
 */


//##################################### CREATE BTC ######################################

/**
 * @api {post} /api/send Bitcoin pay
 * @apiName CreateTransaction
 * @apiDescription Creates a new BTC transaction
 * @apiVersion 1.0.0
 * @apiGroup Swift
 * @apiParam {String} country Country service ES or PL
 * @apiParam {Integer} amount Transaction amount in EUR
 * @apiParam {Integer} phone_number Phone number to send Hal
 * @apiParam {Integer} prefix Prefix phone number
 * @apiSuccess status
 * @apiSuccess id
 * @apiSuccess address
 * @apiSuccess amount
 * @apiSuccess pin
 * @apiSuccess ticket_id
 *
 */

//##################################### CHECK BTC ######################################

/**
 * @api {get} /api/check/{id} Bitcoin check
 * @apiName GetTransaction
 * @apiDescription Check a BTC transaction by transaction_id
 * @apiVersion 1.0.0
 * @apiGroup Swift
 * @apiSuccess status
 * @apiSuccess confirmations
 * @apiSuccess btc
 * @apiSuccess expired
 * @apiSuccess ticket_id
 *
 */