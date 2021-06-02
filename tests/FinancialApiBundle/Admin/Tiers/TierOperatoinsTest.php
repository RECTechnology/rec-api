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

        $tier1 = $this->createTier();
        $this->createTier($tier1);
        $docType = $this->createDoctype();
        $this->addDoctypeToTier($tier1, $docType);
        $this->fetchTier($tier1);
        $this->delDoctypeFromTier($tier1, $docType);
        $this->addDoctypeToTier($tier1, $docType);
        $this->delTier($tier1);
    }

    private function createTier($parent = null) {
        $route = "/admin/v3/tiers";
        $params = ['code' => $this->faker->randomNumber(4)];
        if($parent) $params['parent_id'] = $parent->id;
        return $this->rest('POST', $route, $params);
    }

    private function createDoctype() {
        $route = "/admin/v3/document_kinds";
        return $this->rest('POST', $route, [
            'name' => 'Docname',
            'description' => 'desc',
            'is_user_document' => 1,
            'show_in_app' => 0
        ]);
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

    private function fetchTier($tier)
    {
        $route = "/admin/v3/tiers/{$tier->id}";
        $this->rest('GET', $route);
    }


}