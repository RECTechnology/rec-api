<?php
/**
 * @api {get} /map/v1/list List
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

/**
 * @api {get} /map/v2/search Search_v2
 * @apiName SearchMap_v2
 * @apiDescription Searches Map Commerces
 * @apiPermission User
 * @apiVersion 1.0.0
 * @apiGroup Map
 *
 * @apiUse OAuth2Header
 *
 *
 * @apiParam {Number} limit Specify the maximum of Commerces to get (default 10)
 * @apiParam {Number} ofset Specify the N first of founded Commerces to skip (default 0)
 * @apiParam {Number} min_lat Specify the minimum latitud
 * @apiParam {Number} max_lat Specify the maximum latitud
 * @apiParam {Number} min_lon Specify the minimum longitud
 * @apiParam {Number} max_lon Specify the maximum longitud
 * @apiParam {Boolean} only_offers Only show offers
 * @apiParam {String} search Search query
 *
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
 *            }
 *          ]
 *    }
 * }
 *
 */



/**
 * @api {get} /admin/v1/map/search AdminSearch
 * @apiName AdminSearch
 * @apiDescription Searches Map Commerces showing all information
 * @apiPermission Admin
 * @apiVersion 1.0.0
 * @apiGroup Map
 *
 * @apiUse OAuth2Header
 *
 *
 * @apiParam {Number} limit Specify the maximum of Commerces to get (default 10)
 * @apiParam {Number} ofset Specify the N first of founded Commerces to skip (default 0)
 * @apiParam {Number} min_lat Specify the minimum latitud
 * @apiParam {Number} max_lat Specify the maximum latitud
 * @apiParam {Number} min_lon Specify the minimum longitud
 * @apiParam {Number} max_lon Specify the maximum longitud
 * @apiParam {Boolean} only_offers Only show offers
 * @apiParam {String} search Search query
 *
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 * {
 *    "data": {
 *        "total": 1,
 *        "elements": [
 *            {
 *               "id": 1176,
 *               "name": "abc",
 *               "roles": [
 *                   "ROLE_SUPER_ADMIN"
 *               ],
 *               "kyc_manager": {
 *                   "id": 1044,
 *                   "username": "12345840S",
 *                   "email": "email@email11.com",
 *                   "locked": false,
 *                   "roles": [],
 *                   "dni": "12345840S",
 *                   "access_key": "066b936b3793ade0ee43802b3dbe016b283a2dbe",
 *                   "access_secret": "gK55DWJA9o4GWNECHkoTgM6FwCFxw3khI8oSL1J9hoQ=",
 *                   "name": "test",
 *                   "phone": "64357121",
 *                   "public_phone": true,
 *                   "prefix": 0,
 *                   "profile_image": "files/5c8bc48615cc0.jpeg",
 *                   "two_factor_authentication": true,
 *                   "group_data": [],
 *                   "kyc_validations": {
 *                       "id": 1027,
 *                       "name": "test",
 *                       "last_name": "",
 *                       "full_name_validated": false,
 *                       "email": "email@email.com",
 *                       "email_validated": false,
 *                       "phone": "{\"prefix\":\"00\",\"number\":\"64357121\"}",
 *                       "phone_validated": true,
 *                       "date_birth": "",
 *                       "date_birth_validated": false,
 *                       "document_front": "files/5c8bc48616353.jpeg",
 *                       "document_front_status": "pending",
 *                       "document_rear": "files/5c8bc48616517.jpeg",
 *                       "document_rear_status": "pending",
 *                       "document_validated": false,
 *                       "country": "",
 *                       "country_validated": false,
 *                       "neighborhood": "",
 *                       "street_type": "",
 *                       "street_number": "",
 *                       "street_name": "",
 *                       "address_validated": false,
 *                       "proof_of_residence": false,
 *                       "gender": "",
 *                       "nationality": ""
 *                   },
 *                   "created": "2019-02-08T12:01:12+00:00"
 *               },
 *               "company_image": "files/5c8ba5766750d.jpeg",
 *               "rec_address": "CWuHnCbLJVjx72cuGb2hzRhxNxnDCSp6HQ",
 *               "offered_products": "",
 *               "needed_products": "",
 *               "limits": [],
 *               "commissions": [
 *                   {
 *                       "id": 2425,
 *                       "fixed": 0,
 *                       "variable": 0,
 *                       "service_name": "rec-out",
 *                       "currency": "REC"
 *                   },
 *                   {
 *                       "id": 2426,
 *                       "fixed": 0,
 *                       "variable": 0,
 *                       "service_name": "rec-in",
 *                       "currency": "REC"
 *                   }
 *               ],
 *               "wallets": [
 *                   {
 *                       "id": 2174,
 *                      "currency": "REC",
 *                       "available": 176000000,
 *                       "balance": 176000000,
 *                       "backup": 0,
 *                       "old_balance": 0,
 *                       "blockchain": 0,
 *                       "blockchain_pending": 0,
 *                       "status": "enabled"
 *                   },
 *                   {
 *                       "id": 2175,
 *                       "currency": "EUR",
 *                       "available": 2000000,
 *                       "balance": 2000000,
 *                       "backup": 0,
 *                       "old_balance": 0,
 *                       "blockchain": 0,
 *                       "blockchain_pending": 0,
 *                       "status": "enabled"
 *                   }
 *               ],
 *              "limit_counts": [],
 *               "access_key": "cfdc684b2b688e85287cbf85df42a674cb33e625",
 *               "access_secret": "y0yHUVHlQLe0EwMVZAaZmCZZ6m+VyDLXaS2uysy2/Qs=",
 *               "allowed_methods": [],
 *               "limit_configuration": [],
 *               "offers": [],
 *               "cif": "11168535B",
 *               "prefix": "00",
 *               "phone": "12345678",
 *               "zip": "",
 *               "email": "abc@abc.com",
 *               "city": "",
 *               "country": "",
 *              "latitude": 0.5,
 *               "longitude": 1,
 *               "fixed_location": false,
 *               "web": "",
 *               "address_number": "",
 *               "neighborhood": "",
 *               "association": "",
 *               "observations": "",
 *               "street": "",
 *               "street_type": "",
 *               "comment": "",
 *               "type": "COMPANY",
 *               "lemon_id": "",
 *               "subtype": "WHOLESALE",
 *               "description": "",
 *               "schedule": "",
 *               "public_image": "files/5c8ba5766750d.jpeg",
 *               "active": true,
 *               "cash_in_tokens": [
 *                   {
 *                       "id": 1081,
 *                       "created": "2019-02-08T12:01:12+00:00",
 *                       "updated": "2019-02-08T12:01:12+00:00",
 *                       "token": "CWuHnCbLJVjx72cuGb2hzRhxNxnDCSp6HQ",
 *                      "method": "rec-in",
 *                      "currency": "REC",
 *                       "expires_in": -1,
 *                       "label": "REC account",
 *                       "status": "active",
 *                       "deposits": []
 *                   }
 *               ],
 *               "tier": 0,
 *               "company_token": "5c5d6f88456e1",
 *               "on_map": false
 *
 *
 *
 *              }
 *          ]
 *    }
 * }
 *
 */

/**
 * @api {put} /admin/v1/map/visibility/ SetVisibility
 * @apiName SetVisibility
 * @apiDescription Set Map visibility
 * @apiPermission Admin
 * @apiVersion 1.0.0
 * @apiGroup Map
 *
 * @apiUse OAuth2Header
 *
 *
 * @apiParam {Number = 0, 1} on_map Specify the Commerces visibility on map

 *
 *
 * @apiSuccessExample Success
 *    HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Visibility changed successfully",
 *   "data": []
 *  }
 *
 */