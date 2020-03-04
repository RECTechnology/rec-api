<?php


namespace Test\FinancialApiBundle\Bugs;


use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\DependencyInjection\App\Commons\UploadManager;
use App\FinancialApiBundle\Financial\Driver\LemonWayInterface;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class Issue201Test
 * @package Test\FinancialApiBundle\Bugs
 * @see https://github.com/QbitArtifacts/rec-api/issues/201
 */
class Issue201Test extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);

        $lw = $this->createMock(LemonWayInterface::class);
        $lw->method('callService')
            ->willReturn(json_decode(
                '{"__type":"WonderLib.RegisterIban","UPLOAD":{"ID":"54478","S":null,"MSG":null,"CHECKED":null},"E":null}'
            ));

        $fm = $this->createMock(UploadManager::class);
        $fm->method('saveFile')->willReturn('/file.jpg');

        $this->override('net.app.driver.lemonway.eur', $lw);
    }

    function testIssueIsSolved(){
        $account = $this->getOneAccount();

        $route = "/admin/v3/iban";
        $params = [
            "account_id" => $account->id,
            "number" => $this->faker->iban('ES'),
            "holder" => "adsdas",
            "bank_name" => "cajamar",
            "bank_address" => "adsdaadssda",
            "bic" => $this->faker->swiftBicNumber,
            "name" => "assd"
        ];
        $resp = $this->requestJson('POST', $route, $params);
        self::assertEquals(
            201,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }

    private function getOneAccount() {
        $resp = json_decode($this->requestJson('GET', '/admin/v3/accounts')->getContent());
        self::assertGreaterThan(0, $resp->data->total);
        return $resp->data->elements[0];
    }
}