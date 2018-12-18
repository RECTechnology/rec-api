<?php
/**
 * @api {get} /map/v1/search List
 * @apiName ListMap
 * @apiDescription List Map Commerces  
 * @apiPermission User
 * @apiVersion 1.0.0
 * @apiGroup Map
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
 * @apiParam {String} search Search query
 * 
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 * {
 *    "data": {
 *        "total": 1,
 *        "elements": [
 *            {
 *                "name": "askdalsdñ",
 *                "company_image": "https://prefilesbm.blockroot.com/5ad8d1fde432c.jpeg",
 *                "latitude": 41.396113,
 *                "longitude": 2.163037,
 *                "country": "",
 *                "city": "Barcelona",
 *                "zip": "",
 *                "street": "España",
 *                "street_type": "calle",
 *                "address_number": "1",
 *                "phone": "654654644",
 *                "prefix": "34",
 *                "type": "COMPANY",
 *                "subtype": "RETAILER",
 *                "description": "Hola",
 *                "schedule": "",
 *                "public_image": "https://prefilesbm.blockroot.com/5ad8d232ca1c3.jpeg",
 *                "offers": [],
 *                "total_offers": 0
 *            }
 *          ]
 *    }
 * }
 *
 */


 /**
 * @api {get} /map/v1/search Search
 * @apiName SearchMap
 * @apiDescription Searches Map Commerces  
 * @apiPermission User
 * @apiVersion 1.0.0
 * @apiGroup Map
 *
 * @apiUse OAuth2Header
 * 
 * @apiParam {Number = 0, 1} retailer Only show retailers
 * @apiParam {Number = 0, 1} wholesales Only show wholesales
 * @apiParam {String} search Search query
 * 
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 * {
 *    "data": {
 *        "total": 1,
 *        "elements": [
 *            {
 *                "name": "askdalsdñ",
 *                "company_image": "https://prefilesbm.blockroot.com/5ad8d1fde432c.jpeg",
 *                "latitude": 41.396113,
 *                "longitude": 2.163037,
 *                "country": "",
 *                "city": "Barcelona",
 *                "zip": "",
 *                "street": "España",
 *                "street_type": "calle",
 *                "address_number": "1",
 *                "phone": "654654644",
 *                "prefix": "34",
 *                "type": "COMPANY",
 *                "subtype": "RETAILER",
 *                "description": "Hola",
 *                "schedule": "",
 *                "public_image": "https://prefilesbm.blockroot.com/5ad8d232ca1c3.jpeg",
 *                "offers": [],
 *                "total_offers": 0
 *            }
 *          ]
 *    }
 * }
 *
 */