<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/4/15
 * Time: 2:37 AM
 */

/**
 *
 * @api {get} /user/v1/wallet/listCommerce List Commerce
 * @apiName ListCommerce
 * @apiDescription List all commerces wich are rec intermediaries _(exchange rec)_
 * @apiPermission User
 * @apiVersion 1.0.0
 * @apiGroup User
 *
 * @apiUse OAuth2Header
 *
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {Array} data Data of the request result.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "ok",
 *          "message": "Request successful",
 *          "data":
 *              {
 *                  ...
 *              }
 *    }
 *
 */


/**
 *
 * @api {post} /user/v1/new/account Create Account
 * @apiName CreateAccount
 * @apiPermission User
 * @apiVersion 1.0.0
 * @apiGroup User
 *
 * @apiUse OAuth2Header
 *
 */
/**
 *
 * @api {put} /user/v1/account Update Account
 * @apiName UpdateAccount
 * @apiPermission User
 * @apiVersion 1.0.0
 * @apiGroup User
 *
 * @apiUse OAuth2Header
 *
 */
/**
 *
 * @api {get} /user/v1/public_phone_list Phone List
 * @apiName PhoneList
 * @apiPermission User
 * @apiVersion 1.0.0
 * @apiGroup User
 *
 * @apiUse OAuth2Header
 *
 * @apiSuccess {String} status Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {Array} data Data of the request result.
 *
 * @apiErrorExample Missing Parameter
 *    HTTP/1.1 400: Missing Parameter
 *   {
 *          "error": "invalid_request",
 *          "error_description": ""Missing parameters phone_list""
 *    }
 */


/**
 *
 * @api {post} /user/v1/upload_file Upload File
 * @apiName UploadFile
 * @apiPermission User
 * @apiVersion 1.0.0
 * @apiGroup User
 *
 * @apiUse OAuth2Header
 *
 * @apiParam {File} file File to be uploaded 
 * 
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {Array} data Data of the request result.
 *
 * 
 *  @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "ok",
 *          "message": "Request successful",
 *          "data":
 *              {
 *                "src": '',
 *                "type": "<mime_type>",
 *                "expires_in": 600              
 *              }
 *    }
 * 
 * @apiErrorExample Bad File
 *    HTTP/1.1 400: Bad File Type
 *    {
 *          "error": "invalid_request",
 *          "error_description": "'file' parameter required to be a file"
 *    }
 * @apiErrorExample Bad Mime Type
 *    HTTP/1.1 400: Bad Mime Type
 *    {
 *          "error": "invalid_request",
 *          "error_description": "Bad mime type, '<mime_type>' is not a valid file"
 *    }
 *
 */

 /**
 *
 * @api {put} /user/v1/public_phone Public Phone 
 * @apiName PublicPhone
 * @apiPermission User
 * @apiVersion 1.0.0
 * @apiGroup User
 * @apiParam {Number = 0, 1} activate 
 * @apiParam {Number = 0, 1} deactivate 
 * 
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {User} data The user updated.
 *
 *  @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "ok",
 *          "message": "Request successful",
 *          "data":
 *              {
 *                ...            
 *              }
 *    }
 * @apiUse OAuth2Header
 * @apiErrorExample Invalid parameters
 *    HTTP/1.1 400
 *    {
 *          "error": "invalid_request",
 *          "error_description": "Missing parameters
 *    }
 */
/**
 *
 * @api {put} /user/v1/save_kyc Save Kyc
 * @apiName SaveKyc
 * @apiPermission User
 * @apiVersion 1.0.0
 * @apiGroup User
 * 
 * @apiSuccess {String} name
 * @apiSuccess {String} last_name
 * @apiSuccess {DateTime} date_bith
 * @apiSuccess {String} street_type
 * @apiSuccess {String} street_number
 * @apiSuccess {String} street_name
 *
 *  @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "ok",
 *          "message": "Request successful",
 *          "data":
 *              {
 *                ...            
 *              }
 *    }
 * @apiUse OAuth2Header
 * @apiErrorExample Invalid parameters
 *    HTTP/1.1 400
 *    {
 *          "error": "invalid_request",
 *          "error_description": "Missing parameter <param>
 *    }
 */
