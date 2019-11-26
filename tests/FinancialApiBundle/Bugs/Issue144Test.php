<?php


namespace Test\FinancialApiBundle\Bugs;


use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class Issue144Test
 * @package Test\FinancialApiBundle\Bugs
 * @see https://github.com/QbitArtifacts/rec-api/issues/144
 */
class Issue144Test extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }

    function testIssue144IsSolved(){
        $route = "/admin/v3/tiers";
        $tier = $this->rest('POST', $route, ['code' => "test"]);

        $route = "/admin/v3/document_kinds";
        $this->rest(
            'POST',
            $route,
            [
                'name' => 'Docname',
                'description' => 'desc',
                'tier_id' => $tier->id
            ]
        );

        $doc2 = $this->rest('POST', $route, ['name' => 'Docname', 'description' => 'desc']);

        $route = "/admin/v3/tiers/{$tier->id}/document_kinds";
        $this->rest('POST', $route, ['id' => $doc2->id]);

        $tierNew = $this->rest('GET',"/admin/v3/tiers/{$tier->id}");
        self::assertEquals(2, count($tierNew->document_kinds));

        $this->rest('DELETE',"/admin/v3/tiers/{$tier->id}/document_kinds/{$doc2->id}");

        $tierNew = $this->rest('GET',"/admin/v3/tiers/{$tier->id}");
        self::assertEquals(1, count($tierNew->document_kinds));
    }



}