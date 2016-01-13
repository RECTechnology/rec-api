<?php

/**
 * Author: Lluis Santos
 * This file is for add in some place the Security API Documentation (OAuth2)
 */

/**
 *
 * @api {post} /admin/v1/clients Create client
 * @apiName CreateClient
 * @apiPermission SuperAdmin
 * @apiVersion 0.1.0
 * @apiGroup Clients
 *
 * @apiUse OAuth2Header
 *
 * @apiParam {String} name Name to identify this client
 * @apiParam {Number} user=1 User id to assign this client. By default will be assigned to super admin user
 *
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {String} data Id of the created client.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 201 Created
 *    {
 *          "status": "ok",
 *          "message": "Request successful",
 *          "data":
 *              {
 *                  "id": 38
 *              }
 *    }
 *
 */

/**
 *
 * @api {get} /admin/v1/clients Read clients
 * @apiName ReadClients
 * @apiPermission SuperAdmin
 * @apiVersion 0.1.0
 * @apiGroup Clients
 *
 * @apiUse OAuth2Header
 *
 * @apiParam {Number} [limit] limit=10 Number of Clients to get
 * @apiParam {Number} [offset] offset=0 Offset for get clients
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
 * @apiParam {Number} [limit] limit Number of Clients to get
 * @apiParam {Number} [offset] offset Offset for get clients
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
 * @apiParam {Number} [limit] limit Number of Clients to get
 * @apiParam {Number} [offset] offset Offset for get clients
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
 * @apiParam {Number} [limit] limit Number of Clients to get
 * @apiParam {Number} [offset] offset Offset for get clients
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
 * @api {put} /manager/v1/clients/limit/:id Update Client Limit
 * @apiName UpdateClientLimit
 * @apiPermission Admin
 * @apiVersion 0.1.0
 * @apiGroup Clients
 *
 * @apiUse OAuth2Header
 *
 * @apiParam {Number} [single] single Single transaction limit in <code>cents</code>
 * @apiParam {Number} [day] day Day transaction limit in <code>cents</code>
 * @apiParam {Number} [week] week Week transaction limit in <code>cents</code>
 * @apiParam {Number} [month] month Month transaction limit in <code>cents</code>
 * @apiParam {Number} [year] year Year transaction limit in <code>cents</code>
 * @apiParam {Number} [total] total Total transaction limit in <code>cents</code>
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 204 No Content
 *
 */

/**
 *
 * @api {put} /manager/v1/clients/:id Client add method
 * @apiName ClientAddMethod
 * @apiPermission Admin
 * @apiVersion 0.1.0
 * @apiGroup Clients
 *
 * @apiUse OAuth2Header
 *
 * @apiParam {String[]} services Swift methods
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 204 No Content
 *
 */


######################################   MANAGER USERS  ##################################################

/**
 *
 * @api {get} /manager/v1/users Read users
 * @apiName ReadUsers
 * @apiPermission SuperAdmin
 * @apiVersion 0.1.0
 * @apiGroup Users
 *
 * @apiUse OAuth2Header
 *
 * @apiParam {Number} [limit] limit=10 Number of Clients to get
 * @apiParam {Number} [offset] offset=0 Offset for get clients
 *
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {String} data Data of the request result.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "ok",
 *          "message": "Request successful",
 *          "data":
 *              {
 *                  "total": "30",
 *                  "start": "0",
 *                  "end": "10",
 *                  "elements": "...",
 *              }
 *    }
 *
 */

/**
 *
 * @api {get} /manager/v1/users/:id Read one user
 * @apiName ReadUser
 * @apiPermission SuperAdmin
 * @apiVersion 0.1.0
 * @apiGroup Users
 *
 * @apiUse OAuth2Header
 *
 * @apiParam {Number} [limit] limit=10 Number of Clients to get
 * @apiParam {Number} [offset] offset=0 Offset for get clients
 *
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {String} data Data of the request result.
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
 * @api {post} /manager/v1/users Create User
 * @apiName CreateUser
 * @apiPermission Admin
 * @apiVersion 0.1.0
 * @apiGroup Users
 *
 * @apiUse OAuth2Header
 *
 * @apiParam {Number} [limit] limit=10 Number of Clients to get
 * @apiParam {Number} [offset] offset=0 Offset for get clients
 *
 * @apiParam {String} username Username
 * @apiParam {String} email User email
 * @apiParam {String} password User password
 * @apiParam {String} repassword Password to confirm.
 * @apiParam {String} name User name
 * @apiParam {Number} group_id Group id to include this user
 *
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {String} data Data of the request result.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "ok",
 *          "message": "Request successful",
 *          "data":
 *              {
 *                  "id": 191
 *              }
 *    }
 *
 * @apiErrorExample Error
 *    HTTP/1.1 409 Conflict
 *    {
 *          "status": "error",
 *          "message": "Duplicated resource"
 *    }
 *
 * @apiErrorExample Error
 *    HTTP/1.1 400 Bad Request
 *    {
 *          "status": "error",
 *          "message": "Bad parameters"
 *    }
 *
 */

/**
 *
 * @api {delete} /manager/v1/users/:id Delete User
 * @apiName DeleteUser
 * @apiPermission Admin
 * @apiVersion 0.1.0
 * @apiGroup Users
 *
 * @apiUse OAuth2Header
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 204 No Content
 *
 */

/**
 *
 * @api {get} /manager/v1/usersbygroup/:id Read users by group
 * @apiName ReadUsersByGroup
 * @apiPermission Admin
 * @apiVersion 0.1.0
 * @apiGroup Users
 *
 * @apiUse OAuth2Header
 *
 * @apiParam {Number} [limit] limit=10 Number of Clients to get
 * @apiParam {Number} [offset] offset=0 Offset for get clients
 *
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {String} data Data of the request result.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "code": 200,
 *          "message": "Request successful",
 *          "data":
 *              {
 *                  "total": "30",
 *                  "start": "0",
 *                  "end": "10",
 *                  "elements": "...",
 *              }
 *    }
 *
 * @apiErrorExample Error
 *    HTTP/1.1 404 Not found
 *    {
 *          "status": "error",
 *          "message": "Group not found"
 *    }
 *
 * @apiErrorExample Error
 *    HTTP/1.1 403 Forbidden
 *    {
 *          "status": "error",
 *          "message": "You don't have the necessary permissions"
 *    }
 *
 *
 */

/**
 *
 * @api {put} /manager/v1/users/:id User add service
 * @apiName UserAddService
 * @apiPermission Admin
 * @apiVersion 0.1.0
 * @apiGroup Users
 *
 * @apiUse OAuth2Header
 *
 * @apiParam {String[]} service Services Names
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 204 No Content
 *
 */

/**
 *
 * @api {put} /manager/v1/users/:id Update User
 * @apiName UpdateUser
 * @apiPermission Admin
 * @apiVersion 0.1.0
 * @apiGroup Users
 *
 * @apiUse OAuth2Header
 *
 * @apiParam {String} [username] username Username
 * @apiParam {String} [email] email Email
 * @apiParam {String} [password] password Password (Requires repassword field)
 * @apiParam {String} [name] name Name
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 204 No Content
 *
 */

/**
 *
 * @api {put} /manager/v1/users/:id/image Upload Account image
 * @apiName UpdateImageUser
 * @apiPermission Admin
 * @apiVersion 0.1.0
 * @apiGroup Users
 *
 * @apiUse OAuth2Header
 *
 * @apiParam {String} base64_image Encoded image in base64
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 204 No Content
 *
 */


######################################   MANAGER GROUPS  ##################################################

/**
 *
 * @api {get} /manager/v1/groups Read groups
 * @apiName ReadGroups
 * @apiPermission SuperAdmin
 * @apiVersion 0.1.0
 * @apiGroup Groups
 *
 * @apiUse OAuth2Header
 *
 * @apiParam {Number} [limit] limit=10 Number of Clients to get
 * @apiParam {Number} [offset] offset=0 Offset for get clients
 *
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {String} data Data of the request result.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": "ok",
 *          "message": "Request successful",
 *          "data":
 *              {
 *                  "total": "30",
 *                  "start": "0",
 *                  "end": "10",
 *                  "elements": "...",
 *              }
 *    }
 *
 */

/**
 *
 * @api {get} /manager/v1/groups/:id Read group by id
 * @apiName ReadGroup
 * @apiPermission SuperAdmin
 * @apiVersion 0.1.0
 * @apiGroup Groups
 *
 * @apiUse OAuth2Header
 *
 * @apiParam {Number} [limit] limit=10 Number of Groups to get
 * @apiParam {Number} [offset] offset=0 Offset for get groups
 *
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {String} data Data of the request result.
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
 * @api {post} /manager/v1/groups Create Group
 * @apiName CreateGroup
 * @apiPermission Admin
 * @apiVersion 0.1.0
 * @apiGroup Groups
 *
 * @apiUse OAuth2Header
 *
 * @apiParam {String} name Name for the new group
 *
 * @apiSuccess {String} status Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {String} data Data of the request result.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 201 OK
 *    {
 *          "status": "ok",
 *          "message": "Request successful",
 *          "data":
 *              {
 *                  "id": 191
 *              }
 *    }
 *
 * @apiErrorExample Error
 *    HTTP/1.1 409 Conflict
 *    {
 *          "status": "error",
 *          "message": "Duplicated resource"
 *    }
 *
 * @apiErrorExample Error
 *    HTTP/1.1 400 Bad Request
 *    {
 *          "status": "error",
 *          "message": "Bad parameters"
 *    }
 *
 */

/**
 *
 * @api {delete} /manager/v1/groups/:id Delete Group
 * @apiName DeleteGroup
 * @apiPermission Admin
 * @apiVersion 0.1.0
 * @apiGroup Groups
 *
 * @apiUse OAuth2Header
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 204 No Content
 *
 */

/**
 *
 * @api {get} /manager/v1/groupsbyuser Read groups by user
 * @apiName ReadGroupsByUser
 * @apiPermission Admin
 * @apiVersion 0.1.0
 * @apiGroup Groups
 *
 * @apiUse OAuth2Header
 *
 * @apiParam {Number} [limit] limit=10 Number of Groups to get
 * @apiParam {Number} [offset] offset=0 Offset for get groups
 *
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {String} data Data of the request result.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *          "status": 200,
 *          "message": "Request successful",
 *          "data":
 *              {
 *                  "total": "30",
 *                  "start": "0",
 *                  "end": "10",
 *                  "elements": "...",
 *              }
 *    }
 *
 * @apiErrorExample Error
 *    HTTP/1.1 404 Not found
 *    {
 *          "status": "error",
 *          "message": "Group not found"
 *    }
 *
 * @apiErrorExample Error
 *    HTTP/1.1 403 Forbidden
 *    {
 *          "status": "error",
 *          "message": "You don't have the necessary permissions"
 *    }
 *
 *
 */

/**
 *
 * @api {put} /manager/v1/groups/:id Update Group
 * @apiName UpdateGroup
 * @apiPermission Admin
 * @apiVersion 0.1.0
 * @apiGroup Groups
 *
 * @apiUse OAuth2Header
 *
 * @apiParam {String} [name] name Name
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 204 No Content
 *
 */

/**
 *
 * @api {put} /manager/v1/groups/limit/:id Update Group Limit
 * @apiName UpdateGroupLimit
 * @apiPermission Admin
 * @apiVersion 0.1.0
 * @apiGroup Groups
 *
 * @apiUse OAuth2Header
 *
 * @apiParam {Number} [single] single Single transaction limit in <code>cents</code>
 * @apiParam {Number} [day] day Day transaction limit in <code>cents</code>
 * @apiParam {Number} [week] week Week transaction limit in <code>cents</code>
 * @apiParam {Number} [month] month Month transaction limit in <code>cents</code>
 * @apiParam {Number} [year] year Year transaction limit in <code>cents</code>
 * @apiParam {Number} [total] total Total transaction limit in <code>cents</code>
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 204 No Content
 *
 */

/**
 *
 * @api {put} /manager/v1/groups/fee/:id Update Group Fee
 * @apiName UpdateGroupFee
 * @apiPermission Admin
 * @apiVersion 0.1.0
 * @apiGroup Groups
 *
 * @apiUse OAuth2Header
 *
 * @apiParam {Number} [variable] variable Variable transaction fee
 * @apiParam {Number} [fixed] fixed Fixed transaction fee in <code>cents</code>
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 204 No Content
 *
 */

/**
 *
 * @api {post} /manager/v1/groups/:id Create Group
 * @apiName UserToGroup
 * @apiPermission Admin
 * @apiVersion 0.1.0
 * @apiGroup Groups
 *
 * @apiUse OAuth2Header
 *
 * @apiParam {Number} user_id Id for the user to add
 *
 * @apiSuccess {String} status Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {String} data Data of the request result.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 201 OK
 *    {
 *          "status": "ok",
 *          "message": "User added successful",
 *          "data":
 *              {}
 *    }
 *
 */

