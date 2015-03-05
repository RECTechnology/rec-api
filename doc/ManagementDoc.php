<?php

/**
 * Author: Lluis Santos
 * This file is for add in some place the Security API Documentation (OAuth2)
 */
/**
 *
 *
 * @api {get} /admin/v1/clients Read clients
 * @apiName ReadClients
 * @apiPermission SuperAdmin
 * @apiVersion 0.1.0
 * @apiGroup Clients
 *
 * @apiUse OAuth2Header
 *
 *
 * @apiParam {Number} limit=10 Number of Clients to get
 * @apiParam {Number} offset=0 Offset for get clients
 *
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "ok",
 *          "message": "Clients got OK",
 *          "data":
 *              {
 *                  "client_id": "ksdjfhksljd",
 *                  "client_secret": "ksdjfhksljd",
 *              }
 *    }
 *
 * @apiError {String} lastname  Lastname of the User.
 *
 *
 *
 */

/**
 *
 * @api {get} /admin/v1/clients/{id} Read one client
 * @apiName ReadClient
 * @apiPermission SuperAdmin
 * @apiVersion 0.1.0
 * @apiGroup Clients
 *
 *
 * @apiHeader (Authorization) {String="Bearer: [access_token]"} Authorization The access_token
 * @apiHeader (Format) {String="application/json","application/xml"} Accept The access_token
 * @apiHeaderExample {String} Authorization Example
 *      Authorization: Bearer NTM2MDQ0ZjFhYWI4Zjk4OGMwNGVmYjg4NzJmZGU3YWI1ZWIyYzQyYWM2YTAwMzlmNzNmZDNkNzZkYzZlNTViYg
 * @apiHeaderExample {String} Accept XML
 *      Accept: application/xml
 *
 *
 * @apiParam {Number} limit Number of Clients to get
 * @apiParam {Number} offset Offset for get clients
 *
 * @apiSuccess {String} firstname Firstname of the User.
 * @apiSuccess {String} lastname  Lastname of the User.
 * @apiError {String} lastname  Lastname of the User.
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "ok",
 *          "message": "Users got OK",
 *          "data":
 *              {
 *                  "username": "ksdjfhksljd"
 *              }
 *    }
 * @apiErrorExample Unauthorized
 *    Error 401: Unauthorized
 *    {
 *          "code": 401,
 *          "message": "You are not authenticated"
 *    }
 *
 *
 */

/**
 *
 *
 * @api {put} /admin/v1/clients/{id} Update client
 * @apiName UpdateClient
 * @apiPermission SuperAdmin
 * @apiVersion 0.1.0
 * @apiGroup Clients
 *
 *
 * @apiHeader (Authorization) {String="Bearer: [access_token]"} Authorization The access_token
 * @apiHeader (Format) {String="application/json","application/xml"} Accept The access_token
 * @apiHeaderExample {String} Authorization Example
 *      Authorization: Bearer NTM2MDQ0ZjFhYWI4Zjk4OGMwNGVmYjg4NzJmZGU3YWI1ZWIyYzQyYWM2YTAwMzlmNzNmZDNkNzZkYzZlNTViYg
 * @apiHeaderExample {String} Accept XML
 *      Accept: application/xml
 *
 *
 * @apiParam {Number} limit Number of Clients to get
 * @apiParam {Number} offset Offset for get clients
 *
 * @apiSuccess {String} firstname Firstname of the User.
 * @apiSuccess {String} lastname  Lastname of the User.
 * @apiError {String} lastname  Lastname of the User.
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "ok",
 *          "message": "Users got OK",
 *          "data":
 *              {
 *                  "username": "ksdjfhksljd"
 *              }
 *    }
 * @apiErrorExample Unauthorized
 *    Error 401: Unauthorized
 *    {
 *          "code": 401,
 *          "message": "You are not authenticated"
 *    }
 *
 *
 */


/**
 *
 *
 * @api {delete} /admin/v1/clients/{id} Delete client
 * @apiName DeleteClient
 * @apiPermission SuperAdmin
 * @apiVersion 0.1.0
 * @apiGroup Clients
 *
 *
 * @apiHeader (Authorization) {String="Bearer: [access_token]"} Authorization The access_token
 * @apiHeader (Format) {String="application/json","application/xml"} Accept The access_token
 * @apiHeaderExample {String} Authorization Example
 *      Authorization: Bearer NTM2MDQ0ZjFhYWI4Zjk4OGMwNGVmYjg4NzJmZGU3YWI1ZWIyYzQyYWM2YTAwMzlmNzNmZDNkNzZkYzZlNTViYg
 * @apiHeaderExample {String} Accept XML
 *      Accept: application/xml
 *
 *
 * @apiParam {Number} limit Number of Clients to get
 * @apiParam {Number} offset Offset for get clients
 *
 * @apiSuccess {String} firstname Firstname of the User.
 * @apiSuccess {String} lastname  Lastname of the User.
 * @apiError {String} lastname  Lastname of the User.
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "ok",
 *          "message": "Users got OK",
 *          "data":
 *              {
 *                  "username": "ksdjfhksljd"
 *              }
 *    }
 * @apiErrorExample Unauthorized
 *    Error 401: Unauthorized
 *    {
 *          "code": 401,
 *          "message": "You are not authenticated"
 *    }
 *
 *
 */




/**
 * @api {get} /manager/v1/users Read users
 * @apiName ReadUsers
 * @apiDescription Creates an access token to be used in each request
 * @apiVersion 1.0.0
 * @apiGroup Users
 *
 */

