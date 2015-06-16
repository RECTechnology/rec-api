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
 * @apiSuccess {Decimal} price Bitcoin price in EUR
 * @apiSuccess {Decimal} variable_fee Variable fee in parts per unit
 * @apiSuccess {Decimal} fixed_fee Fixed fee in EUR
 * @apiSuccess {Integer} timeout Expiration time for the transaction
 * @apiSuccess {Integer} daily_limit Day limit
 * @apiSuccess {Integer} monthly_limit Month limit
 * @apiSuccess {Integer} confirmations Minimum number of confirmations
 * @apiSuccess {String} terms Url where you can find the terms and conditions
 * @apiSuccess {String} title App name
 *
 */

/**
 * @api {get} /api/v2/hello Read configuration
 * @apiName GetConfiguration
 * @apiDescription Read configuration service
 * @apiVersion 2.0.0
 * @apiGroup Swift
 * @apiSuccess price Bitcoin prices
 * @apiSuccess price.EUR In EUR
 * @apiSuccess price.PLN In PLN
 * @apiSuccess variable_fee Variable fee in parts per unit
 * @apiSuccess fixed_fee Fixed fee in EUR
 * @apiSuccess timeout Expiration time for the transaction
 * @apiSuccess daily_limit Day limit
 * @apiSuccess monthly_limit Month limit
 * @apiSuccess confirmations Minimum number of confirmations
 * @apiSuccess {String} terms Url where you can find the terms and conditions
 * @apiSuccess {String} title App name
 *
 */

/**
 * @api {get} /api/v3/hello Read configuration
 * @apiName GetConfiguration
 * @apiDescription Read configuration service
 * @apiVersion 3.0.0
 * @apiGroup Swift
 * @apiSuccess price Bitcoin prices
 * @apiSuccess price.eur In EUR
 * @apiSuccess price.pln In PLN
 * @apiSuccess price.mxn In MXN
 * @apiSuccess limits Limits per currency
 * @apiSuccess limits.day Day limits
 * @apiSuccess limits.day.EUR For EUR
 * @apiSuccess limits.day.PLN For PLN
 * @apiSuccess limits.month Month limits
 * @apiSuccess limits.month.EUR For EUR
 * @apiSuccess limits.month.PLN For PLN
 * @apiSuccess values Allowed values
 * @apiSuccess values.EUR For EUR transactions
 * @apiSuccess values.PLN For PLN transactions
 * @apiSuccess values.MXN For MXN transactions
 * @apiSuccess fees Fees per currency
 * @apiSuccess fees.fixed Fixed fees
 * @apiSuccess fees.fixed.EUR In EUR
 * @apiSuccess fees.fixed.PLN In PLN
 * @apiSuccess fees.fixed.MXN In MXN
 * @apiSuccess fees.variable Variable fees in parts per unit
 * @apiSuccess fees.variable.EUR For EUR
 * @apiSuccess fees.variable.PLN For PLN
 * @apiSuccess fees.variable.MXN For MXN
 * @apiSuccess timeout Expiration time for the transaction
 * @apiSuccess confirmations Minimum number of confirmations
 * @apiSuccess {String} terms Url where you can find the terms and conditions
 * @apiSuccess {String} title App name
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
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} id The ID of the transaction
 * @apiSuccess {String} address BTC address where the user must send the BTC
 * @apiSuccess {String} amount Btc transaction amount in <code>satoshis</code>
 * @apiSuccess {Integer} pin Number with four digits
 * @apiSuccess {String} ticket_id Transaction ticket id
 *
 */

//##################################### CHECK BTC ######################################

/**
 * @api {get} /api/check/:id Bitcoin check
 * @apiName GetTransaction
 * @apiDescription Check a BTC transaction by transaction_id
 * @apiVersion 1.0.0
 * @apiGroup Swift
 * @apiSuccess status The resulting status of the transaction
 * @apiSuccess confirmations Number of confirmations
 * @apiSuccess btc Transaction amount in BTC
 * @apiSuccess expired True or false
 * @apiSuccess ticket_id Transaction ticket id
 *
 */