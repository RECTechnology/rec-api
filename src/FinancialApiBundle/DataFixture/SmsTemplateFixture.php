<?php


namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Entity\SmsTemplates;
use App\FinancialApiBundle\Entity\Tier;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class SmsTemplateFixture extends Fixture implements DependentFixtureInterface {

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $orm
     * @throws \Exception
     */
    public function load(ObjectManager $orm)
    {
        $this->createTemplate($orm, 'forget_password', "Este es tu forget_password_code: %SMS_CODE%");
        $this->createTemplate($orm, 'validate_phone', "Este es tu validate_phone_code: %SMS_CODE%");
        $this->createTemplate($orm, 'change_pin', "Este es tu change_pin_code : %SMS_CODE%");
        $this->createTemplate($orm, 'change_password', "Este es tu change_password_code: %SMS_CODE%");

    }

    /**
     * @param ObjectManager $orm
     */
    private function createTemplate(ObjectManager $orm, $type, $body){
        $template = new SmsTemplates();
        $template->setType($type);
        $template->setBody($body);
        $orm->persist($template);
        $orm->flush();
    }

    public function getDependencies(){
        return [
            UserFixture::class,
        ];
    }
}