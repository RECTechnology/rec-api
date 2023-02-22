<?php

namespace App\DataFixtures;

use App\Entity\Award;
use App\Entity\Badge;
use App\Entity\EmailExport;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class EmailExportFixtures extends Fixture
{

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $orm
     * @throws \Exception
     */
    public function load(ObjectManager $orm)
    {
        // TODO: Implement load() method.
        $this->createEmailExport($orm);
    }

    /**
     * @param ObjectManager $orm
     */
    private function createEmailExport(ObjectManager $orm){

        $export = new EmailExport();
        $export->setStatus(EmailExport::STATUS_CREATED);
        $export->setEntityName("User");
        $export->setEmail("inbox@rec.qbitartifacts.com");
        $field_map_string = '{"id": "$.id","username":"$.username","email":"$.email","locked":"$.locked","expired":"$.expired"'.
            ',"name":"$.name","created":"$.created","dni":"$.dni","prefix":"$.prefix","phone":"$.phone"'.
            ',"public_phone":"$.public_phone"}';
        $query = [
            "field_map" => $field_map_string,
            "limit" => 1000000
        ];
        $export->setQuery($query);

        $fieldMap = [
            "id" => "$.id",
            "username" => "$.username",
            "email" => "$.email",
            "locked" => "$.locked",
            "expired" => "$.expired",
            "name" => "$.name",
            "dni" => "$.dni",
            "prefix" => "$.prefix",
            "phone" => "$.phone",
            "public_phone" => "$.public_phone",
        ];
        $export->setFieldMap($fieldMap);

        $orm->persist($export);
        $orm->flush();
    }

}