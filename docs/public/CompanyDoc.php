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


/**
 * @api {post} /company/v1/offer New Offer
 *
 * @apiName New Offer
 * @apiDescription create a new Offer
 * @apiPermission User
 * @apiVersion 1.0.0
 * @apiGroup Company
 *
 * @apiUse OAuth2Header
 *
 *
 * @apiParam {DateTime} start Date when the offer starts
 * @apiParam {DateTime} end Date when the offer ends
 * @apiParam {Number} discount Discount percentage
 * @apiParam {String} description Offer description
 * @apiParam {String} image Image file URL
 *
 *
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {Array} data Data of the request result.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *    {
 *       "data": {
 *           "id": 95,
 *           "created": "2019-03-21T15:36:45+00:00",
 *           "start": "2019-01-01T00:00:00+00:00",
 *           "end": "2019-05-01T00:00:00+00:00",
 *           "discount": "0.1",
 *           "description": "description4",
 *           "image": "files/5c93af8d5f101.png",
 *           "active": true
 *       },
 *       "status": "ok",
 *       "message": "Offer registered successfully"
 *   }
 *
 */

/**
 * @api {get} /company/v1/offer/ Get Company Offers
 *
 * @apiName Get Company Offers
 * @apiDescription List all Company Offers
 * @apiPermission User
 * @apiVersion 1.0.0
 * @apiGroup Company
 *
 * @apiUse OAuth2Header
 *
 *
 *
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {Array} data Data of the request result.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *
 *
 *   "data": [
 *       {
 *           "id": 94,
 *           "created": "2019-03-21T15:23:37+00:00",
 *           "start": "2019-01-01T00:00:00+00:00",
 *           "end": "2019-05-01T00:00:00+00:00",
 *           "discount": "0.1",
 *           "description": "description4",
 *           "image": "files/5c93ac798c120.jpeg",
 *           "active": false
 *       },
 *       {
 *           "id": 95,
 *           "created": "2019-03-21T15:36:45+00:00",
 *           "start": "2019-01-01T00:00:00+00:00",
 *           "end": "2019-05-01T00:00:00+00:00",
 *           "discount": "0.1",
 *           "description": "description4",
 *           "image": "files/5c93af8d5f101.png",
 *           "active": false
 *       }
 *   ],
 *   "status": "ok",
 *   "message": "Request successfull"
 *   }
 *
 */






/**
 * @api {put} /company/v1/offer/{id} Update Offer
 *
 * @apiName Update Offer
 * @apiDescription Update a Offer
 * @apiPermission User
 * @apiVersion 1.0.0
 * @apiGroup Company
 *
 * @apiUse OAuth2Header
 *
 *
 * @apiParam {DateTime} start Date when the offer starts
 * @apiParam {DateTime} end Date when the offer ends
 * @apiParam {Number} discount Discount percentage
 * @apiParam {String} description Offer description
 * @apiParam {String} image Image file URL
 *
 *
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {Array} data Data of the request result.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *
 *   {
 *       "data": [],
 *       "status": "ok",
 *       "message": "Offer updated successfully"
 *   }
 *
 */

/**
 * @api {del} /company/v1/offer/{id} Delete Offer
 *
 * @apiName Delete Offer
 * @apiDescription Delete a Offer
 * @apiPermission User
 * @apiVersion 1.0.0
 * @apiGroup Company
 *
 * @apiUse OAuth2Header
 *
 *

 *
 *
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {Array} data Data of the request result.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *
 *   {
 *       "data": [],
 *       "status": "ok",
 *       "message": "Deleted successfully"
 *   }
 *
 */


/**
 * @api {put} /company/v1/products?offered_products={products}&needed_products={products} Update Products
 *
 * @apiName Update Products
 * @apiDescription Update Products
 * @apiPermission User
 * @apiVersion 1.0.0
 * @apiGroup Company
 *
 * @apiUse OAuth2Header
 *
 *
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {Array} data Data of the request result.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *
 *   {
 *   "data": {
 *       "offered_products": "rosquilletas,galletas",
 *       "needed_products": "chocolate,cafe"
 *   },
 *   "status": "ok",
 *   "message": "Request successfull"
 *    }
 *
 */


/**
 * @api {get} /company/v1/products Get Products
 *
 * @apiName Get Products
 * @apiDescription List Products
 * @apiPermission User
 * @apiVersion 1.0.0
 * @apiGroup Company
 *
 * @apiUse OAuth2Header
 *
 *
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {Array} data Data of the request result.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *
 *   {
 *   "data": {
 *       "offered_products": "rosquilletas,galletas",
 *       "needed_products": "chocolate,cafe"
 *   },
 *   "status": "ok",
 *   "message": "Request successfull"
 *    }
 *
 */

/**
 * @api {get} /company/v1/list_categories List Categories
 *
 * @apiName List Categories
 * @apiDescription List Categories
 * @apiPermission User
 * @apiVersion 1.0.0
 * @apiGroup Company
 *
 * @apiUse OAuth2Header
 *
 *
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {Array} data Data of the request result.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *
 *  {
 *   "data": [
 *       {
 *           "id": 1,
 *           "cat": "Alimentació",
 *           "eng": "Food",
 *           "esp": "Alimentación"
 *       },
 *       {
 *           "id": 2,
 *           "cat": "Forn",
 *           "eng": "Bakery",
 *           "esp": "Panadería"
 *       },
 *       {
 *           "id": 3,
 *           "cat": "Supers",
 *           "eng": "Supermarket",
 *           "esp": "Supermercado"
 *       },
 *       {
 *           "id": 4,
 *           "cat": "Drogueria",
 *           "eng": "Drugstore",
 *           "esp": "Droguería"
 *       }
 *       ],
 *       "status": "ok",
 *       "message": "Request successfull"
 *   }
 *
 */


/**
 * @api {put} //company/v1/category?category={id} Set Category
 *
 *
 * @apiName Set Category
 * @apiDescription Set Company Category
 * @apiPermission User
 * @apiVersion 1.0.0
 * @apiGroup Company
 *
 * @apiUse OAuth2Header
 *
 *
 * @apiSuccess {String} code Status of the request.
 * @apiSuccess {String} message Description of the request result.
 * @apiSuccess {Array} data Data of the request result.
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 *
 *  {
 *       "data": {
 *       "id": 12,
 *       "cat": "Esport",
 *       "eng": "Sport",
 *       "esp": "Deporte"
 *   },
 *   "status": "ok",
 *   "message": "Request successfull"
 *   }
 *
 */

