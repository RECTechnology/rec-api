<?php


namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\Offer;
use App\FinancialApiBundle\Entity\Tier;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class OfferFixture extends Fixture implements DependentFixtureInterface {

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $orm
     * @throws \Exception
     */
    public function load(ObjectManager $orm)
    {
        $companies = $orm->getRepository(Group::class)->findBy(array(
            'type' => AccountFixture::ACCOUNT_TYPE_ORGANIZATION,
        ),array(),6);

        /** @var Group $company */
        foreach ($companies as $company){
            $this->createOffer($orm, $company);
        }

    }

    /**
     * @param ObjectManager $orm
     */
    private function createOffer(ObjectManager $orm, Group $company){
        $offer = new Offer();
        $offer->setType(Offer::OFFER_TYPE_PERCENTAGE);
        $offer->setDiscount(10);
        $offer->setInitialPrice(10);
        $offer->setDescription('bla bla bla');
        $offer->setCompany($company);
        $offer->setStart(new \DateTime('-2 days'));
        if($company->getName() == AccountFixture::TEST_ACCOUNT_CULT21_COMMERCE['name']){
            $offer->setEnd(new \DateTime('-1 year'));
            $offer->setActive(false);
        }else{
            $offer->setEnd(new \DateTime('+1 year'));
            $offer->setActive(true);
        }
        $offer->setImage('https://image.test/flower.jpg');

        $orm->persist($offer);

        $orm->flush();
    }

    public function getDependencies(){
        return [
            AccountFixture::class,
        ];
    }
}