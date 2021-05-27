<?php

namespace Test\FinancialApiBundle\User;

use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Entity\Tier;
use App\FinancialApiBundle\Entity\User;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\CrudV3ReadTestInterface;

/**
 * Class UserCallsTest
 * @package Test\FinancialApiBundle\User
 */
class UserOfferTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
    }

    function testOffer()
    {
        $resp = $this->rest(
            'POST',
            '/company/v4/offers',
            [
                'start' => "2021-06-05",
                'end' => "2021-06-06",
                'discount' => 10,
                'description' => "descuento...",
                'image' => "https://deep-image.ai/static/media/slider-3-b.8cdacaf4.jpg",
                'type' => "classic",
                'initial_price' => 12,
                'offer_price' => 10
            ],
            [],
            200
        );
        $this->updateOffer();
        $this->indexOffer();
        $this->deleteOffer();
    }

    function indexOffer()
    {
        $resp = $this->rest(
            'GET',
            '/company/v4/offers',
            [],
            [],
            200
        );
    }

    function updateOffer()
    {
        $resp = $this->rest(
            'PUT',
            '/company/v4/offers/1',
            ['type' => "percentage"],
            [],
            200
        );
    }

    function deleteOffer()
    {
        $resp = $this->rest(
            'DELETE',
            '/company/v4/offers/1',
            [],
            [],
            200
        );
    }
}
