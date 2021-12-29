<?php


namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\PaymentOrder;
use App\FinancialApiBundle\Entity\Pos;
use App\FinancialApiBundle\Entity\Tier;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

class PaymentOrderFixture extends Fixture implements DependentFixtureInterface {

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $orm
     * @throws \Exception
     */
    public function load(ObjectManager $orm)
    {
        $faker = Factory::create();
        $this->createPayments($faker, $orm);
    }


    /**
     * @param ObjectManager $orm
     */
    private function createPayments(Generator $faker,ObjectManager $orm){

        $tpvs = $orm->getRepository(Pos::class)->findAll();
        /** @var POS $tpv */
        foreach ($tpvs as $tpv){
            for ($i=1; $i<3; $i++){
                $payment = new PaymentOrder();
                $payment->setAmount(100e8);
                $payment->setAccessKey($tpv->getAccessKey());
                $payment->setConcept($i."_".$faker->sentence);
                $payment->setReference($i."_".$faker->numberBetween(99999,999999));
                $payment->setOkUrl('https://returnpage.com/ok');
                $payment->setKoUrl('https://returnpage.com/ko');
                $payment->setSignatureVersion('hmac_sha256_v1');
                $payment->setPaymentType('desktop');
                $payment->setIpAddress($faker->ipv4);

                $payment->setSignature($this->signData($tpv, $payment));
                $payment->setStatus(PaymentOrder::STATUS_IN_PROGRESS);
                $orm->persist($payment);
                $orm->flush();

                if($i%2 == 0) {
                    $payment->setStatus(PaymentOrder::STATUS_DONE);
                    $orm->flush();
                }
            }

        }


    }

    private function signData(Pos $pos, PaymentOrder $payment){
        $dataToSign = [
            "acces_key" => $payment->getAccessKey(),
            "amount" => $payment->getAmount(),
            "concept" => $payment->getConcept(),
            "ko_url" => $payment->getKoUrl(),
            "ok_url" => $payment->getOkUrl(),
            "payment_type" => $payment->getPaymentType(),
            "reference" => $payment->getReference(),
            "signature_version" => $payment->getSignatureVersion()
        ];
        $signaturePack = json_encode($dataToSign, JSON_UNESCAPED_SLASHES);
        return hash_hmac('sha256', $signaturePack, base64_decode($pos->getAccessSecret()));

    }

    public function getDependencies(){
        return [
            AccountFixture::class,
        ];
    }
}