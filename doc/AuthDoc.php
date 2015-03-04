<?php

/**
 * Author: Lluis Santos
 * This file is for add in some place the Security API Documentation (OAuth2)
 */


/**
 * @api {post} /oauth/v2/token OAuth2 Token
 * @apiName OAuthToken
 * @apiDescription Creates an access token to be used in each request
 * @apiVersion 2.0.0
 * @apiGroup Authentication
 *
 * @apiParam {String="client_credentials","password","refresh_token"} grant_type The OAuth2 grant type
 * @apiParam {String} client_id The provided Client ID
 * @apiParam {String} client_secret The provided Client Secret
 * @apiParam {String} [username] The username or email of the user, required with grant_type password
 * @apiParam {String} [password] The password of the user, required with grant_type password
 * @apiParam {String} [scope=panel] The wanted scope
 *
 * @apiSuccess {String} access_token The access token for make requests.
 * @apiSuccess {String} expires_in The life time of the requested access token.
 * @apiSuccess {String} token_type The type of the access token.
 * @apiSuccess {String} scope=panel The granted scope in the application.
 * @apiSuccess {String} [refresh_token]  The refresh token for request another access token later.
 *
 * @apiError {String} error  The error occurred.
 * @apiError {String} error_description  The description of the error.
 *
 * @apiSuccessExample Client Credentials Success
 *    {
 *          "access_token": "YmIwNDY5M2JjNWVlNWUyN2E5NWE4ZDBmMGVkNWU1MjU0NmE3N2FkMzQ1MWNkNjM1ZjJhNWY2ZGFmZTI5NTA1ZQ",
 *          "expires_in": 3600,
 *          "token_type": "bearer",
 *          "scope": "panel"
 *    }
 * @apiSuccessExample Password Success
 *    {
 *          "access_token": "NTM2MDQ0ZjFhYWI4Zjk4OGMwNGVmYjg4NzJmZGU3YWI1ZWIyYzQyYWM2YTAwMzlmNzNmZDNkNzZkYzZlNTViYg",
 *          "expires_in": 3600,
 *          "token_type": "bearer",
 *          "scope": "panel",
 *          "refresh_token": "MzA3MzBjZGU4MWJkOWEyZDI0NTQ1NDhiYjMyZDFkMjU3MzdiOTZkMjUzN2Q2NTMyMTc1NjI2ZDA3OGFmYjQ2Mw"
 *    }
 *
 * @apiErrorExample Invalid Grant Type
 *    Error 400: Bad Request
 *    {
 *          "error": "invalid_request",
 *          "error_description": "Invalid grant_type parameter or parameter missing"
 *    }
 * @apiErrorExample Invalid Scope
 *    Error 400: Bad Request
 *    {
 *          "error": "invalid_scope",
 *          "error_description": "An unsupported scope was requested."
 *    }
 * @apiErrorExample Invalid Credentials
 *    Error 400: Bad Request
 *    {
 *          "error": "invalid_grant",
 *          "error_description": "Invalid username and password combination"
 *    }
 */

/**
 * @apiDefine OAuth2Header Authentication Header
 * @apiHeader (Bearer) {String} Authorization=Bearer:
 * OAuth2 access token authentication header
 *
 */

/**
 * @apiDefine SignatureHeader
 * @apiHeader (Signature) {String} X-Signature
 * Signed request authentication header
 *
 */



/**
 * @api {post} /services/v1/sample Signed request
 * @apiName SignedRequest
 * @apiDescription Creates a transaction to the sample service with tie signed request, this authentication schema
 * is used by all the Telepay SDKs and should be used from machine-to-machine interaction.
 * @apiVersion 1.0.0
 * @apiGroup Authentication
 * @apiUse SignatureHeader
 * @apiUse OAuth2Header
 */