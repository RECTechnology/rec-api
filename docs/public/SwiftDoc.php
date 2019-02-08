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
 * @apiError message The description of the error
 *
 * @apiErrorExample Not found
 *    HTTP/1.1 404: Not found
 *    {
 *          "status": "error",
 *          "message": "Transaction not found"
 *    }
 */

/**
 * @apiDefine UnavailableSwiftMethod
 *
 * @apiError error Error
 * @apiError message The description of the error
 *
 * @apiErrorExample Forbidden
 *  HTTP/1.1 403: Forbidden
 * {
 *   "status": "error",
 *    "message": "Swift method temporally unavailable"
}
 */

/**
 * @apiDefine CryptoToCryptocapitalError
 *
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
 */


//##################################### HELLO CRYPTOCURRENCY ######################################

/**
 * @api {get} /swift/v1/hello/:currency/ Hello Cryptocurrency
 * @apiName HelloCrypto
 * @apiDescription Read Cryptocurrency configuration service
 * @apiVersion 1.0.0
 * @apiGroup Swift
 *
 * @apiParam {String = btc,fac,eth,crea} currency The The cryptocurrency
 *
 * @apiSuccess {Object} swift_methods Available methods for the currency
 * @apiSuccess {Integer} confirmations Minimum number of confirmations
 * @apiSuccess {Integer} timeout Expiration time for the transaction
 * @apiSuccess {String} terms Url where you can find the terms and conditions
 * @apiSuccess {String} title App name
 */


##################################### CRYPTOCURRENCY TO HALCASH ######################################

/**
 * @api {post} /swift/v1/:currency/halcash_es Cryptocurrency to Halcash_ES
 * @apiName CryptoHalcashEs
 * @apiDescription Creates a new Halcash transaction
 * @apiVersion 1.0.0
 * @apiGroup Swift
 *
 * @apiParam {String = btc,fac,eth,crea} currency The The cryptocurrency
 * @apiParam {Integer} amount Transaction amount in EUR
 * @apiParam {Integer} phone Phone number to send Hal
 * @apiParam {Integer} prefix Prefix phone number
 * @apiParam {Text} concept Description
 *
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} message Information message
 * @apiSuccess {String} id Transaction id
 * @apiSuccess {String} method Method used
 * @apiSuccess {String} amount Halcash amount in <code>EUR</code>
 * @apiSuccess {Integer} scale Currency scale
 * @apiSuccess {String} currency Halcash Amount currency
 * @apiSuccess {DateTime} created When transaction was created
 * @apiSuccess {DateTime} updated When transaction was updated last time
 * @apiSuccess {Object} pay_in_info Cryptocurrency payment info
 * @apiSuccess {Integer} pay_in_info.amount Cryptocurrency transaction amount
 * @apiSuccess {String} pay_in_info.currency Amount currency
 * @apiSuccess {Integer} pay_in_info.scale Currency scale
 * @apiSuccess {String} pay_in_info.address Currency address where the user must send the amount
 * @apiSuccess {Integer} pay_in_info.expires_in Transaction expiration time
 * @apiSuccess {Integer} pay_in_info.received Received amount
 * @apiSuccess {Integer} pay_in_info.min_confirmations Minimum confirmations for validate transaction
 * @apiSuccess {Integer} pay_in_info.confirmations Confirmations for the current transaction
 * @apiSuccess {Integer} pay_in_info.status Cryptocurrency status payment
 * @apiSuccess {Boolean} pay_in_info.final Is final status?
 * @apiSuccess {Object} pay_out_info Halcash info
 * @apiSuccess {String} pay_out_info.amount Halcash amount
 * @apiSuccess {String} pay_out_info.currency Amount currency
 * @apiSuccess {Integer} pay_out_info.scale Currency scale
 * @apiSuccess {Integer} pay_out_info.phone Phone where the halcash was sent
 * @apiSuccess {Integer} pay_out_info.prefix Prefix for the phone number
 * @apiSuccess {Text} pay_out_info.concept Halcash concept
 * @apiSuccess {String} pay_out_info.find_token Token to identify SMS
 * @apiSuccess {Integer} pay_out_info.pin Number with four digits
 * @apiSuccess {Boolean} pay_out_info.final Is final status?
 * @apiSuccess {String} pay_out_info.status Halcash status
 * @apiSuccess {String} pay_out_info.halcashticket Generated Halcashticket(only if the transaction was successful)
 *
 * @apiUse UnavailableSwiftMethod
 */


##################################### CHECK CRYPTOCURRENCY TO HALCASH ######################################

/**
 * @api {get} /swift/v1/:currency/halcash_es/:id Cryptocurrency to Halcash_ES Check
 * @apiName CryptoHalcashEsCheck
 * @apiDescription Check a Halcash transaction by transaction_id
 * @apiVersion 1.0.0
 * @apiGroup Swift
 *
 * @apiParam {String = btc,fac,eth,crea} currency The cryptocurrency
 * @apiParam {String} id Unique transaction id
 *
 * @apiSuccess {String} status Current transaction status
 * @apiSuccess {String} message Information message
 * @apiSuccess {String} id Transaction id
 * @apiSuccess {String} method Method used
 * @apiSuccess {Integer} amount Halcash amount in <code>cents</code>
 * @apiSuccess {Integer} scale Currency scale
 * @apiSuccess {String} currency Halcash Amount currency
 * @apiSuccess {DateTime} created When transaction was created
 * @apiSuccess {DateTime} updated When transaction was updated last time
 * @apiSuccess {Object} pay_in_info Cryptocurrency payment info
 * @apiSuccess {Integer} pay_in_info.amount Cryptocurrency transaction amount
 * @apiSuccess {String} pay_in_info.currency Amount currency
 * @apiSuccess {Integer} pay_in_info.scale Currency scale
 * @apiSuccess {String} pay_in_info.address Currency address where the user must send the amount
 * @apiSuccess {Integer} pay_in_info.expires_in Transaction expiration time
 * @apiSuccess {Integer} pay_in_info.received Received amount
 * @apiSuccess {Integer} pay_in_info.min_confirmations Minimum confirmations for validate transaction
 * @apiSuccess {Integer} pay_in_info.confirmations Confirmations for the current transaction
 * @apiSuccess {Integer} pay_in_info.status Cryptocurrency status payment
 * @apiSuccess {Boolean} pay_in_info.final Is final status?
 * @apiSuccess {Object} pay_in_info.refund_info Only if the crypto transaction has been refund
 * @apiSuccess {Integer} pay_in_info.refund_info.amount Crypto amount for the refund transaction
 * @apiSuccess {String} pay_in_info.refund_info.address Crypto address where the amount will be refunded
 * @apiSuccess {String} pay_in_info.refund_info.currency Amount currency
 * @apiSuccess {Integer} pay_in_info.refund_info.scale Currency scale
 * @apiSuccess {Boolean} pay_in_info.refund_info.final is final status?
 * @apiSuccess {String} pay_in_info.refund_info.status Refund transaction status
 * @apiSuccess {Object} pay_out_info Halcash info
 * @apiSuccess {String} pay_out_info.amount Halcash amount in <code>cents</code>
 * @apiSuccess {String} pay_out_info.currency Amount currency
 * @apiSuccess {Integer} pay_out_info.scale Currency scale
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

//##################################   CRYPTOCURRENCY TO CRYPTOCAPITAL  #########################################

/**
 * @api {post} /swift/v1/:currency/cryptocapital Cryptocurrency to Cryptocapital
 * @apiName CryptoCryptocapital
 * @apiDescription Creates a virtual Visa Paying with cryptocurrency
 * @apiVersion 1.0.0
 * @apiGroup Swift
 *
 * @apiParam {String = btc,fac,eth,crea} currency The cryptocurrency
 * @apiParam {Integer} amount Transaction amount in <code>cents</code>.
 * @apiParam {String} email Email where we will send the virtual visa information
 *
 * @apiSuccess {String} status The resulting status for this transaction
 * @apiSuccess {String} message Information message
 * @apiSuccess {String} id Transaction id
 * @apiSuccess {String} method Method used
 * @apiSuccess {String} amount Amount in <code>cents</code>
 * @apiSuccess {Integer} scale scale
 * @apiSuccess {String} currency <code>EUR</code>
 * @apiSuccess {DateTime} created When transaction was created
 * @apiSuccess {DateTime} updated When transaction was updated last time
 * @apiSuccess {Object} pay_in_info Cryptocurrency payment info
 * @apiSuccess {Integer} pay_in_info.amount Cryptocurrency transaction amount
 * @apiSuccess {String} pay_in_info.currency Cryptocurrency
 * @apiSuccess {Integer} pay_in_info.scale Currency scale
 * @apiSuccess {String} pay_in_info.address Cryptocurrency address where the user must send the amount
 * @apiSuccess {Integer} pay_in_info.expires_in Transaction expiration time
 * @apiSuccess {Integer} pay_in_info.received Received amount
 * @apiSuccess {Integer} pay_in_info.min_confirmations Minimum confirmations for validate transaction
 * @apiSuccess {Integer} pay_in_info.confirmations Confirmations for the current transaction
 * @apiSuccess {Integer} pay_in_info.status Cryptocurrency status payment
 * @apiSuccess {Boolean} pay_in_info.final Is final status?
 * @apiSuccess {Object} pay_out_info Cryptocapital info
 * @apiSuccess {String} pay_out_info.amount Virtual Visa amount in <code>cents</code>
 * @apiSuccess {String} pay_out_info.currency <code>EUR</code>
 * @apiSuccess {Integer} pay_out_info.scale <code>EUR</code> scale
 * @apiSuccess {String} pay_out_info.email Email
 * @apiSuccess {String} pay_out_info.concept Concept
 * @apiSuccess {String} pay_out_info.find_token Token
 * @apiSuccess {Boolean} pay_out_info.final Is final status?
 * @apiSuccess {String} pay_out_info.status Cryptocapital status
 *
 *
 * @apiSuccessExample Success
 * HTTP/1.1 200 OK
 * {
 *	"status": "created",
 *	"message": "Done",
 *	"id": "5698d3b714227ee7778b4567",
 *	"amount": "1000",
 *	"scale": 2,
 *	"currency": "EUR",
 *	"created": "2016-01-15T12:10:47+0100",
 *	"updated": "2016-01-15T12:10:47+0100",
 *	"pay_in_info": {
 *		"amount": 2945602,
 *		"currency": "BTC",
 *		"scale": 8,
 *		"address": "1DRjC5Lt1QuU9KDDv56PMmCxfuXFAG85Ey",
 *		"expires_in": 1200,
 *		"received": 0,
 *		"min_confirmations": 1,
 *		"confirmations": 0,
 *		"status": "created",
 *		"final": false
 *	},
 *	"pay_out_info": {
 *		"amount": "1000",
 *		"email": "default@default.com",
 *		"concept": "Cryptocapital transaction",
 *		"find_token": "41lunx",
 *		"currency": "EUR",
 *		"scale": 2,
 *		"final": false,
 *		"status": false
 *	}
 * }
 *
 * @apiUse UnavailableSwiftMethod
 * @apiUse CryptoToCryptocapitalError
 *
 */

//##################################   CHECK CRYPTOCURRENCY TO CRYPTOCAPITAL  #########################################

/**
 * @api {get} /swift/v1/:currency/cryptocapital/:id Cryptocurrency to Cryptocapital check
 * @apiName CryptoCryptocapitalCheck
 * @apiDescription Check cryptocapital transaction
 * @apiVersion 1.0.0
 * @apiGroup Swift
 *
 * @apiParam {String = btc,fac,eth,crea} currency The cryptocurrency
 * @apiParam {String} id Unique transaction Id
 *
 * @apiSuccess {String} status The resulting status for this transaction
 * @apiSuccess {String} message Information message
 * @apiSuccess {String} id Transaction id
 * @apiSuccess {String} method Method used
 * @apiSuccess {Integer} amount Amount in <code>cents</code>
 * @apiSuccess {Integer} scale scale
 * @apiSuccess {String} currency <code>EUR</code>
 * @apiSuccess {DateTime} created When transaction was created
 * @apiSuccess {DateTime} updated When transaction was updated last time
 * @apiSuccess {Object} pay_in_info Crypto payment info
 * @apiSuccess {Integer} pay_in_info.amount Cryptocurrency transaction amount
 * @apiSuccess {String} pay_in_info.currency Cryptocurrency
 * @apiSuccess {Integer} pay_in_info.scale Currency scale
 * @apiSuccess {String} pay_in_info.address Cryptocurrency address where the user must send the amount
 * @apiSuccess {Integer} pay_in_info.expires_in Transaction expiration time
 * @apiSuccess {Integer} pay_in_info.received Received amount
 * @apiSuccess {Integer} pay_in_info.min_confirmations Minimum confirmations for validate transaction
 * @apiSuccess {Integer} pay_in_info.confirmations Confirmations for the current transaction
 * @apiSuccess {Integer} pay_in_info.status Cryptocurrency status payment
 * @apiSuccess {Boolean} pay_in_info.final Is final status?
 * @apiSuccess {Object} pay_in_info.refund_info Only if the cryptocurrency transaction has been refund
 * @apiSuccess {Integer} pay_in_info.refund_info.amount Cryptocurrency amount for the refund transaction
 * @apiSuccess {String} pay_in_info.refund_info.address Cryptocurrency address where the amount will be refunded
 * @apiSuccess {String} pay_in_info.refund_info.currency Cryptocurrency
 * @apiSuccess {Integer} pay_in_info.refund_info.scale Cryptocurrency scale
 * @apiSuccess {Boolean} pay_in_info.refund_info.final is final status?
 * @apiSuccess {String} pay_in_info.refund_info.status Refund transaction status
 * @apiSuccess {Object} pay_out_info Cryptocapital info
 * @apiSuccess {String} pay_out_info.amount Virtual Visa amount in <code>cents</code>
 * @apiSuccess {String} pay_out_info.currency <code>EUR</code>
 * @apiSuccess {Integer} pay_out_info.scale <code>EUR</code> scale
 * @apiSuccess {String} pay_out_info.email Email
 * @apiSuccess {String} pay_out_info.concept Concept
 * @apiSuccess {String} pay_out_info.find_token Token
 * @apiSuccess {Boolean} pay_out_info.final Is final status?
 * @apiSuccess {String} pay_out_info.status Cryptocapital status
 *
 * @apiSuccessExample Success
 * HTTP/1.1 200 OK
 * {
 *	"status": "created",
 *	"message": "Done",
 *	"id": "5698d3b714227ee7778b4567",
 *	"amount": 1000,
 *	"scale": 2,
 *	"currency": "EUR",
 *	"created": "2016-01-15T12:10:47+0100",
 *	"updated": "2016-01-15T12:10:47+0100",
 *	"pay_in_info": {
 *		"amount": 2945602,
 *		"currency": "BTC",
 *		"scale": 8,
 *		"address": "1DRjC5Lt1QuU9KDDv56PMmCxfuXFAG85Ey",
 *		"expires_in": 1200,
 *		"received": 0,
 *		"min_confirmations": 1,
 *		"confirmations": 0,
 *		"status": "created",
 *		"final": false
 *	},
 *	"pay_out_info": {
 *		"amount": "1000",
 *		"email": "default@default.com",
 *		"concept": "Cryptocapital transaction",
 *		"find_token": "41punx",
 *		"currency": "EUR",
 *		"scale": 2,
 *		"final": false,
 *		"status": false
 *	}
 * }
 *
 * @apiUse TransactionNotFoundError
 *
 */


//##################################   CRYPTOCURRENCY TO SEPA #########################################

/**
 * @api {post} /swift/v1/:currency/sepa Cryptocurrency to SEPA
 * @apiName CryptoSepa
 * @apiDescription Sell Crypto and send a bank transfer
 * @apiVersion 1.0.0
 * @apiGroup Swift
 *
 * @apiParam {String = btc,fac,eth,crea} currency The cryptocurrency
 * @apiParam {Integer} amount Transaction amount in <code>cents</code>.
 * @apiParam {String} beneficiary Bank account beneficiary
 * @apiParam {String} iban IBAN
 * @apiParam {String} bic_swift BIC/SWIFT
 * @apiParam {String} description Optianl description for the transaction
 *
 * @apiSuccess {String} status The resulting status for this transaction
 * @apiSuccess {String} message Information message
 * @apiSuccess {String} id Transaction id
 * @apiSuccess {String} amount Amount in <code>cents</code>
 * @apiSuccess {Integer} scale scale
 * @apiSuccess {String} currency <code>EUR</code>
 * @apiSuccess {DateTime} created When transaction was created
 * @apiSuccess {DateTime} updated When transaction was updated last time
 * @apiSuccess {Object} pay_in_info Cryptocurrency payment info
 * @apiSuccess {String} pay_in_info.amount Cryptocurrency transaction amount
 * @apiSuccess {String} pay_in_info.currency Currency amount
 * @apiSuccess {Integer} pay_in_info.scale Currency scale
 * @apiSuccess {String} pay_in_info.address Cryptocurrency address where the user must send the amount
 * @apiSuccess {Integer} pay_in_info.expires_in Transaction expiration time
 * @apiSuccess {Integer} pay_in_info.received Received amount
 * @apiSuccess {Integer} pay_in_info.min_confirmations Minimum confirmations for validate transaction
 * @apiSuccess {Integer} pay_in_info.confirmations Confirmations for the current transaction
 * @apiSuccess {String} pay_in_info.status Cryptocurrency status payment
 * @apiSuccess {Boolean} pay_in_info.final Is final status?
 * @apiSuccess {Object} pay_out_info SEPA info
 * @apiSuccess {String} pay_out_info.amount Bank transfer mount in <code>cents</code>
 * @apiSuccess {String} pay_out_info.currency <code>EUR</code>
 * @apiSuccess {Integer} pay_out_info.scale <code>EUR</code> scale
 * @apiSuccess {String} pay_out_info.beneficiary Name of the beneficiary account
 * @apiSuccess {String} pay_out_info.iban IBAN
 * @apiSuccess {String} pay_out_info.concept Concpet
 * @apiSuccess {String} pay_out_info.find_token Token
 * @apiSuccess {String} pay_out_info.bic_swift BIC/SWIFT
 * @apiSuccess {Boolean} pay_out_info.final Is final status?
 * @apiSuccess {Boolean} pay_out_info.status SEPA status
 * @apiSuccess {Boolean} pay_out_info.gestioned SEPA status
 *
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "created",
 *          "message": "Done",
 *          "id": "56991f4314227e42308b4567",
 *          "amount": "1000",
 *          "scale": 2,
 *          "currency": "EUR",
 *          "created": "2016-01-15T17:33:07+0100",
 *          "updated": "2016-01-15T17:33:07+0100",
 *          "pay_in_info": {
 *              "amount": 3066044,
 *              "currency": "BTC",
 *              "scale": 8,
 *              "address": "13cPLaRAduHKV3oT8wzU1j7DUtPnry917K",
 *              "expires_in": 1200,
 *              "received": 0,
 *              "min_confirmations": 1,
 *              "confirmations": 0,
 *              "status": "created",
 *              "final": false
 *          },
 *          "pay_out_info": {
 *              "beneficiary": "Default default default",
 *              "iban": "sdfgfdsa",
 *              "amount": "1000",
 *              "bic_swift": "hjkl",
 *              "concept": "Sepa transaction",
 *              "find_token": "5acfap",
 *              "currency": "EUR",
 *              "scale": 2,
 *              "final": false,
 *              "status": false,
 *              "gestioned": false
 *          }
 *    }
 *
 * @apiUse UnavailableSwiftMethod
 */

//##################################   CHECK CRYPTOCURRENCY TO SEPA #########################################

/**
 * @api {get} /swift/v1/:currency/sepa/:id Cryptocurrency to SEPA check
 * @apiName CryptoSepaCheck
 * @apiDescription Check Cryptocurrency-SEPA transaction
 * @apiVersion 1.0.0
 * @apiGroup Swift
 *
 * @apiParam {String = btc,fac,eth,crea} currency The cryptocurrency
 * @apiParam {String} id Unique transaction Id
 *
 * @apiSuccess {String} status The resulting status for this transaction
 * @apiSuccess {String} message Information message
 * @apiSuccess {String} id Transaction id
 * @apiSuccess {String} amount Amount in <code>cents</code>
 * @apiSuccess {Integer} scale Currency scale
 * @apiSuccess {String} currency EUR
 * @apiSuccess {DateTime} created When transaction was created
 * @apiSuccess {DateTime} updated When transaction was updated last time
 * @apiSuccess {Object} pay_in_info Cryptocurrency payment info
 * @apiSuccess {String} pay_in_info.amount Cryptocurrency transaction amount
 * @apiSuccess {String} pay_in_info.currency Currency
 * @apiSuccess {Integer} pay_in_info.scale Currency scale
 * @apiSuccess {String} pay_in_info.address Cryptocurrency address where the user must send the amount
 * @apiSuccess {Integer} pay_in_info.expires_in Transaction expiration time
 * @apiSuccess {Integer} pay_in_info.received Received amount
 * @apiSuccess {Integer} pay_in_info.min_confirmations Minimum confirmations for validate transaction
 * @apiSuccess {Integer} pay_in_info.confirmations Confirmations for the current transaction
 * @apiSuccess {String} pay_in_info.status Cryptocurrency status payment
 * @apiSuccess {Boolean} pay_in_info.final Is final status?
 * @apiSuccess {Object} pay_out_info SEPA info
 * @apiSuccess {String} pay_out_info.amount Bank transfer mount in <code>cents</code>
 * @apiSuccess {String} pay_out_info.currency <code>EUR</code>
 * @apiSuccess {Integer} pay_out_info.scale <code>EUR</code> scale
 * @apiSuccess {String} pay_out_info.beneficiary Name of the beneficiary account
 * @apiSuccess {String} pay_out_info.iban IBAN
 * @apiSuccess {String} pay_out_info.concept Concpet
 * @apiSuccess {String} pay_out_info.find_token Token
 * @apiSuccess {String} pay_out_info.bic_swift BIC/SWIFT
 * @apiSuccess {Boolean} pay_out_info.final Is final status?
 * @apiSuccess {Boolean} pay_out_info.status SEPA status
 * @apiSuccess {Boolean} pay_out_info.gestioned SEPA status
 *
 * @apiUse TransactionNotFoundError
 *
 */