<?php


namespace Test\FinancialApiBundle\Bugs;


use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class Issue307Test
 * @package Test\FinancialApiBundle\Bugs
 * @see https://github.com/QbitArtifacts/rec-api/issues/307
 */
class Issue307Test extends BaseApiTest {

    function setAdminPhoneAndPublic($phone){
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);

        $user = $this->getSignedInUser();
        $route = "/admin/v3/users/{$user->id}";
        $this->rest("PUT", $route, ['phone' => $phone]);
        $this->signOut();
    }


    function testIssue(){
        $phone = "605450083";
        $this->setAdminPhoneAndPublic($phone);
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);

        $route = "/user/v1/public_phone_list";
        $params = ["phone_list" => [$phone]];

        $resp = $this->requestJson('POST', $route, $params);
        $content = json_decode($resp->getContent());
        self::assertObjectHasAttribute($phone, $content->data);
    }

}