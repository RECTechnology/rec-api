<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/4/15
 * Time: 2:37 AM
 */

/**
 *
 * @api {get} /user/v1/account Read Account
 * @apiName ReadAccount
 * @apiPermission User
 * @apiVersion 0.1.0
 * @apiGroup User
 *
 * @apiUse OAuth2Header
 *
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {String} data Data of the request result.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "ok",
 *          "message": "Account info got successfully",
 *          "data":
 *              {
 *                  ...
 *              }
 *    }
 *
 */


/**
 *
 * @api {put} /user/v1/account Update Account
 * @apiName UpdateAccount
 * @apiPermission User
 * @apiVersion 0.1.0
 * @apiGroup User
 *
 * @apiUse OAuth2Header
 *
 * @apiParam {String} [email] email Email
 * @apiParam {String} [name] name Name
 *
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {String} data Data of the request result.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "ok",
 *          "message": "Account info got successfully",
 *          "data":
 *              {
 *                  ...
 *              }
 *    }
 *
 */

/**
 *
 * @api {get} /user/v1/wallet Read wallets
 * @apiName ReadWallets
 * @apiPermission User
 * @apiVersion 0.1.0
 * @apiGroup User
 *
 * @apiUse OAuth2Header
 *
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {String} data Data of the request result.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "ok",
 *          "message": "Wallet info got successful",
 *          "data":
 *              {
 *              "id": 3,
 *              "currency": "BTC",
 *              "available": 680914,
 *              "balance": 680914,
 *              "scale": 8
 *              },
 *              {
 *              "id": 4,
 *              "currency": "EUR",
 *              "available": 36997,
 *              "balance": 36997,
 *              "scale": 2
 *              },
 *              {
 *              "id": 5,
 *              "currency": "USD",
 *              "available": 1268,
 *              "balance": 1268,
 *              "scale": 2
 *              },
 *              {
 *              "id": 6,
 *              "currency": "FAC",
 *              "available": 2047,
 *              "balance": 2047,
 *              "scale": 8
 *              },
 *              {
 *              "id": 7,
 *              "currency": "MXN",
 *              "available": 20297,
 *              "balance": 20297,
 *              "scale": 2
 *              },
 *              {
 *              "id": 183,
 *              "currency": "PLN",
 *              "available": 0,
 *              "balance": 0,
 *              "scale": 2
 *              },
 *              {
 *              "id": "multidivisa",
 *              "currency": "EUR",
 *              "available": 39529,
 *              "balance": 39529,
 *              "scale": 2
 *              }
 *    }
 *
 */


/**
 *
 * @api {get} /user/v1/wallet/transactions Read wallet transactions
 * @apiName ReadWalletTransactions
 * @apiPermission User
 * @apiVersion 0.1.0
 * @apiGroup User
 *
 * @apiUse OAuth2Header
 *
 * @apiParam {Number} [limit] limit=10 Number of Clients to get
 * @apiParam {Number} [offset] offset=0 Offset for get clients
 * @apiParam {String[]} [query] query[] params
 *
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {String} data Data of the request result.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "ok",
 *          "message": "Transactions info got successful",
 *          "data":
 *              {
 *              ...
 *              },
 *              {
 *              ...
 *              },
 *              ...
 *    }
 *
 */

/**
 *
 * @api {get} /user/v1/wallet/monthearnings Month earnings
 * @apiName ReadMonthEarnings
 * @apiPermission User
 * @apiVersion 0.1.0
 * @apiGroup User
 *
 * @apiUse OAuth2Header
 *
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {String} data Data of the request result.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "ok",
 *          "message": "MOnth earnings got successful",
 *          "data":
 *              {
 *              ...
 *              },
 *              {
 *              ...
 *              },
 *              ...
 *    }
 *
 */


/**
 *
 * @api {get} /user/v1/wallet/countryearnings Country earnings
 * @apiName ReadCountryEarnings
 * @apiPermission User
 * @apiVersion 0.1.0
 * @apiGroup User
 *
 * @apiUse OAuth2Header
 *
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {String} data Data of the request result.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "ok",
 *          "message": "Country earnings info got successful",
 *          "data":
 *              {
 *              ...
 *              },
 *              {
 *              ...
 *              },
 *              ...
 *    }
 *
 */


/**
 *
 * @api {get} /user/v1/last Read 10 last transactions
 * @apiName Read10LastTransactions
 * @apiPermission User
 * @apiVersion 0.1.0
 * @apiGroup User
 *
 * @apiUse OAuth2Header
 *
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {String} data Data of the request result.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "ok",
 *          "message": "Last 10 transactions got successfully",
 *          "data":
 *              {
 *              ...
 *              },
 *              {
 *              ...
 *              },
 *              ...
 *    }
 *
 */

/**
 *
 * @api {post} /user/v1/wallet/send/:currency Wallet To Wallet
 * @apiName WalletToWallet
 * @apiPermission User
 * @apiVersion 0.1.0
 * @apiGroup User
 *
 * @apiUse OAuth2Header
 *
 * @apiParam {String} username Username receiver
 * @apiParam {Number} amount Amount in <code>cents</code>
 *
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {String} data Data of the request result.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "ok",
 *          "message": "Transactions info got successful",
 *          "data":
 *              {
 *              ...
 *              },
 *              {
 *              ...
 *              },
 *              ...
 *    }
 *
 */

/**
 *
 * @api {post} /user/v1/wallet/currency_exchange Exchange
 * @apiName Exchange
 * @apiPermission User
 * @apiVersion 0.1.0
 * @apiGroup User
 *
 * @apiUse OAuth2Header
 *
 * @apiParam {String} currency_in Currency In
 * @apiParam {String} currency_out Currency Out
 * @apiParam {Number} amount Amount in <code>cents</code> of currency in
 *
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {String} data Data of the request result.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "ok",
 *          "message": "Exchange got successful",
 *          "data":{}
 *    }
 *
 */
