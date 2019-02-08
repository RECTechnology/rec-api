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
 * @api {post} /methods/v1/out/rec Send recs to address
 * @apiName RecOut
 * @apiDescription Sends recs to address
 * @apiVersion 1.0.0
 * @apiGroup Methods
 * 
 * @apiParam {String} address The receiver adress
 * @apiParam {Number} amount The amount to send
 * @apiParam {String} concept A message to indentify the transaction
 * @apiParam {String} pin Your pin number
 * 
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} message The message about the result of the request
 * @apiSuccess {String} id The ID of the transaction
 * @apiSuccess {Integer} amount The amount in <code>satoshis</code> of the transaction
 * @apiSuccess {Integer=8} scale The number of decimals to represent the amount
 * @apiSuccess {String=REC} currency The currency
 * @apiSuccess {DateTime} created The datetime when the transaction was created
 * @apiSuccess {DateTime} updated The datetime when the transaction was updated
 * @apiSuccess {Object} pay_in_info The cryptocurrency payment related data of the transaction
 * 
 * @apiSuccessExample Response
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "success",
 *          "message": "",
 *          "id": "fffcccaaacccddd",
 *          "amount": 100,
 *          "scale": 8,
 *          "currency": "REC",
 *          "created": "date",
 *          "updated": "date",
 *          "pay_in_info": {},
 *    }
 * @apiErrorExample Invalid Param
 *    HTTP/1.1 400: Bad Request
 *    {
 *          "error": "invalid_request",
 *          "error_description": "Param amount not found or incorrect"
 *    }
 * @apiErrorExample No Funds Available
 *    HTTP/1.1 405: Bad Request
 *    {
 *          "error": "invalid_request",
 *          "error_description": "Not founds enough"
 *    }
 * @apiErrorExample Incorrect Pin
 *    HTTP/1.1 400: Bad Request
 *    {
 *          "error": "invalid_request",
 *          "error_description": "Incorrect Pin"
 *    }
 * @apiErrorExample Pin Not Found
 *    HTTP/1.1 400: Bad Request
 *    {
 *          "error": "invalid_request",
 *          "error_description": "Param pin not found or incorrect"
 *    }
 * @apiErrorExample Address Not Found
 *    HTTP/1.1 405: Bad Request
 *    {
 *          "error": "invalid_request",
 *          "error_description": "Destination address does not exists"
 *    }
 * @apiErrorExample Addres Match
 *    HTTP/1.1 405: Bad Request
 *    {
 *          "error": "invalid_request",
 *          "error_description": "Error, destination address is equal than origin address"
 *    }
 */


// /methods/v1/in/lemonway
/**
 * @api {post} /methods/v1/in/lemonway LemonwayIn 
 * @apiName LemonwayIn
 * @apiDescription LemonwayIn
 * @apiVersion 1.0.0
 * @apiGroup Methods
 * 
 * @apiParam {Number} amount The amount to send
 * @apiParam {String} concept A message to indentify the transaction
 * @apiParam {String} pin Your pin number
 * @apiParam {Number = 0,1} savecard Wether to save the card
 * @apiParam {String} commerce_id The selected commerce from wich to receive the RECs
 * 
 * @apiSuccess {String} status The resulting status of the transaction
 * @apiSuccess {String} message The message about the result of the request
 * @apiSuccess {String} id The ID of the transaction
 * @apiSuccess {Integer} amount The amount in <code>satoshis</code> of the transaction
 * @apiSuccess {Integer=8} scale The number of decimals to represent the amount
 * @apiSuccess {String=REC} currency The currency
 * @apiSuccess {DateTime} created The datetime when the transaction was created
 * @apiSuccess {DateTime} updated The datetime when the transaction was updated
 * @apiSuccess {Object} pay_in_info The cryptocurrency payment related data of the transaction
 * 
 * @apiSuccessExample Response
 *    HTTP/1.1 200 OK
 * {
 *  "status": "created",
 *   "message": "Done",
 *   "id": "5c191f775a5c9810741a21b0",
 *   "amount": "1000",
 *   "scale": 2,
 *   "currency": "EUR",
 *   "created": "2018-12-18T17:25:27+01:00",
 *   "updated": "2018-12-18T17:25:28+01:00",
 *   "pay_in_info": {
 *       "amount": "1000",
 *       "commerce_id": "756",
 *       "currency": "EUR",
 *       "scale": 2,
 *       "token_id": "13dgtevx54",
 *       "payment_url": "https://sandbox-webkit.lemonway.fr/NOVACT/dev/?moneyintoken=13dgtevx54",
 *       "save_card": false,
 *       "wl_token": "29f6a6ed",
 *       "transaction_id": "519",
 *       "expires_in": 1200,
 *       "received": 0,
 *       "status": "created",
 *       "final": false,
 *       "concept": "asdasd\n"
 *   }
 * @apiErrorExample Invalid Param
 *    HTTP/1.1 400: Bad Request
 *    {
 *          "error": "invalid_request",
 *          "error_description": "Param amount not found or incorrect"
 *    }
 * @apiErrorExample No Funds Available
 *    HTTP/1.1 405: Bad Request
 *    {
 *          "error": "invalid_request",
 *          "error_description": "Not founds enough"
 *    }
 * @apiErrorExample Incorrect Pin
 *    HTTP/1.1 400: Bad Request
 *    {
 *          "error": "invalid_request",
 *          "error_description": "Incorrect Pin"
 *    }
 * @apiErrorExample Pin Not Found
 *    HTTP/1.1 400: Bad Request
 *    {
 *          "error": "invalid_request",
 *          "error_description": "Param pin not found or incorrect"
 *    }
 * @apiErrorExample Commerce selected is not available
 *    HTTP/1.1 400: Bad Request
 *    {
 *          "error": "invalid_request",
 *          "error_description": "Commerce selected is not available"
 *    }
 * @apiErrorExample commerce_id Not Found
 *    HTTP/1.1 400: Bad Request
 *    {
 *          "error": "invalid_request",
 *          "error_description": "Param commerce_id not found or incorrect"
 *    }
 * @apiErrorExample Address Not Found
 *    HTTP/1.1 405: Bad Request
 *    {
 *          "error": "invalid_request",
 *          "error_description": "Destination address does not exists"
 *    }
 * @apiErrorExample Addres Match
 *    HTTP/1.1 405: Bad Request
 *    {
 *          "error": "invalid_request",
 *          "error_description": "Error, destination address is equal than origin address"
 *    }
 * @apiErrorExample Addres Match
 *    HTTP/1.1 405: Bad Request
 *    {
 *          "error": "invalid_request",
 *          "error_description": "Credit card selected is not available"
 *    }
 * 
 */


