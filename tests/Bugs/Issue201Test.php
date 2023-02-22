<?php


namespace App\Tests\Bugs;


use App\DataFixtures\UserFixtures;
use App\DependencyInjection\Commons\UploadManager;
use App\Financial\Driver\LemonWayInterface;
use App\Tests\BaseApiTest;

/**
 * Class Issue201Test
 * @package App\Tests\Bugs
 * @see https://github.com/QbitArtifacts/rec-api/issues/201
 */
class Issue201Test extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);

        $lw = $this->createMock(LemonWayInterface::class);
        $lw->method('callService')
            ->willReturn(json_decode(
                '{"__type":"WonderLib.RegisterIBANResult","IBAN_REGISTER":{"ID":"171","S":"4"},"E":null}'
            ));

        $fm = $this->createMock(UploadManager::class);
        $fm->method('saveFile')->willReturn('/file.jpg');

        $this->inject('net.app.driver.lemonway.eur', $lw);
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