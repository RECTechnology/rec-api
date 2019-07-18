<?php

namespace REC\FinancialApiBundle\Test\Legacy;

use REC\FinancialApiBundle\Test\BaseApiTest;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class StatusCodesTest
 * @package REC\FinancialApiBundle\Test\Security\Perimeter
 */
class LegacyCallsExistsTest extends WebTestCase {

    const API_LEGACY_ROUTES = [
        "/admin/v1/delegated_change_data/csv" => ["POST"],
        "/admin/v1/delegated_changes" => ["GET","POST"],
        "/admin/v1/delegated_changes/{id}" => ["GET", "PUT", "DELETE"],
        "/admin/v1/delegated_change_data" => ["GET", "POST"],
        "/admin/v1/delegated_change_data/{id}" => ["GET", "PUT", "DELETE"],
        "/admin/v1/kyc/uploads/{account_id}" => ["GET"],
        "/admin/v1/kyc/{account_id}/lemon/upload" => ["POST"],
        "/admin/v1/kyc/lemon/{id}" => ["POST"],
        "/admin/v1/kyc/file/{tag}/{id}" => ["POST", "DELETE"],
        "/admin/v1/user/{id}/kyc" => ["PUT"],
        "/admin/v1/users/{id}" => ["PUT"],
        "/admin/v1/companies/{id}" => ["DELETE", "PUT"],
        "/admin/v1/user/{id}/phone" => ["PUT"],
        "/admin/v2/groups/{group_id}/{user_id}" => ["DELETE"],
        "/admin/v1/activeuser/{id_user}" => ["POST"],
        "/admin/v1/deactiveuser/{id_user}" => ["POST"],
        "/admin/v1/treasure_withdrawals" => ["GET", "POST"],
        "/admin/v1/treasure_withdrawals/{id}" => ["GET"],
        "/admin/v1/treasure_withdrawal_validations" => ["GET", "POST"],
        "/admin/v1/treasure_withdrawal_validations/{id}" => ["GET", "PUT"],
        "/manager/v2/groups" => ["GET"],
        "/company/{id}/v1/wallet/transactions" => ["GET"],
        "/company/v1/category?category={category}" => ["PUT"],
        "/manager/v1/groupsbyuser/{user_id}" => ["GET"],
        "/manager/v1/usersbygroup/{account_id}" => ["GET"],
        "/company/v1/account/group_id/image" => ["PUT"],
        "/manager/v1/groupsrole/{group_id}/{user_id}" => ["POST"],
        "/groups/v1/show/{group_id}" => ["GET"],
        "/company/v1/list_categories" => ["GET"],
        "/manager/v1/groups/{account_id}" => ["GET"],
        "/user/v1/wallet/exchangers" => ["GET"],
        "/user/v1/account" => ["GET", "PUT"],
        "/user/v1/upload_file" => ["POST"],
        "/user/v1/save_kyc" => ["POST"],
        "/user/v1/companies" => ["GET"],
        "/user/v1/activegroup" => ["PUT"],
        "/user/v1/question" => ["GET"],
        "/user/v1/pin" => ["PUT"],
        "/user/v1/public_phone" => ["PUT"],
        "/user/v1/public_phone_list" => ["GET"],
        "/user/v1/new/account" => ["POST"],
        "/users/v1/usersbygroup/{account_id}" => ["GET"],
        "/user/v2/wallet/transactions" => ["GET"],
        "/company/v1/offer" => ["GET", "POST"],
        "/company/v1/offer/{offer_id}" => ["GET", "PUT", "DELETE"],
        "/company/v1/products" => ["GET", "PUT", "DELETE"],
        "/company/v1/category" => ["PUT"],
        "/manager/v1/account/{account_id}" => ["POST", "PUT", "DELETE"],
        "/manager/v1/groups/{id}" => ["POST"],
        "/public/map/v1/search" => ["GET"],
        "/public/map/v1/list" => ["GET"],
    ];


    private function getAllRoutes(){
        $client = static::createClient();
        /** @var RouterInterface $router */
        $router = $client->getKernel()->getContainer()->get('router');
        return $router->getRouteCollection()->all();
    }

    public function testAllLegacyCallsArePresent(){
        $routes = $this->getAllRoutes();

        foreach(self::API_LEGACY_ROUTES as $legacyRoute => $legacyRouteMethods){
            $legacyRouteIsFound = false;
            foreach ($routes as $route){
                if($route->getPath() === $legacyRoute){
                    foreach($route->getMethods() as $method){
                        self::assertTrue(in_array($method, $legacyRouteMethods), "Route {$legacyRoute} methods mismatch");
                    }
                    $legacyRouteIsFound = true;
                }
            }
            self::assertTrue($legacyRouteIsFound);
        }

    }
}
