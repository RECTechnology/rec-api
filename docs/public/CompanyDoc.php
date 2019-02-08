<?php


/**
 * @api {get} /company/v1/products List Products
 * 
 * @apiName Products
 * @apiDescription Gets the products for company 
 * @apiPermission User
 * @apiVersion 1.0.0
 * @apiGroup Company
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
 *                 "offered": [],
 *                 "needed": []
 *              }
 *    }
 *
 */

 
/**
 * @api {get} /company/v1/list_categories List Categories
 * 
 * @apiName Categories
 * @apiPermission User
 * @apiVersion 1.0.0
 * @apiGroup Company
 *
 * @apiUse OAuth2Header
 * 
 * @apiParam {Number} min_lat Specify the minimum latitud
 * @apiParam {Number} max_lat Specify the maximum latitud
 * @apiParam {Number} min_lon Specify the minimum longitud
 * @apiParam {Number} max_lon Specify the maximum longitud
 * @apiParam {Boolean} only_offers Only show offers
 * @apiParam {Number = 0, 1} retailer Only show retailers
 * @apiParam {Number = 0, 1} wholesales Only show wholesales
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
 *                "elements": [],
 *                "total": 0 
 *              }
 *    }
 * 
 * @apiErrorExample Incorrect Filters
 *    HTTP/1.1 400 
 *    {
 *          "error": "invalid_request",
 *          "message": "Filters options are incorrect"
 *    }
 *
 */
