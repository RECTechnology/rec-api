<?php

namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Entity\Award;
use App\FinancialApiBundle\Entity\AwardScoreRule;
use App\FinancialApiBundle\Entity\Badge;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class AwardRulesFixture extends Fixture implements DependentFixtureInterface
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
        $this->createImpulsAwardRules($orm);
        $this->createParaulaAwardRules($orm);
        $this->createSaviesaAwardRules($orm);

    }

    /**
     * @param ObjectManager $orm
     */
    private function createSaviesaAwardRules($orm){
        $saviesa = $orm->getRepository(Award::class)->findOneBy(array('name' => 'La saviesa' ));

        $rule1 = new AwardScoreRule();
        $rule1->setAward($saviesa);
        $rule1->setScore(5);
        $rule1->setAction('create_topic');
        //1 means category compartir
        $rule1->setCategory(1);
        $orm->persist($rule1);

        $rule2 = new AwardScoreRule();
        $rule2->setAward($saviesa);
        $rule2->setScore(2);
        $rule2->setAction('comment');
        //1 means category compartir
        $rule2->setCategory(2);
        $orm->persist($rule2);

        $orm->flush();
    }

    /**
     * @param ObjectManager $orm
     */
    private function createParaulaAwardRules($orm){
        $paraula = $orm->getRepository(Award::class)->findOneBy(array('name' => 'La paraula' ));

        $rule1 = new AwardScoreRule();
        $rule1->setAward($paraula);
        $rule1->setScore(5);
        $rule1->setAction('create_topic');
        //3 means category preguntar
        $rule1->setCategory(3);
        $orm->persist($rule1);

        $rule2 = new AwardScoreRule();
        $rule2->setAward($paraula);
        $rule2->setScore(2);
        $rule2->setAction('comment');
        //3 means category preguntar
        $rule2->setCategory(3);
        $orm->persist($rule2);

        $rule3 = new AwardScoreRule();
        $rule3->setAward($paraula);
        $rule3->setScore(2);
        $rule3->setAction('comment');
        //4 means category news
        $rule3->setCategory(4);
        $orm->persist($rule3);

        $orm->flush();
    }

    /**
     * @param ObjectManager $orm
     */
    private function createImpulsAwardRules($orm){
        $impuls = $orm->getRepository(Award::class)->findOneBy(array('name' => 'La impuls' ));

        $rule1 = new AwardScoreRule();
        $rule1->setAward($impuls);
        $rule1->setScore(10);
        $rule1->setAction('create_topic');
        //5 means category proposar
        $rule1->setCategory(5);
        $orm->persist($rule1);

        $rule2 = new AwardScoreRule();
        $rule2->setAward($impuls);
        $rule2->setScore(2);
        $rule2->setAction('comment');
        //5 means category proposar
        $rule2->setCategory(5);
        $orm->persist($rule2);

        $rule3 = new AwardScoreRule();
        $rule3->setAward($impuls);
        $rule3->setScore(1);
        $rule3->setAction('like');
        $rule3->setScope('post');
        $orm->persist($rule3);

        $rule4 = new AwardScoreRule();
        $rule4->setAward($impuls);
        $rule4->setScore(1);
        $rule4->setAction('like');
        $rule4->setScope('topic');
        $orm->persist($rule4);

        $rule5 = new AwardScoreRule();
        $rule5->setAward($impuls);
        $rule5->setScore(1);
        $rule5->setAction('like');
        $rule5->setScope('topic');
        //5 means category news
        $rule5->setCategory(5);
        $orm->persist($rule5);

        $rule6 = new AwardScoreRule();
        $rule6->setAward($impuls);
        $rule6->setScore(2);
        $rule6->setAction('receive_like');
        $rule6->setScope('post');
        $orm->persist($rule6);

        $rule7 = new AwardScoreRule();
        $rule7->setAward($impuls);
        $rule7->setScore(3);
        $rule7->setAction('receive_like');
        $rule7->setScope('topic');
        $orm->persist($rule7);

        $orm->flush();
    }

    public function getDependencies(){
        return [
            AwardsFixture::class,
        ];
    }

}