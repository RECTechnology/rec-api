<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/4/15
 * Time: 2:37 AM
 */

/**
 * @apiDefine TransactionNotFoundError
 *
 * @apiError error Error
 * @apiError message Description of the error
 *
 * @apiErrorExample Transaction not found
 *    HTTP/1.1 404: Not found
 *    {
 *          "status": "error",
 *          "message": "Transaction not found"
 *    }
 */


//##################################### HELLO BTC ######################################

/**
 * @api {get} /api/hello Hello Btc
 * @apiName Hello_btc
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
 * @api {get} /api/v2/hello Hello Btc
 * @apiName Hello_btc
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
 * @api {get} /api/v3/hello Hello Btc
 * @apiName Hello_btc
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
 * @api {post} /api/send Bitcoin to Halcash_ES
 * @apiName btc_halcash_es
 * @apiDescription Creates a new BTC transaction
 * @apiVersion 0.5.0
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

/**
 * @api {post} /swift/v1/btc/halcash_es Bitcoin to Halcash_ES
 * @apiName btc_halcash_es
 * @apiDescription Creates a new BTC transaction
 * @apiVersion 1.0.0
 * @apiGroup Swift
 * @apiParam {Integer} amount Transaction amount in EUR
 * @apiParam {Integer} phone Phone number to send Hal
 * @apiParam {Integer} prefix Prefix phone number
 * @apiParam {Text} description Description (optional)
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} message Information message
 * @apiSuccess {String} id Transaction id
 * @apiSuccess {String} amount Halcash amount in <code>cents</code>
 * @apiSuccess {Integer} scale scale
 * @apiSuccess {String} currency Halcash currency
 * @apiSuccess {DateTime} created When transaction was created
 * @apiSuccess {DateTime} updated When transaction was updated last time
 * @apiSuccess {Object} pay_in_info Btc payment info
 * @apiSuccess {String} pay_in_info.amount Btc transaction amount in <code>satoshis</code>
 * @apiSuccess {String} pay_in_info.currency Btc
 * @apiSuccess {Integer} pay_in_info.scale Btc scale
 * @apiSuccess {String} pay_in_info.address BTC address where the user must send the BTC
 * @apiSuccess {Integer} pay_in_info.expires_in Transaction expiration time
 * @apiSuccess {Integer} pay_in_info.received Received amount
 * @apiSuccess {Integer} pay_in_info.min_confirmations Minimum confirmations for validate transaction
 * @apiSuccess {Integer} pay_in_info.confirmations Confirmations for the current transaction
 * @apiSuccess {Integer} pay_in_info.status Bitcoin status payment
 * @apiSuccess {Object} pay_out_info Halcash info
 * @apiSuccess {String} pay_out_info.amount Halcash amount in <code>cents</code>
 * @apiSuccess {String} pay_out_info.currency Eur
 * @apiSuccess {Integer} pay_out_info.scale Eur scale
 * @apiSuccess {Integer} pay_out_info.phone Phone where the halcash was sent
 * @apiSuccess {Integer} pay_out_info.prefix Prefix for the phone number
 * @apiSuccess {Text} pay_out_info.description Halcash description
 * @apiSuccess {Integer} pay_out_info.pin Number with four digits
 * @apiSuccess {Boolean} pay_out_info.final Is final status?
 * @apiSuccess {String} pay_out_info.status Halcash status
 * @apiSuccess {String} pay_out_info.halcashticket Generated Halcashticket(only if the transaction was successful)
 *
 */


//##################################### CHECK BTC ######################################

/**
 * @api {get} /api/check/:id Bitcoin to Halcash_ES Check
 * @apiName btc_halcash_es_check
 * @apiDescription Check a BTC transaction by transaction_id
 * @apiVersion 0.1.0
 * @apiGroup Swift
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {Integer} confirmations Number of confirmations
 * @apiSuccess {Integer} btc Transaction amount in <code>satoshis</code>
 * @apiSuccess {Boolean} expired True or false
 * @apiSuccess {String} ticket_id Transaction ticket id
 *
 * @apiUse TransactionNotFoundError
 *
 */

/**
 * @api {get} /swift/v1/btc/halcash_es/:id Bitcoin to Halcash_ES Check
 * @apiName btc_halcash_es_check
 * @apiDescription Check a BTC transaction by transaction_id
 * @apiVersion 1.0.0
 * @apiGroup Swift
 * @apiParam {String} id Unique transaction id
 * @apiSuccess {String} status Current transaction status
 * @apiSuccess {String} message Information message
 * @apiSuccess {String} id Transaction id
 * @apiSuccess {String} amount Halcash amount in <code>cents</code>
 * @apiSuccess {Integer} scale scale
 * @apiSuccess {String} currency Halcash currency
 * @apiSuccess {DateTime} created When transaction was created
 * @apiSuccess {DateTime} updated When transaction was updated last time
 * @apiSuccess {Object} pay_in_info Btc payment info
 * @apiSuccess {String} pay_in_info.amount Btc transaction amount in <code>satoshis</code>
 * @apiSuccess {String} pay_in_info.currency Btc
 * @apiSuccess {Integer} pay_in_info.scale Btc scale
 * @apiSuccess {String} pay_in_info.address BTC address where the user must send the BTC
 * @apiSuccess {Integer} pay_in_info.expires_in Transaction expiration time
 * @apiSuccess {Integer} pay_in_info.received Received amount
 * @apiSuccess {Integer} pay_in_info.min_confirmations Minimum confirmations for validate transaction
 * @apiSuccess {Integer} pay_in_info.confirmations Confirmations for the current transaction
 * @apiSuccess {Integer} pay_in_info.status Bitcoin status payment
 * @apiSuccess {Object} pay_in_info.refund_info Only if the bitcoin transaction has been refund
 * @apiSuccess {Integer} pay_in_info.refund_info.amount Btc amount for the refund transaction
 * @apiSuccess {String} pay_in_info.refund_info.address Btc address where bitcoins were refund in <code>satoshis</code>
 * @apiSuccess {String} pay_in_info.refund_info.currency Btc
 * @apiSuccess {Integer} pay_in_info.refund_info.scale Btc scale
 * @apiSuccess {Boolean} pay_in_info.refund_info.final is final status?
 * @apiSuccess {String} pay_in_info.refund_info.status Refund transaction status
 * @apiSuccess {Object} pay_out_info Halcash info
 * @apiSuccess {String} pay_out_info.amount Halcash amount in <code>cents</code>
 * @apiSuccess {String} pay_out_info.currency Eur
 * @apiSuccess {Integer} pay_out_info.scale Eur scale
 * @apiSuccess {Integer} pay_out_info.phone Phone where the halcash was sent
 * @apiSuccess {Integer} pay_out_info.prefix Prefix for the phone number
 * @apiSuccess {Text} pay_out_info.description Halcash description
 * @apiSuccess {Integer} pay_out_info.pin Number with four digits
 * @apiSuccess {Boolean} pay_out_info.final Is final status?
 * @apiSuccess {String} pay_out_info.status Halcash status
 * @apiSuccess {String} pay_out_info.halcashticket Generated Halcashticket(only if the transaction was successful)
 *
 * @apiUse TransactionNotFoundError
 *
 */

//##################################### HELLO FAC ######################################

/**
 * @api {get} /api/fac/hello Hello Fac
 * @apiName Hello_fac
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
 * @api {get} /api/v2/fac/hello Hello Fac
 * @apiName Hello_fac
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
 * @api {get} /api/v3/fac/hello Hello Fac
 * @apiName Hello_fac
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
 * @api {post} /api/fac/send Faircoin to Halcash_ES
 * @apiName fac_halcash_es
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
 * @api {get} /api/fac/check/:id Faircoin to Halcash_ES Check
 * @apiName fac_halcash_es_check
 * @apiDescription Check a FAC transaction by transaction_id
 * @apiVersion 1.0.0
 * @apiGroup Swift
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {Integer} confirmations Number of confirmations
 * @apiSuccess {Integer} fac Transaction amount in FAC
 * @apiSuccess {Boolean} expired True or false
 * @apiSuccess {String} ticket_id Transaction ticket id
 *
 * @apiUse TransactionNotFoundError
 *
 */

//##################################### CREATE PAYNET/BTC ######################################

/**
 * @api {post} /api/paynet/btc Paynet to Bitcoin
 * @apiName paynet_btc
 * @apiDescription Creates a new Paynet-Btc transaction
 * @apiVersion 0.1.0
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

/**
 * @api {post} /swift/v1/paynet/btc Paynet to Bitcoin
 * @apiName paynet_btc
 * @apiDescription Creates a new Paynet-Btc transaction
 * @apiVersion 1.0.0
 * @apiGroup Swift
 * @apiParam {Integer} amount Transaction amount in <code>satoshis</code>
 * @apiParam {String} address Address where we have to send the bitcoins if transaction was successful
 * @apiParam {Text} description Description (optional)
 * @apiSuccess {String} status The resulting status for the transaction
 * @apiSuccess {String} message Information message
 * @apiSuccess {String} id Transaction id
 * @apiSuccess {String} amount Btc amount in <code>satoshis</code>
 * @apiSuccess {Integer} scale Btc scale
 * @apiSuccess {String} currency <code>BTC</code> currency
 * @apiSuccess {DateTime} created When transaction was created
 * @apiSuccess {DateTime} updated When transaction was updated last time
 * @apiSuccess {Object} pay_in_info Paynet payment info
 * @apiSuccess {String} pay_in_info.amount MXN transaction amount in <code>cents</code>
 * @apiSuccess {String} pay_in_info.currency <code>MXN</code>
 * @apiSuccess {Integer} pay_in_info.scale <code>MXN</code> scale
 * @apiSuccess {Date} pay_in_info.expires_in Transaction expiration date
 * @apiSuccess {Integer} pay_in_info.received Received amount
 * @apiSuccess {String} pay_in_info.barcode code for generate the barcode for the payment
 * @apiSuccess {String} pay_in_info.paynet_id transaction id generated by paynet
 * @apiSuccess {Integer} pay_in_info.status Paynet status payment
 * @apiSuccess {Object} pay_out_info Bitcoin transaction info
 * @apiSuccess {String} pay_out_info.amount Bitcoin amount in <code>satoshis</code>
 * @apiSuccess {String} pay_out_info.address Bitcoin address were bitcoin will be sent when the cash in is successful
 * @apiSuccess {String} pay_out_info.currency <code>BTC</code>
 * @apiSuccess {Integer} pay_out_info.scale <code>BTC</code> scale
 * @apiSuccess {Boolean} pay_out_info.final Is final status?
 * @apiSuccess {String} pay_out_info.status Btc transaction status
 *
 */

/**
 * @api {get} /swift/v1/paynet/btc/:id Paynet to Bitcoin Check
 * @apiName paynet_btc_check
 * @apiDescription Check Paynet-Btc transaction
 * @apiVersion 1.0.0
 * @apiGroup Swift
 * @apiParam {String} id Unique transaction id
 * @apiSuccess {String} status The resulting status for the transaction
 * @apiSuccess {String} message Information message
 * @apiSuccess {String} id Transaction id
 * @apiSuccess {String} amount Btc amount in <code>satoshis</code>
 * @apiSuccess {Integer} scale Btc scale
 * @apiSuccess {String} currency <code>BTC</code> currency
 * @apiSuccess {DateTime} created When transaction was created
 * @apiSuccess {DateTime} updated When transaction was updated last time
 * @apiSuccess {Object} pay_in_info Paynet payment info
 * @apiSuccess {String} pay_in_info.amount MXN transaction amount in <code>cents</code>
 * @apiSuccess {String} pay_in_info.currency <code>MXN</code>
 * @apiSuccess {Integer} pay_in_info.scale <code>MXN</code> scale
 * @apiSuccess {Date} pay_in_info.expires_in Transaction expiration date
 * @apiSuccess {Integer} pay_in_info.received Received amount
 * @apiSuccess {String} pay_in_info.barcode code for generate the barcode for the payment
 * @apiSuccess {String} pay_in_info.paynet_id transaction id generated by paynet
 * @apiSuccess {Integer} pay_in_info.status Paynet status payment
 * @apiSuccess {Object} pay_out_info Bitcoin transaction info
 * @apiSuccess {String} pay_out_info.amount Bitcoin amount in <code>satoshis</code>
 * @apiSuccess {String} pay_out_info.address Bitcoin address were bitcoin will be sent when the cash in is successful
 * @apiSuccess {String} pay_out_info.currency <code>BTC</code>
 * @apiSuccess {Integer} pay_out_info.scale <code>BTC</code> scale
 * @apiSuccess {Boolean} pay_out_info.final Is final status?
 * @apiSuccess {String} pay_out_info.status Btc transaction status
 *
 * @apiUse TransactionNotFoundError
 *
 */


//##################################### CREATE PAYNET/FAC ######################################

/**
 * @api {post} /api/fac/paynet Paynet to Faircoin
 * @apiName paynet_fac
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

/**
 * @api {post} /swift/v1/paynet/fac Paynet to Faircoin
 * @apiName paynet_fac
 * @apiDescription Creates a new Paynet-Fac transaction
 * @apiVersion 1.0.0
 * @apiGroup Swift
 * @apiParam {Integer} amount Transaction amount in <code>satoshis</code>
 * @apiParam {String} address Address where we have to send the bitcoins if transaction was successful
 * @apiParam {Text} description Description (optional)
 * @apiSuccess {String} status The resulting status for the transaction
 * @apiSuccess {String} message Information message
 * @apiSuccess {String} id Transaction id
 * @apiSuccess {String} amount Fac amount in <code>satoshis</code>
 * @apiSuccess {Integer} scale Fac scale
 * @apiSuccess {String} currency <code>FAC</code> currency
 * @apiSuccess {DateTime} created When transaction was created
 * @apiSuccess {DateTime} updated When transaction was updated last time
 * @apiSuccess {Object} pay_in_info Paynet payment info
 * @apiSuccess {String} pay_in_info.amount MXN transaction amount in <code>cents</code>
 * @apiSuccess {String} pay_in_info.currency <code>MXN</code>
 * @apiSuccess {Integer} pay_in_info.scale <code>MXN</code> scale
 * @apiSuccess {Date} pay_in_info.expires_in Transaction expiration date
 * @apiSuccess {Integer} pay_in_info.received Received amount
 * @apiSuccess {String} pay_in_info.barcode code for generate the barcode for the payment
 * @apiSuccess {String} pay_in_info.paynet_id transaction id generated by paynet
 * @apiSuccess {Integer} pay_in_info.status Paynet status payment
 * @apiSuccess {Object} pay_out_info Faircoin transaction info
 * @apiSuccess {String} pay_out_info.amount Faircoin amount in <code>satoshis</code>
 * @apiSuccess {String} pay_out_info.address Faircoin address were bitcoin will be sent when the cash in is successful
 * @apiSuccess {String} pay_out_info.currency <code>FAC</code>
 * @apiSuccess {Integer} pay_out_info.scale <code>FAC</code> scale
 * @apiSuccess {Boolean} pay_out_info.final Is final status?
 * @apiSuccess {String} pay_out_info.status Fac transaction status
 *
 */

/**
 * @api {get} /swift/v1/paynet/fac/:id Paynet to Faircoin Check
 * @apiName paynet_fac_check
 * @apiDescription Check Paynet-Fac transaction
 * @apiVersion 1.0.0
 * @apiGroup Swift
 * @apiParam {String} id Unique transaction id
 * @apiSuccess {String} status The resulting status for the transaction
 * @apiSuccess {String} message Information message
 * @apiSuccess {String} id Transaction id
 * @apiSuccess {String} amount Fac amount in <code>satoshis</code>
 * @apiSuccess {Integer} scale Fac scale
 * @apiSuccess {String} currency <code>FAC</code> currency
 * @apiSuccess {DateTime} created When transaction was created
 * @apiSuccess {DateTime} updated When transaction was updated last time
 * @apiSuccess {Object} pay_in_info Paynet payment info
 * @apiSuccess {String} pay_in_info.amount MXN transaction amount in <code>cents</code>
 * @apiSuccess {String} pay_in_info.currency <code>MXN</code>
 * @apiSuccess {Integer} pay_in_info.scale <code>MXN</code> scale
 * @apiSuccess {Date} pay_in_info.expires_in Transaction expiration date
 * @apiSuccess {Integer} pay_in_info.received Received amount
 * @apiSuccess {String} pay_in_info.barcode code for generate the barcode for the payment
 * @apiSuccess {String} pay_in_info.paynet_id transaction id generated by paynet
 * @apiSuccess {Integer} pay_in_info.status Paynet status payment
 * @apiSuccess {Object} pay_out_info Faircoin transaction info
 * @apiSuccess {String} pay_out_info.amount Faircoin amount in <code>satoshis</code>
 * @apiSuccess {String} pay_out_info.address Faircoin address were faircoin will be sent when the cash in is successful
 * @apiSuccess {String} pay_out_info.currency <code>FAC</code>
 * @apiSuccess {Integer} pay_out_info.scale <code>FAC</code> scale
 * @apiSuccess {Boolean} pay_out_info.final Is final status?
 * @apiSuccess {String} pay_out_info.status Fac transaction status
 *
 * @apiUse TransactionNotFoundError
 *
 */


//##################################   BTC-CRYPTOCAPITAL  #########################################

/**
 * @api {post} /api/cryptocapital/btc Bitcoin to Cryptocapital
 * @apiName btc_cryptocapital
 * @apiDescription Creates a virtual Visa Paying with bitcoins
 * @apiVersion 0.1.0
 * @apiGroup Swift
 * @apiParam {String} email Email where we send the virtual Visa information
 * @apiParam {Integer} amount Transaction amount in <code>EUR</code>
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} created Creation date
 * @apiSuccess {Integer} ticket_id The ID of the transaction
 * @apiSuccess {String} type Transaction type
 * @apiSuccess {String} orig_coin <code>BTC</code>
 * @apiSuccess {Integer} orig_scale 100000000
 * @apiSuccess {Integer} orig_amount Amount to send in <code>satoshis</code>
 * @apiSuccess {String} dst_coin <code>EUR</code>
 * @apiSuccess {Integer} dst_scale 100
 * @apiSuccess {Integer} dst_amount Virtual Visa amount in <code>cents</code>
 * @apiSuccess {Integer} price Bitcoin Price
 * @apiSuccess {String} address Address where user has to send bitcoins
 * @apiSuccess {Number} confirmations Current transaction confirmations
 * @apiSuccess {Number} received Received bitcoins in <code>satoshis</code>
 * @apiSuccess {String} message Information message about these type of transactions
 *
 * @apiError {String} status Error occurred.
 * @apiError {String} message  The description of the error.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "ok",
 *          "created": "2016-01-15T11:42:52+0100",
 *          "ticket_id": 15053,
 *          "type": "btc_cryptocapital",
 *          "orig_coin": "btc",
 *          "orig_scale": 100000000,
 *          "orig_amount": 2697672,
 *          "dst_coin": "eur",
 *          "dst_scale": 100,
 *          "dst_amount": 1000,
 *          "price": 37069,
 *          "address": "17JiT4kiaxaSbw57UiT1nz2Wn2MLH5LA6Z",
 *          "confirmations": -1,
 *          "received": 0,
 *          "message": "After the payment you will receive an email with the instructions"
 *    }
 *
 * @apiErrorExample Invalid Email
 *    HTTP/1.1 400: Bad Request
 *    {
 *          "status": "error",
 *          "message": "Invalid email"
 *    }
 *
 * @apiErrorExample Parameter not found
 *    HTTP/1.1 404: Not found
 *    {
 *          "status": "error",
 *          "message": "Parameter email not found"
 *    }
 *
 * @apiErrorExample Invalid Amount
 *    HTTP/1.1 400: Bad Request
 *    {
 *          "status": "error",
 *          "message": "Amount must be greater than 1000"
 *    }
 *
 */

/**
 * @api {post} /swift/v1/btc/cryptocapital Bitcoin to Cryptocapital
 * @apiName btc_cryptocapital
 * @apiDescription Creates a virtual Visa Paying with bitcoins
 * @apiVersion 1.0.0
 * @apiGroup Swift
 * @apiParam {Integer} amount Transaction amount in <code>cents</code>.
 * @apiParam {String} email Email where we will send the virtual visa information
 * @apiSuccess {String} status The resulting status for this transaction
 * @apiSuccess {String} message Information message
 * @apiSuccess {String} id Transaction id
 * @apiSuccess {String} amount Amount in <code>cents</code>
 * @apiSuccess {Integer} scale scale
 * @apiSuccess {String} currency <code>EUR</code>
 * @apiSuccess {DateTime} created When transaction was created
 * @apiSuccess {DateTime} updated When transaction was updated last time
 * @apiSuccess {Object} pay_in_info Btc payment info
 * @apiSuccess {String} pay_in_info.amount Btc transaction amount in <code>satoshis</code>
 * @apiSuccess {String} pay_in_info.currency Btc
 * @apiSuccess {Integer} pay_in_info.scale Btc scale
 * @apiSuccess {String} pay_in_info.address BTC address where the user must send the BTC
 * @apiSuccess {Integer} pay_in_info.expires_in Transaction expiration time
 * @apiSuccess {Integer} pay_in_info.received Received amount
 * @apiSuccess {Integer} pay_in_info.min_confirmations Minimum confirmations for validate transaction
 * @apiSuccess {Integer} pay_in_info.confirmations Confirmations for the current transaction
 * @apiSuccess {Integer} pay_in_info.status Bitcoin status payment
 * @apiSuccess {Object} pay_in_info.refund_info Only if the bitcoin transaction has been refund
 * @apiSuccess {Integer} pay_in_info.refund_info.amount Btc amount for the refund transaction
 * @apiSuccess {String} pay_in_info.refund_info.address Btc address where bitcoins were refund in <code>satoshis</code>
 * @apiSuccess {String} pay_in_info.refund_info.currency Btc
 * @apiSuccess {Integer} pay_in_info.refund_info.scale Btc scale
 * @apiSuccess {Boolean} pay_in_info.refund_info.final is final status?
 * @apiSuccess {String} pay_in_info.refund_info.status Refund transaction status
 * @apiSuccess {Object} pay_out_info Cryptocapital info
 * @apiSuccess {String} pay_out_info.amount Virtual Visa amount in <code>cents</code>
 * @apiSuccess {String} pay_out_info.currency <code>EUR</code>
 * @apiSuccess {Integer} pay_out_info.scale <code>EUR</code> scale
 * @apiSuccess {String} pay_out_info.email Email
 * @apiSuccess {Boolean} pay_out_info.final Is final status?
 * @apiSuccess {String} pay_out_info.status Cryptocapital status
 *
 * @apiError {String} status Error occurred.
 * @apiError {String} message  The description of the error.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "created",
 *          "message": "Done",
 *          "id": "5698d3b714227ee7778b4567",
 *          "amount": "1000",
 *          "scale": 2,
 *          "currency": "EUR",
 *          "created": "2016-01-15T12:10:47+0100",
 *          "updated": "2016-01-15T12:10:47+0100",
 *          "pay_in_info": {
 *              "amount": 2945602,
 *              "currency": "BTC",
 *              "scale": 8,
 *              "address": "1DRjC5Lt1QuU9KDDv56PMmCxfuXFAG85Ey",
 *              "expires_in": 1200,
 *              "received": 0,
 *              "min_confirmations": 1,
 *              "confirmations": 0,
 *              "status": "created"
 *          },
 *          "pay_out_info": {
 *              "amount": "1000",
 *              "email": "default@default.com",
 *              "currency": "EUR",
 *              "scale": 2,
 *              "final": false,
 *              "status": false
 *          }
 *    }
 *
 * @apiErrorExample Invalid Email
 *    HTTP/1.1 400: Bad Request
 *    {
 *          "status": "error",
 *          "message": "Invalid email"
 *    }
 *
 * @apiErrorExample Parameter not found
 *    HTTP/1.1 404: Not found
 *    {
 *          "status": "error",
 *          "message": "Parameter email not found"
 *    }
 *
 * @apiErrorExample Invalid Amount
 *    HTTP/1.1 400: Bad Request
 *    {
 *          "status": "error",
 *          "message": "Amount must be greater than 1000"
 *    }
 *
 */

/**
 * @api {get} /api/cryptocapital/btc/:id Bitcoin to Cryptocapital Check
 * @apiName btc_cryptocapital_check
 * @apiDescription Check cryptocapital transaction
 * @apiVersion 0.1.0
 * @apiGroup Swift
 * @apiParam {Integer} id Transaction id
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} created Creation date
 * @apiSuccess {Integer} ticket_id The ID of the transaction
 * @apiSuccess {String} type Transaction type
 * @apiSuccess {String} orig_coin <code>BTC</code>
 * @apiSuccess {Integer} orig_scale 100000000
 * @apiSuccess {Integer} orig_amount Amount to send in <code>satoshis</code>
 * @apiSuccess {String} dst_coin <code>EUR</code>
 * @apiSuccess {Integer} dst_scale 100
 * @apiSuccess {Integer} dst_amount Virtual Visa amount in <code>cents</code>
 * @apiSuccess {Integer} price Bitcoin Price
 * @apiSuccess {String} address Address where user has to send bitcoins
 * @apiSuccess {Number} confirmations Current transaction confirmations
 * @apiSuccess {Number} received Received bitcoins in <code>satoshis</code>
 * @apiSuccess {String} email Email
 *
 * @apiUse TransactionNotFoundError
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "ok",
 *          "created": "2016-01-15T11:42:52+0100",
 *          "ticket_id": 15053,
 *          "type": "btc_cryptocapital",
 *          "orig_coin": "btc",
 *          "orig_scale": 100000000,
 *          "orig_amount": 2697672,
 *          "dst_coin": "eur",
 *          "dst_scale": 100,
 *          "dst_amount": 1000,
 *          "price": 37069,
 *          "address": "17JiT4kiaxaSbw57UiT1nz2Wn2MLH5LA6Z",
 *          "confirmations": -1,
 *          "received": 0,
 *          "email": "default@default.com"
 *    }
 *
 */

/**
 * @api {get} /swift/v1/btc/cryptocapital/:id Bitcoin to Cryptocapital check
 * @apiName btc_cryptocapital_check
 * @apiDescription Check cryptocapital transaction
 * @apiVersion 1.0.0
 * @apiGroup Swift
 * @apiParam {String} id Unique transaction Id
 * @apiSuccess {String} status The resulting status for this transaction
 * @apiSuccess {String} message Information message
 * @apiSuccess {String} id Transaction id
 * @apiSuccess {String} amount Amount in <code>cents</code>
 * @apiSuccess {Integer} scale scale
 * @apiSuccess {String} currency <code>EUR</code>
 * @apiSuccess {DateTime} created When transaction was created
 * @apiSuccess {DateTime} updated When transaction was updated last time
 * @apiSuccess {Object} pay_in_info Btc payment info
 * @apiSuccess {String} pay_in_info.amount Btc transaction amount in <code>satoshis</code>
 * @apiSuccess {String} pay_in_info.currency Btc
 * @apiSuccess {Integer} pay_in_info.scale Btc scale
 * @apiSuccess {String} pay_in_info.address BTC address where the user must send the BTC
 * @apiSuccess {Integer} pay_in_info.expires_in Transaction expiration time
 * @apiSuccess {Integer} pay_in_info.received Received amount
 * @apiSuccess {Integer} pay_in_info.min_confirmations Minimum confirmations for validate transaction
 * @apiSuccess {Integer} pay_in_info.confirmations Confirmations for the current transaction
 * @apiSuccess {Integer} pay_in_info.status Bitcoin status payment
 * @apiSuccess {Object} pay_out_info Cryptocapital info
 * @apiSuccess {String} pay_out_info.amount Virtual Visa amount in <code>cents</code>
 * @apiSuccess {String} pay_out_info.currency <code>EUR</code>
 * @apiSuccess {Integer} pay_out_info.scale <code>EUR</code> scale
 * @apiSuccess {String} pay_out_info.email Email
 * @apiSuccess {Boolean} pay_out_info.final Is final status?
 * @apiSuccess {String} pay_out_info.status Cryptocapital status
 *
 * @apiUse TransactionNotFoundError
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "created",
 *          "message": "Done",
 *          "id": "5698d3b714227ee7778b4567",
 *          "amount": "1000",
 *          "scale": 2,
 *          "currency": "EUR",
 *          "created": "2016-01-15T12:10:47+0100",
 *          "updated": "2016-01-15T12:10:47+0100",
 *          "pay_in_info": {
 *              "amount": 2945602,
 *              "currency": "BTC",
 *              "scale": 8,
 *              "address": "1DRjC5Lt1QuU9KDDv56PMmCxfuXFAG85Ey",
 *              "expires_in": 1200,
 *              "received": 0,
 *              "min_confirmations": 1,
 *              "confirmations": 0,
 *              "status": "created"
 *          },
 *          "pay_out_info": {
 *              "amount": "1000",
 *              "email": "default@default.com",
 *              "currency": "EUR",
 *              "scale": 2,
 *              "final": false,
 *              "status": false
 *          }
 *    }
 *
 *
 *
 */


//##################################   BTC-SEPA  #########################################

/**
 * @api {post} /swift/v1/btc/sepa Bitcoin to sepa
 * @apiName btc_sepa
 * @apiDescription Sell bitcoins and send a bank transfer
 * @apiVersion 1.0.0
 * @apiGroup Swift
 * @apiParam {Integer} amount Transaction amount in <code>cents</code>.
 * @apiParam {String} beneficiary Bank account beneficiary
 * @apiParam {String} iban IBAN
 * @apiParam {String} bic_swift BIC/SWIFT
 * @apiParam {String} description Optianl description for the transaction
 * @apiSuccess {String} status The resulting status for this transaction
 * @apiSuccess {String} message Information message
 * @apiSuccess {String} id Transaction id
 * @apiSuccess {String} amount Amount in <code>cents</code>
 * @apiSuccess {Integer} scale scale
 * @apiSuccess {String} currency <code>EUR</code>
 * @apiSuccess {DateTime} created When transaction was created
 * @apiSuccess {DateTime} updated When transaction was updated last time
 * @apiSuccess {Object} pay_in_info Btc payment info
 * @apiSuccess {String} pay_in_info.amount Btc transaction amount in <code>satoshis</code>
 * @apiSuccess {String} pay_in_info.currency Btc
 * @apiSuccess {Integer} pay_in_info.scale Btc scale
 * @apiSuccess {String} pay_in_info.address BTC address where the user must send the BTC
 * @apiSuccess {Integer} pay_in_info.expires_in Transaction expiration time
 * @apiSuccess {Integer} pay_in_info.received Received amount
 * @apiSuccess {Integer} pay_in_info.min_confirmations Minimum confirmations for validate transaction
 * @apiSuccess {Integer} pay_in_info.confirmations Confirmations for the current transaction
 * @apiSuccess {Integer} pay_in_info.status Bitcoin status payment
 * @apiSuccess {Object} pay_in_info.refund_info Only if the bitcoin transaction has been refund
 * @apiSuccess {Integer} pay_in_info.refund_info.amount Btc amount for the refund transaction
 * @apiSuccess {String} pay_in_info.refund_info.address Btc address where bitcoins were refund in <code>satoshis</code>
 * @apiSuccess {String} pay_in_info.refund_info.currency Btc
 * @apiSuccess {Integer} pay_in_info.refund_info.scale Btc scale
 * @apiSuccess {Boolean} pay_in_info.refund_info.final is final status?
 * @apiSuccess {String} pay_in_info.refund_info.status Refund transaction status
 * @apiSuccess {Object} pay_out_info Cryptocapital info
 * @apiSuccess {String} pay_out_info.amount Bank transfer mount in <code>cents</code>
 * @apiSuccess {String} pay_out_info.currency <code>EUR</code>
 * @apiSuccess {Integer} pay_out_info.scale <code>EUR</code> scale
 * @apiSuccess {String} pay_out_info.beneficiary Name of the beneficiary account
 * @apiSuccess {String} pay_out_info.iban IBAN
 * @apiSuccess {String} pay_out_info.bic_swift BIC/SWIFT
 * @apiSuccess {Boolean} pay_out_info.final Is final status?
 * @apiSuccess {String} pay_out_info.status Cryptocapital status
 *
 */

/**
 * @api {get} /swift/v1/btc/sepa/:id Bitcoin to sepa check
 * @apiName btc_sepa_check
 * @apiDescription Check BTC-Sepa transaction
 * @apiVersion 1.0.0
 * @apiGroup Swift
 * @apiParam {String} id Unique transaction Id
 * @apiSuccess {String} status The resulting status for this transaction
 * @apiSuccess {String} message Information message
 * @apiSuccess {String} id Transaction id
 * @apiSuccess {String} amount Amount in <code>cents</code>
 * @apiSuccess {Integer} scale scale
 * @apiSuccess {String} currency <code>EUR</code>
 * @apiSuccess {DateTime} created When transaction was created
 * @apiSuccess {DateTime} updated When transaction was updated last time
 * @apiSuccess {Object} pay_in_info Btc payment info
 * @apiSuccess {String} pay_in_info.amount Btc transaction amount in <code>satoshis</code>
 * @apiSuccess {String} pay_in_info.currency Btc
 * @apiSuccess {Integer} pay_in_info.scale Btc scale
 * @apiSuccess {String} pay_in_info.address BTC address where the user must send the BTC
 * @apiSuccess {Integer} pay_in_info.expires_in Transaction expiration time
 * @apiSuccess {Integer} pay_in_info.received Received amount
 * @apiSuccess {Integer} pay_in_info.min_confirmations Minimum confirmations for validate transaction
 * @apiSuccess {Integer} pay_in_info.confirmations Confirmations for the current transaction
 * @apiSuccess {Integer} pay_in_info.status Bitcoin status payment
 * @apiSuccess {Object} pay_out_info Cryptocapital info
 * @apiSuccess {String} pay_out_info.amount Virtual Visa amount in <code>cents</code>
 * @apiSuccess {String} pay_out_info.currency <code>EUR</code>
 * @apiSuccess {Integer} pay_out_info.scale <code>EUR</code> scale
 * @apiSuccess {String} pay_out_info.email Email
 * @apiSuccess {Boolean} pay_out_info.final Is final status?
 * @apiSuccess {String} pay_out_info.status Cryptocapital status
 *
 * @apiUse TransactionNotFoundError
 *
 */

//##################################   FAC-CRYPTOCAPITAL  #########################################

/**
 * @api {post} /swift/v1/fac/cryptocapital Faircoin to Cryptocapital
 * @apiName fac_cryptocapital
 * @apiDescription Creates a virtual Visa Paying with Faircoins
 * @apiVersion 1.0.0
 * @apiGroup Swift
 * @apiParam {Integer} amount Transaction amount in <code>cents</code>.
 * @apiParam {String} email Email where we will send the virtual visa information
 * @apiSuccess {String} status The resulting status for this transaction
 * @apiSuccess {String} message Information message
 * @apiSuccess {String} id Transaction id
 * @apiSuccess {String} amount Amount in <code>cents</code>
 * @apiSuccess {Integer} scale scale
 * @apiSuccess {String} currency <code>EUR</code>
 * @apiSuccess {DateTime} created When transaction was created
 * @apiSuccess {DateTime} updated When transaction was updated last time
 * @apiSuccess {Object} pay_in_info Fac payment info
 * @apiSuccess {String} pay_in_info.amount Fac transaction amount in <code>satoshis</code>
 * @apiSuccess {String} pay_in_info.currency Fac
 * @apiSuccess {Integer} pay_in_info.scale Fac scale
 * @apiSuccess {String} pay_in_info.address Fac address where the user must send the FAC
 * @apiSuccess {Integer} pay_in_info.expires_in Transaction expiration time
 * @apiSuccess {Integer} pay_in_info.received Received amount
 * @apiSuccess {Integer} pay_in_info.min_confirmations Minimum confirmations for validate transaction
 * @apiSuccess {Integer} pay_in_info.confirmations Confirmations for the current transaction
 * @apiSuccess {Integer} pay_in_info.status Faircoin status payment
 * @apiSuccess {Object} pay_in_info.refund_info Only if the faircoin transaction has been refund
 * @apiSuccess {Integer} pay_in_info.refund_info.amount Fac amount for the refund transaction
 * @apiSuccess {String} pay_in_info.refund_info.address Fac address where bitcoins were refund in <code>satoshis</code>
 * @apiSuccess {String} pay_in_info.refund_info.currency Fac
 * @apiSuccess {Integer} pay_in_info.refund_info.scale Fac scale
 * @apiSuccess {Boolean} pay_in_info.refund_info.final is final status?
 * @apiSuccess {String} pay_in_info.refund_info.status Refund transaction status
 * @apiSuccess {Object} pay_out_info Cryptocapital info
 * @apiSuccess {String} pay_out_info.amount Virtual Visa amount in <code>cents</code>
 * @apiSuccess {String} pay_out_info.currency <code>EUR</code>
 * @apiSuccess {Integer} pay_out_info.scale <code>EUR</code> scale
 * @apiSuccess {String} pay_out_info.email Email
 * @apiSuccess {Boolean} pay_out_info.final Is final status?
 * @apiSuccess {String} pay_out_info.status Cryptocapital status
 *
 */

/**
 * @api {get} /swift/v1/fac/cryptocapital/:id Faircoin to Cryptocapital check
 * @apiName fac_cryptocapital_check
 * @apiDescription Check cryptocapital transaction
 * @apiVersion 1.0.0
 * @apiGroup Swift
 * @apiParam {String} id Unique transaction Id
 * @apiSuccess {String} status The resulting status for this transaction
 * @apiSuccess {String} message Information message
 * @apiSuccess {String} id Transaction id
 * @apiSuccess {String} amount Amount in <code>cents</code>
 * @apiSuccess {Integer} scale scale
 * @apiSuccess {String} currency <code>EUR</code>
 * @apiSuccess {DateTime} created When transaction was created
 * @apiSuccess {DateTime} updated When transaction was updated last time
 * @apiSuccess {Object} pay_in_info Fac payment info
 * @apiSuccess {String} pay_in_info.amount Fac transaction amount in <code>satoshis</code>
 * @apiSuccess {String} pay_in_info.currency Fac
 * @apiSuccess {Integer} pay_in_info.scale Fac scale
 * @apiSuccess {String} pay_in_info.address Fac address where the user must send the Fac
 * @apiSuccess {Integer} pay_in_info.expires_in Transaction expiration time
 * @apiSuccess {Integer} pay_in_info.received Received amount
 * @apiSuccess {Integer} pay_in_info.min_confirmations Minimum confirmations for validate transaction
 * @apiSuccess {Integer} pay_in_info.confirmations Confirmations for the current transaction
 * @apiSuccess {Integer} pay_in_info.status Faircoin status payment
 * @apiSuccess {Object} pay_out_info Cryptocapital info
 * @apiSuccess {String} pay_out_info.amount Virtual Visa amount in <code>cents</code>
 * @apiSuccess {String} pay_out_info.currency <code>EUR</code>
 * @apiSuccess {Integer} pay_out_info.scale <code>EUR</code> scale
 * @apiSuccess {String} pay_out_info.email Email
 * @apiSuccess {Boolean} pay_out_info.final Is final status?
 * @apiSuccess {String} pay_out_info.status Cryptocapital status
 *
 * @apiUse TransactionNotFoundError
 *
 */

//##################################   FAC-SEPA  #########################################

/**
 * @api {post} /swift/v1/fac/sepa Faircoin to sepa
 * @apiName fac_sepa
 * @apiDescription Sell faircoins and send a bank transfer
 * @apiVersion 1.0.0
 * @apiGroup Swift
 * @apiParam {Integer} amount Transaction amount in <code>cents</code>.
 * @apiParam {String} beneficiary Bank account beneficiary
 * @apiParam {String} iban IBAN
 * @apiParam {String} bic_swift BIC/SWIFT
 * @apiParam {String} description Optianl description for the transaction
 * @apiSuccess {String} status The resulting status for this transaction
 * @apiSuccess {String} message Information message
 * @apiSuccess {String} id Transaction id
 * @apiSuccess {String} amount Amount in <code>cents</code>
 * @apiSuccess {Integer} scale scale
 * @apiSuccess {String} currency <code>EUR</code>
 * @apiSuccess {DateTime} created When transaction was created
 * @apiSuccess {DateTime} updated When transaction was updated last time
 * @apiSuccess {Object} pay_in_info Fac payment info
 * @apiSuccess {String} pay_in_info.amount Fac transaction amount in <code>satoshis</code>
 * @apiSuccess {String} pay_in_info.currency Fac
 * @apiSuccess {Integer} pay_in_info.scale Fac scale
 * @apiSuccess {String} pay_in_info.address Fac address where the user must send the Fac
 * @apiSuccess {Integer} pay_in_info.expires_in Transaction expiration time
 * @apiSuccess {Integer} pay_in_info.received Received amount
 * @apiSuccess {Integer} pay_in_info.min_confirmations Minimum confirmations for validate transaction
 * @apiSuccess {Integer} pay_in_info.confirmations Confirmations for the current transaction
 * @apiSuccess {Integer} pay_in_info.status Faircoin status payment
 * @apiSuccess {Object} pay_in_info.refund_info Only if the Faircoin transaction has been refund
 * @apiSuccess {Integer} pay_in_info.refund_info.amount Fac amount for the refund transaction
 * @apiSuccess {String} pay_in_info.refund_info.address Fac address where faircoins were refund in <code>satoshis</code>
 * @apiSuccess {String} pay_in_info.refund_info.currency Fac
 * @apiSuccess {Integer} pay_in_info.refund_info.scale Fac scale
 * @apiSuccess {Boolean} pay_in_info.refund_info.final is final status?
 * @apiSuccess {String} pay_in_info.refund_info.status Refund transaction status
 * @apiSuccess {Object} pay_out_info Cryptocapital info
 * @apiSuccess {String} pay_out_info.amount Bank transfer mount in <code>cents</code>
 * @apiSuccess {String} pay_out_info.currency <code>EUR</code>
 * @apiSuccess {Integer} pay_out_info.scale <code>EUR</code> scale
 * @apiSuccess {String} pay_out_info.beneficiary Name of the beneficiary account
 * @apiSuccess {String} pay_out_info.iban IBAN
 * @apiSuccess {String} pay_out_info.bic_swift BIC/SWIFT
 * @apiSuccess {Boolean} pay_out_info.final Is final status?
 * @apiSuccess {String} pay_out_info.status Cryptocapital status
 *
 */

/**
 * @api {get} /swift/v1/btc/sepa/:id Faircoin to sepa check
 * @apiName fac_sepa_check
 * @apiDescription Check FAC-Sepa transaction
 * @apiVersion 1.0.0
 * @apiGroup Swift
 * @apiParam {String} id Unique transaction Id
 * @apiSuccess {String} status The resulting status for this transaction
 * @apiSuccess {String} message Information message
 * @apiSuccess {String} id Transaction id
 * @apiSuccess {String} amount Amount in <code>cents</code>
 * @apiSuccess {Integer} scale scale
 * @apiSuccess {String} currency <code>EUR</code>
 * @apiSuccess {DateTime} created When transaction was created
 * @apiSuccess {DateTime} updated When transaction was updated last time
 * @apiSuccess {Object} pay_in_info Fac payment info
 * @apiSuccess {String} pay_in_info.amount Fac transaction amount in <code>satoshis</code>
 * @apiSuccess {String} pay_in_info.currency Fac
 * @apiSuccess {Integer} pay_in_info.scale Fac scale
 * @apiSuccess {String} pay_in_info.address Fac address where the user must send the Fac
 * @apiSuccess {Integer} pay_in_info.expires_in Transaction expiration time
 * @apiSuccess {Integer} pay_in_info.received Received amount
 * @apiSuccess {Integer} pay_in_info.min_confirmations Minimum confirmations for validate transaction
 * @apiSuccess {Integer} pay_in_info.confirmations Confirmations for the current transaction
 * @apiSuccess {Integer} pay_in_info.status Faircoin status payment
 * @apiSuccess {Object} pay_out_info Cryptocapital info
 * @apiSuccess {String} pay_out_info.amount Virtual Visa amount in <code>cents</code>
 * @apiSuccess {String} pay_out_info.currency <code>EUR</code>
 * @apiSuccess {Integer} pay_out_info.scale <code>EUR</code> scale
 * @apiSuccess {String} pay_out_info.email Email
 * @apiSuccess {Boolean} pay_out_info.final Is final status?
 * @apiSuccess {String} pay_out_info.status Cryptocapital status
 *
 * @apiUse TransactionNotFoundError
 *
 */
