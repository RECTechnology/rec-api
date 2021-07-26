<?php

namespace Test\FinancialApiBundle\Admin\LemonWay;

use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\DependencyInjection\App\Commons\UploadManager;
use App\FinancialApiBundle\Entity\LemonDocumentKind;
use App\FinancialApiBundle\Financial\Driver\LemonWayInterface;
use Test\FinancialApiBundle\Admin\AdminApiTest;

/**
 * Class UploadDocumentTest
 * @package Test\FinancialApiBundle\Admin\LemonWay
 */
class UploadDocumentTest extends AdminApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);

        $lw = $this->createMock(LemonWayInterface::class);
        $lw->method('callService')
            ->willReturn(json_decode(
                '{"__type":"WonderLib.UploadFileResult","UPLOAD":{"ID":"54478","S":null,"MSG":null,"CHECKED":null},"E":null}'
            ));

        $fm = $this->createMock(UploadManager::class);
        $fm->method('saveFile')->willReturn('/default_file.jpg');

        $this->override('net.app.driver.lemonway.eur', $lw);
        $this->override('file_manager', $fm);
    }


    function createLemonDocumentKind() {
        return $this->rest(
            'POST',
            '/admin/v3/lemon_document_kinds',
            [
                'name' => "DNI",
                'description' => 'user dni',
                'is_user_document' => 1,
                'show_in_app' => 0,
                'lemon_doctype' => LemonDocumentKind::DOCTYPE_LW_NON_EU_PASSPORT
            ]
        );
    }

    function getUserAccount($user) {
        return $user->group_data;
    }

    function createDocument($account, $kind){
        return $this->rest(
            'POST',
            "/admin/v3/lemon_documents",
            [
                'name' => 'uploaded dni',
                'content' => 'https://loremflickr.com/320/240?random=' . $this->faker->randomNumber(),
                'kind_id' => $kind->id,
                'account_id' => $account->id,
                'status_text' => "texto"
            ]
        );
    }

    function createNormalDocument($account, $kind, $user){
        return $this->rest(
            'POST',
            "/admin/v3/documents",
            [
                'name' => 'uploaded dni',
                'content' => 'https://loremflickr.com/320/240?random=' . $this->faker->randomNumber(),
                'kind_id' => $kind->id,
                'account_id' => $account->id,
                'status_text' => "texto",
                'user_id' => $user->id
            ]
        );
    }


    function syncLemon(){
        $this->runCommand('rec:sync:lemonway');
    }

    function testUploadLWDocumentAndCheckCron(){
        $kind = $this->createLemonDocumentKind();
        $user = $this->getSignedInUser();
        $account = $this->getUserAccount($user);
        $this->createDocument($account, $kind);
        $this->syncLemon();
    }

    function testGetDocuments()
    {
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $user = $this->getSignedInUser();
        $account = $this->getUserAccount($user);

        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $kind = $this->createLemonDocumentKind();
        $kindNormal = $this->createDocumentKind();
        $this->createDocument($account, $kind);
        $this->createNormalDocument($account, $kindNormal, $user);

        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $resp = $this->rest(
            'GET',
            '/admin/v3/documents',
            [],
            [],
            200
        );

        foreach ($resp as $doc){
            if($doc->kind->id == 5){
                self::assertObjectHasAttribute('user_id', $doc);
                self::assertObjectHasAttribute('user', $doc);
            }
        }
    }

    function testGetDocumentsv4()
    {
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $user = $this->getSignedInUser();
        $account = $this->getUserAccount($user);

        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $kind = $this->createLemonDocumentKind();
        $document = $this->createDocument($account, $kind);

        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $resp = $this->rest(
            'GET',
            '/user/v4/documents?company_id=1',
            ['company_id' => $account->id],
            [],
            200
        );
    }
    function testPostDocuments()
    {
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $user = $this->getSignedInUser();
        $account = $this->getUserAccount($user);

        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $kind = $this->createDocumentKind();
        //$this->createDocument($account, $kind);

        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $resp = $this->rest(
            'POST',
            '/user/v4/documents',
            [
                "content" => "/file_url.jpg",
                "name" => "dni",
                "account_id" => $account->id,
                "kind_id" => $kind->id
            ],
            [],
            200
        );
        $this->updateDocument($resp->document_id);
    }

    function updateDocument($document_id) {
        $resp = $this->rest(
            'PUT',
            '/user/v4/documents/'.$document_id,
            [
                "content" => "/new_file_url.jpg"
            ],
            [],
            404
        );
    }

    function createDocumentKind() {
        return $this->rest(
            'POST',
            '/admin/v3/document_kinds',
            [
                'name' => "DNI",
                'description' => 'user dni',
                'is_user_document' => 1
            ]
        );
    }

}
