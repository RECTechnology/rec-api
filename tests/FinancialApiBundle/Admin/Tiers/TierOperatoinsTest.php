<?php


namespace Test\FinancialApiBundle\Admin\Tiers;


use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class TierOperatoinsTest
 * @package Test\FinancialApiBundle\Admin\Tiers
 */
class TierOperatoinsTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }

    function testAllOperations(){

        $tier = $this->createTier();
        $docType = $this->createDoctype();
        $this->addDoctypeToTier($tier, $docType);
        $this->delDoctypeFromTier($tier, $docType);
        $this->addDoctypeToTier($tier, $docType);
        $this->delTier($tier);
    }

    private function createTier() {
        $route = "/admin/v3/tiers";
        return $this->rest('POST', $route, ['code' => "test"]);
    }

    private function createDoctype() {
        $route = "/admin/v3/document_kinds";
        return $this->rest('POST', $route, ['name' => 'Docname', 'description' => 'desc']);
    }

    private function addDoctypeToTier($tier, $docType)
    {
        $route = "/admin/v3/tiers/{$tier->id}/document_kinds";
        $this->rest('POST', $route, ['id' => $docType->id]);
    }

    private function delDoctypeFromTier($tier, $docType)
    {
        $route = "/admin/v3/tiers/{$tier->id}/document_kinds/{$docType->id}";
        $this->rest('DELETE', $route);
    }

    private function delTier($tier)
    {
        $route = "/admin/v3/tiers/{$tier->id}";
        $this->rest('DELETE', $route);
    }


}