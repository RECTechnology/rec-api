<?php


namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Entity\Activity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class ActivityFixture extends Fixture implements DependentFixtureInterface {

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $orm
     * @throws \Exception
     */
    public function load(ObjectManager $orm)
    {
        $act1 = $this->createActivity($orm, 'Culture', null);
        $act3 = $this->createActivity($orm, 'Alimentacion', null);
        $act2 = $this->createActivity($orm, 'Musica', $act1);
        $act4 = $this->createActivity($orm, 'Musica_pop', $act2);
        $act5 = $this->createActivity($orm, 'Verdura', $act3);
        $act6 = $this->createActivity($orm, 'Cine', $act1);

    }

    /**
     * @param ObjectManager $orm
     */
    private function createActivity(ObjectManager $orm, $name, $parent){
        $activity = new Activity();
        $activity->setName($name);
        $activity->setNameEs($name);
        $activity->setNameCa($name);
        $activity->setParent($parent);
        $orm->persist($activity);
        $orm->flush();
        return $activity;
    }

    public function getDependencies(){
        return [
            UserFixture::class,
        ];
    }
}