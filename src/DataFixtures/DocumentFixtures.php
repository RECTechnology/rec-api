<?php

namespace App\DataFixtures;

use App\Entity\Document;
use App\Entity\DocumentKind;
use App\Entity\Group;
use App\Entity\User;
use App\Entity\UserGroup;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class DocumentFixtures extends Fixture implements DependentFixtureInterface {

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
                $document->setStatus(Document::STATUS_APP_SUBMITTED);
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
            UserFixtures::class,
            AccountFixtures::class,
            DocumentKindFixtures::class
        ];
    }
}