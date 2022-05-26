<?php

namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Entity\Badge;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\Qualification;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class QualificationFixture extends Fixture implements DependentFixtureInterface
{

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $orm
     * @throws \Exception
     */
    public function load(ObjectManager $orm)
    {
        //get one shop
        /** @var Group $shop */
        $shop = $orm->getRepository(Group::class)->findOneBy(array('name' => AccountFixture::TEST_SHOP_ACCOUNT['name']));
        //get one particular account
        //Cogemos el nombre del user en vez del account porque el nombre del account es el nombre del user en el fixtures de AccountFixture
        /** @var Group $customer_account */
        $customer_account = $orm->getRepository(Group::class)->findOneBy(array('name' => UserFixture::TEST_THIRD_USER_CREDENTIALS['name']));

        //get all badges
        $badges = $orm->getRepository(Badge::class)->findAll();

        //make a pending review for every badget
        /** @var Badge $badge */
        foreach ($badges as $badge){
            $this->createQualification($orm, null, Qualification::STATUS_PENDING, $badge, $shop, $customer_account);
        }

        //COgemos otro user para generar mas reviews
        /** @var Group $customer_account_2 */
        $customer_account_2 = $orm->getRepository(Group::class)->findOneBy(array('name' => 'private_account_1'));

        /** @var Badge $badge */
        foreach ($badges as $badge){
            $this->createQualification($orm, null, Qualification::STATUS_PENDING, $badge, $shop, $customer_account_2);
        }

        //TODO make some reviewed reviews

    }

    /**
     * @param ObjectManager $orm
     */
    private function createQualification(ObjectManager $orm, $value, $status, Badge $badge, Group $account, Group $reviewer){
        $review = new Qualification();
        $review->setValue($value);
        $review->setStatus($status);
        $review->setBadge($badge);
        $review->setAccount($account);
        $review->setReviewer($reviewer);

        $orm->persist($review);
        $orm->flush();
    }

    public function getDependencies()
    {
        return [
            BadgesFixture::class,
            AccountFixture::class,
            UserFixture::class
        ];
    }
}