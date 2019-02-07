<?php
/**
 * @api {post} /register/v1/commerce/mobile User Registration
 * @apiName RegisterUser
 * @apiDescription Registers a user into the system
 * @apiVersion 1.0.0
 * @apiGroup Other
 *
 * @apiParam {String} name
 * @apiParam {String} password
 * @apiParam {String} repassword
 * @apiParam {String} phone
 * @apiParam {String} prefix
 * @apiParam {String} dni
 * @apiParam {String} dni_confirmation
 * @apiParam {String = "PRIVATE", "COMPANY"} type
 * @apiParam {String = "RETAILER", "WHOLESALE"} subtype
 * @apiParam {String} email
 * @apiParam {String} pin
 * @apiParam {String} repin
 * @apiParam {String} company_name
 * @apiParam {String} company_cif
 * @apiParam {String} company_email
 * @apiParam {String} company_prefix
 * @apiParam {String} company_phone
 * @apiParam {String} security_question
 * @apiParam {String} security_answer
 * 
 * @apiSuccess {String} access_token The access token for make requests.
 * @apiSuccess {String} expires_in The life time of the requested access token.
 * @apiSuccess {String} token_type The type of the access token.
 * @apiSuccess {String} scope=panel The granted scope in the application.
 * @apiSuccess {String} [refresh_token]  The refresh token for request another access token later.
 *
 * @apiError invalid_password Password must be longer than 6 characters
 * @apiError password_mismatch Password and repassword are differents
 * @apiError bad_params Bad parameters
 * @apiError invalid_param NIF not valid
 * @apiError phone_registered phone already registered
 * @apiError dni_registered dni already registered
 * @apiError username_registered Username already registered
 * @apiError email_invalid Email is invalid
 * @apiError invalid_question Security question is too large or too simple
 * 
 * @apiErrorExample Type not valid
 * HTTP/1.1 404: Bad Request
 *    {
 *      "error": "invalid_request",
 *      "error_description": "Type not valid"
 *    }
 * 
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {}
 */
