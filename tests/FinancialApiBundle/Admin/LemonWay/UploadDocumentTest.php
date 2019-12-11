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
        $fm->method('saveFile')->willReturn('/file.jpg');

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
                'content' => $this->faker->imageUrl(),
                'kind_id' => $kind->id,
                'account_id' => $account->id
            ]
        );
    }

    function testUploadLWDocument(){
        $kind = $this->createLemonDocumentKind();
        $user = $this->getSignedInUser();
        $account = $this->getUserAccount($user);
        $this->createDocument($account, $kind);
    }
}
