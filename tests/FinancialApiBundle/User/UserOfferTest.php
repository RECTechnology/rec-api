<?php

namespace Test\FinancialApiBundle\User;

use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Entity\Offer;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class UserOfferTest
 * @package Test\FinancialApiBundle\User
 */
class UserOfferTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
    }

    function testCreateClassicOffer()
    {
        $resp = $this->rest(
            'POST',
            '/company/v4/offers',
            [
                'end' => "2021-06-06",
                'description' => "descuento...",
                'image' => "https://rec.barcelona/wp-content/uploads/2018/12/RecNadal-2.jpg",
                'type' => "classic",
                'initial_price' => 10,
                'offer_price' => 7
            ],
            [],
            200
        );
        self::assertObjectHasAttribute('discount', $resp);
        self::assertEquals(30, $resp->discount);

        $this->indexOffer();
        $this->deleteOffer($resp->id);
    }

    function testCreateClassicOfferWithoutImageShouldWork()
    {
        $resp = $this->rest(
            'POST',
            '/company/v4/offers',
            [
                'end' => "2021-06-06",
                'description' => "descuento...",
                'type' => "classic",
                'initial_price' => 10,
                'offer_price' => 7
            ],
            [],
            200
        );
    }

    function testCreateClassicOfferWithBadParamsShouldFail()
    {
        $this->rest(
            'POST',
            '/company/v4/offers',
            [
                'end' => "2021-06-06",
                'description' => "descuento...",
                'image' => "https://rec.barcelona/wp-content/uploads/2018/12/RecNadal-2.jpg",
                'type' => "classic",
                'initial_price' => 10
            ],
            [],
            404
        );

        $this->rest(
            'POST',
            '/company/v4/offers',
            [
                'end' => "2021-06-06",
                'description' => "descuento...",
                'image' => "https://rec.barcelona/wp-content/uploads/2018/12/RecNadal-2.jpg",
                'type' => "classic",
                'offer_price' => 10
            ],
            [],
            404
        );

        $this->rest(
            'POST',
            '/company/v4/offers',
            [
                'end' => "2021-06-06",
                'description' => "descuento...",
                'image' => "https://rec.barcelona/wp-content/uploads/2018/12/RecNadal-2.jpg",
                'type' => "classic",
            ],
            [],
            404
        );

        $this->rest(
            'POST',
            '/company/v4/offers',
            [
                'end' => "2021-06-06",
                'description' => "descuento...",
                'image' => "https://rec.barcelona/wp-content/uploads/2018/12/RecNadal-2.jpg",
                'type' => "classic",
                "initial_price" => null,
                "offer_price" => null
            ],
            [],
            404
        );

    }

    function testCreatePercentageOffer()
    {
        $resp = $this->rest(
            'POST',
            '/company/v4/offers',
            [
                'end' => "2021-06-06",
                'description' => "descuento...",
                'image' => "https://rec.barcelona/wp-content/uploads/2018/12/RecNadal-2.jpg",
                'type' => "percentage",
                'discount' => 30
            ],
            [],
            200
        );

        self::assertObjectHasAttribute('discount', $resp);
        self::assertEquals(30, $resp->discount);
        self::assertObjectNotHasAttribute('initial_price', $resp);
        self::assertObjectNotHasAttribute('offer_price', $resp);

    }

    function testCreateFreeOffer()
    {
        $resp = $this->rest(
            'POST',
            '/company/v4/offers',
            [
                'end' => "2021-06-06",
                'description' => "descuento...",
                'image' => "https://rec.barcelona/wp-content/uploads/2018/12/RecNadal-2.jpg",
                'type' => "free"
            ],
            [],
            200
        );

        self::assertObjectNotHasAttribute('discount', $resp);
        self::assertObjectNotHasAttribute('initial_price', $resp);
        self::assertObjectNotHasAttribute('offer_price', $resp);

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

    function testUpdateOfferFromFreeToPercentage(){
        $resp = $this->rest(
            'POST',
            '/company/v4/offers',
            [
                'end' => "2021-06-06",
                'description' => "descuento...",
                'image' => "https://rec.barcelona/wp-content/uploads/2018/12/RecNadal-2.jpg",
                'type' => "free"
            ],
            [],
            200
        );

        $resp = $this->rest(
            'PUT',
            '/company/v4/offers/4',
            ['type' => "percentage", "discount" => 10],
            [],
            200
        );

    }

    function testUpdateOfferFromFreeToClassic(){
        $this->rest(
            'POST',
            '/company/v4/offers',
            [
                'end' => "2021-06-06",
                'description' => "descuento...",
                'image' => "https://rec.barcelona/wp-content/uploads/2018/12/RecNadal-2.jpg",
                'type' => "free"
            ],
            [],
            200
        );

        $resp = $this->rest(
            'PUT',
            '/company/v4/offers/4',
            ['type' => "classic", "initial_price" => 0, "offer_price" => 7],
            [],
            400
        );

        self::assertEquals('Param initial price cannot be null or 0', $resp->message);

        //offer price can be null or 0 because we can make a 100% discount
        $resp = $this->rest(
            'PUT',
            '/company/v4/offers/4',
            ['type' => "classic", "initial_price" => 10, "offer_price" => null],
            [],
            400
        );

        self::assertEquals('Param offer price cannot be null or 0', $resp->message);

        $this->rest(
            'PUT',
            '/company/v4/offers/4',
            ['type' => "classic", "initial_price" => 10, "offer_price" => 7],
            [],
            200
        );
    }

    function testUpdateOfferFromPercentageToFree()
    {
        $this->rest(
            'POST',
            '/company/v4/offers',
            [
                'end' => "2021-06-06",
                'description' => "descuento...",
                'image' => "https://rec.barcelona/wp-content/uploads/2018/12/RecNadal-2.jpg",
                'type' => "percentage",
                'discount' => 30
            ],
            [],
            200
        );

        $this->rest(
            'PUT',
            '/company/v4/offers/4',
            ['type' => "free"],
            [],
            200
        );


    }

    function testUpdateOfferFromPercentageToClassic()
    {
        $this->rest(
            'POST',
            '/company/v4/offers',
            [
                'end' => "2021-06-06",
                'description' => "descuento...",
                'image' => "https://rec.barcelona/wp-content/uploads/2018/12/RecNadal-2.jpg",
                'type' => "percentage",
                'discount' => 30
            ],
            [],
            200
        );

        $this->rest(
            'PUT',
            '/company/v4/offers/4',
            ['type' => "classic", "initial_price" => 10, "offer_price" => 7],
            [],
            200
        );

    }

    function testUpdateOfferFromClassicToFree()
    {
        $this->rest(
            'POST',
            '/company/v4/offers',
            [
                'end' => "2021-06-06",
                'description' => "descuento...",
                'image' => "https://rec.barcelona/wp-content/uploads/2018/12/RecNadal-2.jpg",
                'type' => "classic",
                'initial_price' => 10,
                'offer_price' => 7
            ],
            [],
            200
        );

        $this->rest(
            'PUT',
            '/company/v4/offers/4',
            ['type' => "free"],
            [],
            200
        );

    }

    function testUpdateOfferFromClassicToPercentage()
    {
        $this->rest(
            'POST',
            '/company/v4/offers',
            [
                'end' => "2021-06-06",
                'description' => "descuento...",
                'image' => "https://rec.barcelona/wp-content/uploads/2018/12/RecNadal-2.jpg",
                'type' => "classic",
                'initial_price' => 10,
                'offer_price' => 7
            ],
            [],
            200
        );

        $this->rest(
            'PUT',
            '/company/v4/offers/4',
            ['type' => "percentage", "discount" => 20],
            [],
            200
        );

    }

    function deleteOffer($id)
    {
        $this->rest(
            'DELETE',
            '/company/v4/offers/'.$id,
            [],
            [],
            200
        );
    }

    function testOfferBadTypeShouldFail()
    {
        $this->rest(
            'POST',
            '/company/v4/offers',
            [
                'end' => "2021-06-06",
                'discount' => 10,
                'description' => "descuento...",
                'image' => "https://rec.barcelona/wp-content/uploads/2018/12/RecNadal-2.jpg",
                'type' => "fake",
                'initial_price' => 12,
                'offer_price' => 10
            ],
            [],
            400
        );
    }

    function testUpdateClassicOffer()
    {
        $this->rest(
            'POST',
            '/company/v4/offers',
            [
                'end' => "2021-06-06",
                'description' => "descuento...",
                'image' => "https://rec.barcelona/wp-content/uploads/2018/12/RecNadal-2.jpg",
                'type' => "classic",
                'initial_price' => 10,
                'offer_price' => 7
            ],
            [],
            200
        );

        $this->rest(
            'PUT',
            '/company/v4/offers/4',
            [
                'type' => "classic",
                'initial_price' => 12,
                'offer_price' => 10
            ],
            [],
            200
        );

    }
}
