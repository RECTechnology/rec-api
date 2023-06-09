<?php

/**
 * Author: Lluis Santos
 * This file is for add in some place the Security API Documentation (OAuth2)
 */


/**
 * @api {post} /oauth/v2/token OAuth2 Token
 * @apiName OAuthToken
 * @apiDescription Creates one <code>access_token</code> to be used in each request, for simpleness we will use this
 * authentication method in all the <code>sandbox/test</code> calls.
 * @apiVersion 1.0.0
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
 *    HTTP/1.1 200 OK
 *    {
 *          "access_token": "YmIwNDY5M2JjNWVlNWUyN2E5NWE4ZDBmMGVkNWU1MjU0NmE3N2FkMzQ1MWNkNjM1ZjJhNWY2ZGFmZTI5NTA1ZQ",
 *          "expires_in": 3600,
 *          "token_type": "bearer",
 *          "scope": "panel"
 *    }
 * @apiSuccessExample Password Success
 *    HTTP/1.1 200 OK
 *    {
 *          "access_token": "NTM2MDQ0ZjFhYWI4Zjk4OGMwNGVmYjg4NzJmZGU3YWI1ZWIyYzQyYWM2YTAwMzlmNzNmZDNkNzZkYzZlNTViYg",
 *          "expires_in": 3600,
 *          "token_type": "bearer",
 *          "scope": "panel",
 *          "refresh_token": "MzA3MzBjZGU4MWJkOWEyZDI0NTQ1NDhiYjMyZDFkMjU3MzdiOTZkMjUzN2Q2NTMyMTc1NjI2ZDA3OGFmYjQ2Mw"
 *    }
 *
 * @apiErrorExample Invalid Grant Type
 *    HTTP/1.1 400: Bad Request
 *    {
 *          "error": "invalid_request",
 *          "error_description": "Invalid grant_type parameter or parameter missing"
 *    }
 * @apiErrorExample Invalid Scope
 *    HTTP/1.1 400: Bad Request
 *    {
 *          "error": "invalid_scope",
 *          "error_description": "An unsupported scope was requested."
 *    }
 * @apiErrorExample Invalid Credentials
 *    HTTP/1.1 400: Bad Request
 *    {
 *          "error": "invalid_grant",
 *          "error_description": "Invalid username and password combination"
 *    }
 */


/**
 * @apiDefine OAuth2Header
 * @apiHeader (Headers) {String="Bearer: access_token"} Authorization The bearer <code>access_token</code>.
 * @apiHeaderExample {String} OAuth2 Header Example
 *      Authorization: Bearer NTM2MDQ0ZjFhYWI4Zjk4OGMwNGVmYjg4NzJmZGU3YWI1ZWIyYzQyYWM2YTAwMzlmNzNmZDNkNzZkYzZlNTViYg
 *
 */

/**
 *
 * @apiDefine NotAuthenticated
 * @apiError {String} status  error
 * @apiError {String} message  The description of the error.
 * @apiErrorExample {json} Unauthorized
 * HTTP/1.1 401 Unauthorized
 * {
 *    "status": "error",
 *    "message": "You are not authenticated"
 * }
 *
 */

/**
 *
 * @apiDefine BadSignature
 * @apiErrorExample {json} Bad signature
 * HTTP/1.1 403 Forbidden
 *
 */
