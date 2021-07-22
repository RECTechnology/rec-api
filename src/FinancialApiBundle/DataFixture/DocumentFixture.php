<?php

namespace  App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Entity\Document;
use App\FinancialApiBundle\Entity\DocumentKind;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Entity\UserGroup;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class DocumentFixture extends Fixture implements DependentFixtureInterface {

    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();

        $accounts = $manager->getRepository(Group::class)->findAll();
        $documentKinds = $manager->getRepository(DocumentKind::class)->findAll();

        /** @var Group $account */
        foreach ($accounts as $account){
            $userGroups = $account->getUsers();

            /** @var UserGroup $userGroup */
            foreach ($userGroups as $userGroup){
                $document = new Document();
                $document->setStatus('rec_submitted');
                $document->setName($faker->name);
                //Cant asign content because url is fake and there is no file there
                //$document->setContent($faker->url);
                $document->setValidUntil($faker->dateTime);
                $document->setAccount($account);
                $document->setKind($documentKinds[0]);
                $document->setUserId($userGroup->getUser()->getId());
                $document->setStatusText($faker->text);

                $manager->persist($document);
                $manager->flush();
            }

        }



    }

    public function getDependencies(){
        return[
            UserFixture::class,
            AccountFixture::class,
            DocumentKindFixture::class
        ];
    }
}