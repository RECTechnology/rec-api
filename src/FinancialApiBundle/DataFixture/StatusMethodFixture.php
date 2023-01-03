<?php

namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Entity\StatusMethod;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class StatusMethodFixture
 * @package App\FinancialApiBundle\DataFixture
 */
class StatusMethodFixture extends Fixture {

    private $currency;

    public function __construct(ContainerInterface $container){
        $this->currency = $container->getParameter('crypto_currency');
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        $methods_to_create = [
            [strtolower($this->currency), 'in', $this->currency],
            [strtolower($this->currency), 'out', $this->currency],
            ['lemonway', 'in', 'EUR'],
        ];

        foreach ($methods_to_create as $data){
            $method = $this->createStatusMethod($data[0], $data[1], 'available', '0', $data[2]);
            $manager->persist($method);
        }
        $manager->flush();
    }

    private function createStatusMethod($name, $type, $status, $balance, $currency){
        $method = new StatusMethod();
        $method->setMethod($name);
        $method->setType($type);
        $method->setStatus($status);
        $method->setBalance($balance);
        $method->setCurrency($currency);
        return $method;
    }
}