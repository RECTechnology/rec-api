<?php


namespace App\DataFixtures;

use App\Entity\SmsTemplates;
use App\Entity\Tier;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class SmsTemplateFixtures extends Fixture implements DependentFixtureInterface {

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
        $this->createTemplate($orm, 'pin_max_failures', "¡Alerta de seguridad! Tu usuario ha excedido el máximo intento de PINs y ha sido bloqueado. Desbloquealo desde este enlace https://rec.barcelona/%SMS_CODE%");
        $this->createTemplate($orm, 'password_max_failures', "¡Alerta de seguridad! Tu usuario ha excedido el máximo intento de contraseñas para acceder y ha sido bloqueado. Desbloquealo desde este enlace https://rec.barcelona/%SMS_CODE%");
        $this->createTemplate($orm, 'sms_unlock_user', "¡Alerta de seguridad! Tu usuario ha excedido el máximo intento de contraseñas para acceder y ha sido bloqueado. Desbloquealo desde este enlace https://rec.barcelona/%SMS_CODE%");
        $this->createTemplate($orm, 'rezero_b2b_access_granted', "Ya tienes acceso a tu cuenta");

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
            UserFixtures::class,
        ];
    }
}