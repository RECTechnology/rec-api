<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Admin;

use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\Entity\Client;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ClientController
 * @package Telepay\FinancialApiBundle\Controller\Management\Admin
 */
class ClientsController extends BaseApiController {

    function getRepositoryName() {
        return 'TelepayFinancialApiBundle:Client';
    }

    function getNewEntity() {
        return new Client();
    }

    /**
     * @api {get} /admin/v1/clients Read clients
     * @apiName GetClients
     * @apiPermission SuperAdmin
     * @apiVersion 0.1.0
     * @apiGroup Clients
     *
     *
     * @apiHeader (Authorization) {String="Bearer: [access_token]"} Authorization The access_token
     * @apiHeaderExample {String} Authorization Example
     *      Authorization: Bearer NTM2MDQ0ZjFhYWI4Zjk4OGMwNGVmYjg4NzJmZGU3YWI1ZWIyYzQyYWM2YTAwMzlmNzNmZDNkNzZkYzZlNTViYg
     *
     *
     * @apiParam {Number} limit Number of Clients to get
     * @apiParam {Number} offset Offset for get clients
     *
     * @apiSuccess {String} status="ok" Status of the request.
     * @apiSuccess {String} lastname  Lastname of the User.
     *
     * @apiSuccessExample Success
     *    HTTP/1.1 200 OK
     *    {
     *          "status": "ok",
     *          "message": "Users got OK",
     *          "data":
     *              {
     *                  "client_id": "ksdjfhksljd",
     *                  "client_secret": "ksdjfhksljd",
     *              }
     *    }
     *
     * @apiError {String} lastname  Lastname of the User.
     *
     * @apiUse UnauthorizedError
     *
     * @Rest\View
     */
    public function indexAction(Request $request){
        return parent::indexAction($request);
    }

    /**
     * @api {get} /admin/v1/clients/{id} Read one client
     * @apiIgnore Disabled method
     * @apiName GetClient
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
     * @Rest\View
     */
    public function showAction($id){
        return parent::showAction($id);
    }

    /**
     * @api {put} /admin/v1/clients/{id} Update client
     * @apiIgnore Disabled method
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
     * @Rest\View
     */
    public function updateAction(Request $request, $id){
        return parent::updateAction($request, $id);
    }

    /**
     * @api {delete} /admin/v1/clients/{id} Delete client
     * @apiIgnore Disabled method
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
     * @Rest\View
     */
    public function deleteAction($id){
        return parent::deleteAction($id);
    }


}
