<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/4/15
 * Time: 2:37 AM
 */


//##################################### HELLO BTC ######################################

/**
 * @api {get} /api/hello Bitcoin configuration
 * @apiName GetConfiguration
 * @apiDescription Read bitcoin configuration service
 * @apiVersion 1.0.0
 * @apiGroup Swift
 * @apiSuccess {Float} price Bitcoin price in EUR
 * @apiSuccess {Float} variable_fee Variable fee in parts per unit
 * @apiSuccess {Float} fixed_fee Fixed fee in EUR
 * @apiSuccess {Integer} timeout Expiration time for the transaction
 * @apiSuccess {Integer} daily_limit Day limit
 * @apiSuccess {Integer} monthly_limit Month limit
 * @apiSuccess {Integer} confirmations Minimum number of confirmations
 * @apiSuccess {String} terms Url where you can find the terms and conditions
 * @apiSuccess {String} title App name
 *
 */

/**
 * @api {get} /api/v2/hello Bitcoin configuration
 * @apiName GetConfiguration
 * @apiDescription Read bitcoin configuration service
 * @apiVersion 2.0.0
 * @apiGroup Swift
 * @apiSuccess price Bitcoin prices
 * @apiSuccess {Float} price.EUR In EUR
 * @apiSuccess {Float} price.PLN In PLN
 * @apiSuccess {Float} variable_fee Variable fee in parts per unit
 * @apiSuccess {Float} fixed_fee Fixed fee in EUR
 * @apiSuccess {Integer} timeout Expiration time for the transaction
 * @apiSuccess {Integer} daily_limit Day limit
 * @apiSuccess {Integer} monthly_limit Month limit
 * @apiSuccess {Integer} confirmations Minimum number of confirmations
 * @apiSuccess {String} terms Url where you can find the terms and conditions
 * @apiSuccess {String} title App name
 *
 */

/**
 * @api {get} /api/v3/hello Bitcoin configuration
 * @apiName GetConfiguration
 * @apiDescription Read bitcoin configuration service
 * @apiVersion 3.0.0
 * @apiGroup Swift
 * @apiSuccess price Bitcoin prices
 * @apiSuccess {Float} price.eur In EUR
 * @apiSuccess {Float} price.pln In PLN
 * @apiSuccess {Float} price.mxn In MXN
 * @apiSuccess limits Limits per currency
 * @apiSuccess {Integer} limits.day Day limits
 * @apiSuccess {Integer} limits.day.EUR For EUR
 * @apiSuccess {Integer} limits.day.PLN For PLN
 * @apiSuccess {Integer} limits.month Month limits
 * @apiSuccess {Integer} limits.month.EUR For EUR
 * @apiSuccess {Integer} limits.month.PLN For PLN
 * @apiSuccess values Allowed values
 * @apiSuccess {Integer[]} values.EUR For EUR transactions. Array of integers
 * @apiSuccess {Integer[]} values.PLN For PLN transactions. Array of integers
 * @apiSuccess {Integer[]} values.MXN For MXN transactions. Array of integers
 * @apiSuccess fees Fees per currency
 * @apiSuccess {Float} fees.fixed Fixed fees
 * @apiSuccess {Float} fees.fixed.EUR In EUR
 * @apiSuccess {Float} fees.fixed.PLN In PLN
 * @apiSuccess {Float} fees.fixed.MXN In MXN
 * @apiSuccess fees.variable Variable fees in parts per unit
 * @apiSuccess {Float} fees.variable.EUR For EUR
 * @apiSuccess {Float} fees.variable.PLN For PLN
 * @apiSuccess {Float} fees.variable.MXN For MXN
 * @apiSuccess {Integer} timeout Expiration time for the transaction
 * @apiSuccess {Integer} confirmations Minimum number of confirmations
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
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {Integer} confirmations Number of confirmations
 * @apiSuccess {Integer} btc Transaction amount in <code>satoshis</code>
 * @apiSuccess {Boolean} expired True or false
 * @apiSuccess {String} ticket_id Transaction ticket id
 *
 */

//##################################### HELLO FAC ######################################

/**
 * @api {get} /api/fac/hello Faircoin configuration
 * @apiName GetConfiguration
 * @apiDescription Read faircoin configuration service
 * @apiVersion 1.0.0
 * @apiGroup Swift
 * @apiSuccess {Float} price Faircoin price in EUR
 * @apiSuccess {Float} variable_fee Variable fee in parts per unit
 * @apiSuccess {Float} fixed_fee Fixed fee in EUR
 * @apiSuccess {Integer} timeout Expiration time for the transaction
 * @apiSuccess {Float} daily_limit Day limit
 * @apiSuccess {Float} monthly_limit Month limit
 * @apiSuccess {Integer} confirmations Minimum number of confirmations
 * @apiSuccess {String} terms Url where you can find the terms and conditions
 * @apiSuccess {String} title App name
 *
 */

/**
 * @api {get} /api/v2/fac/hello Faircoin configuration
 * @apiName GetConfiguration
 * @apiDescription Read faircoin configuration service
 * @apiVersion 2.0.0
 * @apiGroup Swift
 * @apiSuccess price Faircoin prices
 * @apiSuccess {Float} price.EUR In EUR
 * @apiSuccess {Float} price.PLN In PLN
 * @apiSuccess {Float} variable_fee Variable fee in parts per unit
 * @apiSuccess {Float} fixed_fee Fixed fee in EUR
 * @apiSuccess {Integer} timeout Expiration time for the transaction
 * @apiSuccess {Float} daily_limit Day limit
 * @apiSuccess {Float} monthly_limit Month limit
 * @apiSuccess {Integer} confirmations Minimum number of confirmations
 * @apiSuccess {String} terms Url where you can find the terms and conditions
 * @apiSuccess {String} title App name
 *
 */


/**
 * @api {get} /api/v3/fac/hello Faircoin configuration
 * @apiName GetConfiguration
 * @apiDescription Read faircoin configuration service
 * @apiVersion 3.0.0
 * @apiGroup Swift
 * @apiSuccess price Faircoin prices
 * @apiSuccess {Float} price.eur In EUR
 * @apiSuccess {Float} price.pln In PLN
 * @apiSuccess {Float} price.mxn In MXN
 * @apiSuccess limits Limits per currency
 * @apiSuccess limits.day Day limits
 * @apiSuccess {Float} limits.day.EUR For EUR
 * @apiSuccess {Float} limits.day.PLN For PLN
 * @apiSuccess limits.month Month limits
 * @apiSuccess {Float} limits.month.EUR For EUR
 * @apiSuccess {Float} limits.month.PLN For PLN
 * @apiSuccess values Allowed values
 * @apiSuccess {Integer[]} values.EUR For EUR transactions
 * @apiSuccess {Integer[]} values.PLN For PLN transactions
 * @apiSuccess {Integer[]} values.MXN For MXN transactions
 * @apiSuccess fees Fees per currency
 * @apiSuccess fees.fixed Fixed fees
 * @apiSuccess {Float} fees.fixed.EUR In EUR
 * @apiSuccess {Float} fees.fixed.PLN In PLN
 * @apiSuccess {Float} fees.fixed.MXN In MXN
 * @apiSuccess fees.variable Variable fees in parts per unit
 * @apiSuccess {Float} fees.variable.EUR For EUR
 * @apiSuccess {Float} fees.variable.PLN For PLN
 * @apiSuccess {Float} fees.variable.MXN For MXN
 * @apiSuccess {Integer} timeout Expiration time for the transaction
 * @apiSuccess {Integer} confirmations Minimum number of confirmations
 * @apiSuccess {String} terms Url where you can find the terms and conditions
 * @apiSuccess {String} title App name
 *
 */

//##################################### CREATE FAC ######################################

/**
 * @api {post} /api/fac/send Faircoin pay
 * @apiName CreateTransaction
 * @apiDescription Creates a new FAC transaction
 * @apiVersion 1.0.0
 * @apiGroup Swift
 * @apiParam {String} country Country service ES or PL
 * @apiParam {Integer} amount Transaction amount in EUR
 * @apiParam {Integer} phone_number Phone number to send Hal
 * @apiParam {Integer} prefix Prefix phone number
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} id The ID of the transaction
 * @apiSuccess {String} address FAC address where the user must send the FAC
 * @apiSuccess {String} amount FAC transaction amount in <code>microFaircoin</code>
 * @apiSuccess {Integer} pin Number with four digits
 * @apiSuccess {String} ticket_id Transaction ticket id
 *
 */

//##################################### CHECK FAC ######################################

/**
 * @api {get} /api/fac/check/:id Faircoin check
 * @apiName GetTransaction
 * @apiDescription Check a FAC transaction by transaction_id
 * @apiVersion 1.0.0
 * @apiGroup Swift
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {Integer} confirmations Number of confirmations
 * @apiSuccess {Integer} fac Transaction amount in FAC
 * @apiSuccess {Boolean} expired True or false
 * @apiSuccess {String} ticket_id Transaction ticket id
 *
 */

//##################################### CREATE PAYNET/BTC ######################################

/**
 * @api {post} /api/paynet/btc Paynet-Bitcoin
 * @apiName CreatePaynetTransaction
 * @apiDescription Creates a new Paynet-Btc transaction
 * @apiVersion 1.0.0
 * @apiGroup Swift
 * @apiParam {String} description Transaction description
 * @apiParam {Integer} amount Transaction amount in MXN
 * @apiParam {Integer} btc_address Valid bitcoin address
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} id The ID of the transaction
 * @apiSuccess {String} amount Amount in MXN
 * @apiSuccess {String} barcode String to generate the barcode
 * @apiSuccess {String} url Url to show the barcode
 * @apiSuccess {String} expiration_date Expiration date for the payment
 * @apiSuccess {String} description Your own description sent before
 * @apiSuccess {String} ticket_id Transaction ticket id
 *
 */

//##################################### CREATE PAYNET/FAC ######################################

/**
 * @api {post} /api/fac/paynet Paynet-Faircoin
 * @apiName CreatePaynetTransaction
 * @apiDescription Creates a new Paynet-Fac transaction
 * @apiVersion 1.0.0
 * @apiGroup Swift
 * @apiParam {String} description Transaction description
 * @apiParam {Integer} amount Transaction amount in MXN
 * @apiParam {Integer} fac_address Valid bitcoin address
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} id The ID of the transaction
 * @apiSuccess {String} amount Amount in MXN
 * @apiSuccess {String} barcode String to generate the barcode
 * @apiSuccess {String} url Url to show the barcode
 * @apiSuccess {String} expiration_date Expiration date for the payment
 * @apiSuccess {String} description Your own description sent before
 * @apiSuccess {String} ticket_id Transaction ticket id
 *
 */