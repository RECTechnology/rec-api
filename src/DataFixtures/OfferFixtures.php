<?php


namespace App\DataFixtures;

use App\Entity\Group;
use App\Entity\Offer;
use App\Entity\Tier;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class OfferFixtures extends Fixture implements DependentFixtureInterface {

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $orm
     * @throws \Exception
     */
    public function load(ObjectManager $orm)
    {
        $companies = $orm->getRepository(Group::class)->findBy(array(
            'type' => AccountFixtures::ACCOUNT_TYPE_ORGANIZATION,
        ),array(),6);

        /** @var Group $company */
        foreach ($companies as $company){
            $this->createOffer($orm, $company,false, '-1 year');
            $this->createOffer($orm, $company,true, '+1 year');
        }

    }

    /**
     * @param ObjectManager $orm
     */
    private function createOffer(ObjectManager $orm, Group $company, bool $active, $end){
        $offer = new Offer();
        $offer->setType(Offer::OFFER_TYPE_PERCENTAGE);
        $offer->setDiscount(10);
        $offer->setInitialPrice(10);
        $offer->setDescription('bla bla bla');
        $offer->setCompany($company);
        $offer->setStart(new \DateTime('-2 year'));
        if($company->getName() == AccountFixtures::TEST_ACCOUNT_CULT21_COMMERCE['name']){
            $offer->setEnd(new \DateTime('-1 year'));
            $offer->setActive(false);
        }else{
            $offer->setEnd(new \DateTime($end));
            $offer->setActive($active);
        }
        $offer->setImage('https://image.test/flower.jpg');

        $orm->persist($offer);

        $orm->flush();
    }

    public function getDependencies(){
        return [
            AccountFixtures::class,
        ];
    }
}