<?php

namespace App\Tests\Admin\Pos;

use App\Tests\Admin\AdminApiTest;
use App\Tests\CrudV3WriteTestInterface;
use function PHPUnit\Framework\assertIsObject;

/**
 * Class PosTest
 * @package App\Tests\Admin\Pos
 */
class PosTest extends AdminApiTest implements CrudV3WriteTestInterface {

    const ROUTE = "/admin/v3/pos";

    function testCreate()
    {
        $account = $this->getOneAccount();
        $pos = $this->createPos($account);
        $route = '/admin/v3/accounts/'.$account->id;
        $resp = $this->requestJson('GET', $route);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(), true);
        self::assertArrayHasKey('data', $content);

        $data = $content['data'];
        self::assertArrayHasKey('pos', $data);

        self::assertIsObject($pos);
        self::assertTrue(property_exists($pos, 'active'));
        self::assertTrue(property_exists($pos, 'account'));
        self::assertTrue(property_exists($pos, 'access_key'));
        self::assertTrue(property_exists($pos, 'access_secret'));
    }

    function testUpdate()
    {
        $url_notification = "https://admin.rec.qbitartifacts.com";
        $pos = $this->getOnePos();
        $this->updatePos($pos, ['active' => true]);
        $updatedPos = $this->updatePos($pos, ['notification_url' => $url_notification]);

        self::assertIsObject($updatedPos);
        self::assertTrue(property_exists($updatedPos, 'notification_url'));
        self::assertEquals($url_notification, $updatedPos->notification_url);
    }

    function testDelete()
    {
        $pos = $this->getOnePos();
        $this->deletePos($pos);
        $resp = $this->createPaymentOrder($pos, 100000000, '', '');

        $content = json_decode($resp->getContent(),true);
        self::assertEquals($content['status'], 'error');
    }

    private function getOneAccount()
    {
        $route = "/admin/v3/accounts";
        return $this->rest('GET', $route)[0];
    }

    private function getOnePos()
    {
        $route = "/admin/v3/pos";
        return $this->rest('GET', $route)[0];
    }

    private function createPos($account)
    {
        return $this->rest('POST', self::ROUTE, [
            'account_id' => $account->id
        ]);
    }

    private function updatePos($pos, array $params)
    {
        return $this->rest('PUT', self::ROUTE . "/{$pos->id}", $params);
    }

    private function deletePos($pos)
    {
        return $this->rest('DELETE', self::ROUTE . "/{$pos->id}");
    }

    private function createPaymentOrder($pos, int $amount, string $okUrl, string $koUrl)
    {
        $route = "/public/v3/payment_orders";
        $reference = "1234123412341234";
        $concept = "Mercat do castelo 1234123412341234";
        $signatureParams = [
            'access_key' => $pos->access_key,
            'reference' => $reference,
            'ok_url' => $okUrl,
            'ko_url' => $koUrl,
            'signature_version' => 'hmac_sha256_v1',
            'amount' => $amount,
            'concept' => $concept,
            'payment_type' => 'desktop',
        ];
        ksort($signatureParams);
        $signatureData = json_encode($signatureParams, JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', $signatureData, base64_decode($pos->access_secret));
        return $this->requestJson('POST', $route, [
            'access_key' => $pos->access_key,
            'amount' => $amount,
            'ok_url' => $okUrl,
            'ko_url' => $koUrl,
            'concept' => $concept,
            'reference' => $reference,
            'signature_version' => 'hmac_sha256_v1',
            'signature' => $signature,
            'payment_type' => 'desktop',
        ]);
    }
}
