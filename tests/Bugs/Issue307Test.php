<?php


namespace App\Tests\Bugs;


use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

/**
 * Class Issue307Test
 * @package App\Tests\Bugs
 * @see https://github.com/QbitArtifacts/rec-api/issues/307
 */
class Issue307Test extends BaseApiTest {

    function setAdminPhoneAndPublic($phone){
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);

        $user = $this->getSignedInUser();
        $route = "/admin/v3/users/{$user->id}";
        $this->rest("PUT", $route, ['phone' => $phone]);
        $this->signOut();
    }


    function testIssue(){
        $phone = "605450083";
        $this->setAdminPhoneAndPublic($phone);
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);

        $route = "/user/v1/public_phone_list";
        $params = ["phone_list" => [$phone]];

        $resp = $this->requestJson('POST', $route, $params);
        $content = json_decode($resp->getContent());
        $phone_found = false;
        foreach ($content->data as $account_data){
            if ($account_data->phone == $phone){
                $phone_found = true;
            }
        }
        self::assertTrue($phone_found);
    }

}