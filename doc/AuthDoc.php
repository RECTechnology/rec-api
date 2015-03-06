<?php

/**
 * Author: Lluis Santos
 * This file is for add in some place the Security API Documentation (OAuth2)
 */


/**
 * @api {post} /oauth/v2/token Create access_token
 * @apiName OAuthToken
 * @apiDescription Creates one <code>access_token</code> to be used in each request
 * (see <a href="#api-Authentication-TestOAuth2">Test OAuth2 authentication</a>), for simpleness we will use this
 * authentication method in all the <code>sandbox/test</code> calls.
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
 * @apiDefine OAuth2Header
 * @apiHeader (Headers) {String="Bearer: access_token"} Authorization The bearer <code>access_token</code>.
 * @apiHeaderExample {String} OAuth2 Header Example
 *      Authorization: Bearer NTM2MDQ0ZjFhYWI4Zjk4OGMwNGVmYjg4NzJmZGU3YWI1ZWIyYzQyYWM2YTAwMzlmNzNmZDNkNzZkYzZlNTViYg
 *
 */


/**
 * @apiDefine SignatureHeader
 * @apiHeader (Authentication) {String} X-Signature
 * Signed request authentication header
 *
 * @apiExample {python} Signed requests authentication schema
 * # Authenticaton scheme explained in pseudo-code
 *
 * # The authentication credentials provided in https://cp.telepay.net/user/account
 * access_key = "edbeb673024f2d0e23752e2814ca1ac4c589f761"
 * access_secret = "wlqDEET8uIr5RN00AMuuceI9LLKMTNLpzlETlX3djVg="
 *
 * # access_secret bytes, used later as a key to make the signature
 * access_secret_bin = base64_decode(access_secret)
 *
 * # Number used for a single use, see http://en.wikipedia.org/wiki/Cryptographic_nonce
 * nonce = "1570156405"
 *
 * # Current unix timestamp, see http://en.wikipedia.org/wiki/Unix_time
 * timestamp = "1411000260"
 *
 * # Authentication scheme version, now 1
 * version = "1"
 *
 * # Concat access_key, nonce and timestamp as a new string
 * string_to_sign = access_key + nonce + timestamp
 *
 * # Encrypt the above string with sha256 hash hmac algorithm and the access_secret_bin as a key
 * signature = hash_hmac_256(string_to_sign, access_secret_bin)
 *
 * # Build and add X-Signature header to the http request (without line endings)
 * 'X-Signature: Signature access-key="'+access_key+'",
 *      nonce="'+nonce+'",
 *          timestamp="'+timestamp+'",
 *              version="1",
 *                  signature="'+signature+'"'
 *
 * # The resulting header should be like (without line endings)
 * X-Signature: Signature access-key="edbeb673024f2d0e23752e2814ca1ac4c589f761",
 *      nonce="1570156405",
 *          timestamp="1411000260",
 *              version="1",
 *                  signature="a481af8644e99b120a312009176a115e1673d81f12ccf12e178a5cb0a59ea9db"
 *
 *
 */


/**
 * @apiDefine SampleResponse
 * @apiParam {String} param Sample parameter
 * @apiSuccess {String} param The sent parameter
 * @apiSuccess {Object} server_time The server time
 * @apiSuccess {Integer} server_time.secs The server time seconds
 * @apiSuccess {Integer} server_time.usec The server time time part micro seconds
 *
 */


/**
 *
 * @apiDefine SampleResponseExample
 * @apiSuccessExample {json} Success
 * {
 *   "code": 200,
 *   "message": "Successful",
 *   "data": {
 *       "param": "hello, this is a testing parameter",
 *       "server_time": {
 *           "sec": 1425605649,
 *           "usec": 27000
 *       }
 *   }
 * }
 *
 */

/**
 *
 * @apiDefine NotAuthenticated
 * @apiErrorExample {json} Unauthorized
 * Error 401: Unauthorized
 * {
 *     "code": 401,
 *     "message": "You are not authenticated"
 * }
 *
 */

/**
 *
 * @apiDefine BadSignature
 * @apiErrorExample {json} Bad signature
 * Error 403: Forbidden
 *
 */




/**
 * @api {post} /services/v1/sample Sample OAuth 2.0
 * @apiName TestOAuth2
 * @apiDescription Creates a transaction to the sample service the OAuth2 <code>access_token</code>
 * (obtained in <a href="#api-Authentication-OAuthToken">Create access_token</a> section).
 * @apiVersion 0.1.0
 * @apiGroup Authentication
 * @apiUse SampleResponse
 * @apiUse SampleResponseExample
 * @apiUse NotAuthenticated
 * @apiUse OAuth2Header
 *
 */



/**
 * @api {post} /services/v1/sample Sample signature
 * @apiName TestSignedRequest
 * @apiDescription Creates a transaction to the sample service with tie signed request, this authentication schema
 * allows to make a single call (OAuth 2.0 needs user interaction and needs two calls) is used by all the Telepay
 * SDKs and should be used for <a href="http://en.wikipedia.org/wiki/Machine_to_machine">machine-to-machine</a>
 * interaction.
 * @apiVersion 0.1.1
 * @apiGroup Authentication
 * @apiUse SampleResponse
 * @apiUse BadSignature
 * @apiUse SignatureHeader
 *
 */