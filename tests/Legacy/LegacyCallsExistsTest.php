<?php

namespace App\Tests\Legacy;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;
use App\Tests\BaseApiTest;

class LegacyCallsExistsTest extends BaseApiTest {

    const API_LEGACY_ROUTES = [
        "/admin/v1/activeuser/{id}" => ["POST"],
        "/admin/v1/companies/{id}" => ["DELETE", "PUT"],
        "/admin/v1/deactiveuser/{id}" => ["POST"],
        "/admin/v1/delegated_change_data" => ["GET", "POST"],
        "/admin/v1/delegated_change_data/csv" => ["POST"],
        "/admin/v1/delegated_change_data/{id}" => ["GET", "PUT", "DELETE"],
        "/admin/v1/delegated_changes" => ["GET","POST"],
        "/admin/v1/delegated_changes/{id}" => ["GET", "PUT", "DELETE"],
        "/admin/v1/groups/{group_id}/{user_id}" => ["DELETE"],
        "/admin/v1/kyc/file/{tag}/{id}" => ["POST", "DELETE"],
        "/admin/v1/kyc/lemon/{id}" => ["POST"],
        "/admin/v1/kyc/uploads/{id}" => ["GET"],
        "/admin/v1/kyc/{account_id}/lemon/upload" => ["POST"],
        "/admin/v1/treasure_withdrawal_validations" => ["GET", "POST"],
        "/admin/v1/treasure_withdrawal_validations/{id}" => ["GET", "PUT"],
        "/admin/v1/treasure_withdrawals" => ["GET", "POST"],
        "/admin/v1/treasure_withdrawals/{id}" => ["GET"],
        "/admin/v1/user/{id}/kyc" => ["PUT"],
        "/admin/v1/user/{id}/phone" => ["PUT"],
        "/admin/v1/users/{id}" => ["PUT"],
        "/company/v1/account/{account_id}/image" => ["PUT"],
        "/company/v1/offer" => ["GET", "POST"],
        "/company/v1/offer/{id}" => ["GET", "PUT", "DELETE"],
        "/manager/v1/account/{id}" => ["POST", "PUT", "DELETE"],
        "/manager/v1/groups/{id}" => ["POST"],
        "/manager/v1/groupsbyuser/{id}" => ["GET"],
        "/manager/v1/groupsrole/{group_id}/{user_id}" => ["POST"],
        "/manager/v1/usersbygroup/{id}" => ["GET"],
        "/manager/v2/groups" => ["GET"],
        "/public/map/v1/list" => ["GET"],
        "/public/map/v1/search" => ["GET"],
        "/user/v1/account" => ["GET", "PUT"],
        "/user/v1/activegroup" => ["PUT"],
        "/user/v1/companies" => ["GET"],
        "/user/v1/new/account" => ["POST"],
        "/user/v1/pin" => ["POST"],
        "/user/v1/public_phone" => ["PUT"],
        "/user/v1/public_phone_list" => ["POST"],
        "/user/v1/question" => ["GET"],
        "/user/v1/save_kyc" => ["POST"],
        "/user/v1/upload_file" => ["POST"],
        "/user/v1/wallet/exchangers" => ["GET"],
        "/user/v2/wallet/transactions" => ["GET"],
        "/users/v1/usersbygroup/{id}" => ["GET"],
    ];


    /**
     * @param Client $client
     * @return Route[]
     */
    private function getAllRoutes(Client $client){
        /** @var RouterInterface $router */
        $router = $client->getKernel()->getContainer()->get('router');
        return $router->getRouteCollection()->all();
    }

    public function testAllLegacyCallsArePresent(){
        $this->markTestIncomplete("This test will be incompleted until https://github.com/qbitartifacts/rec-issues#31 resolves.Reviewed");
        $client = $this->request();
        $routes = $this->getAllRoutes($client);

        foreach(self::API_LEGACY_ROUTES as $legacyRoute => $legacyRouteMethods){
            $legacyRouteIsFound = false;
            foreach ($routes as $route){
                if($route->getPath() === $legacyRoute){
                    foreach($route->getMethods() as $method){
                        self::assertContains($method, $legacyRouteMethods, "Route {$legacyRoute} methods mismatch");
                    }
                    $legacyRouteIsFound = true;
                    break;
                }
            }
            self::assertTrue($legacyRouteIsFound, "Route $legacyRoute not found");
        }

    }
}
