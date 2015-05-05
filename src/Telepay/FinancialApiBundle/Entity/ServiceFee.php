<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 5:26 PM
 */

namespace Telepay\FinancialApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="service_fees")
 */
class ServiceFee implements Fee{

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;


    /**
     * @ORM\Column(type="bigint")
     */
    private $fixed;

    /**
     * @ORM\Column(type="float")
     */
    private $variable;

    /**
     * @ORM\Column(type="string")
     */
    private $service_name;

    /**
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\Group")
     */
    private $group;

    private  $scale;

    private $currency;


    public static function createFromController($service_cname, Group $group){
        $fee = new ServiceFee();
        $fee->setFixed(0);
        $fee->setVariable(0);
        $fee->setServiceName($service_cname);
        $fee->setGroup($group);
        return $fee;
    }

    /**
     * @return mixed
     */
    public function getFixed()
    {
        return $this->fixed;
    }

    /**
     * @param mixed $fixed
     */
    public function setFixed($fixed)
    {
        $this->fixed = $fixed;
    }

    /**
     * @return mixed
     */
    public function getVariable()
    {
        return $this->variable;
    }

    /**
     * @param mixed $variable
     */
    public function setVariable($variable)
    {
        $this->variable = $variable;
    }



    /**
     * @return mixed
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param mixed $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getServiceName()
    {
        return $this->service_name;
    }

    /**
     * @param mixed $service_name
     */
    public function setServiceName($service_name)
    {
        $this->service_name = $service_name;
    }

    //TODO quitar esto de aqui porque se repite en varios sitios
    public function setScale($currency){
        $scale=0;
        switch($currency){
            case "EUR":
                $scale=2;
                break;
            case "MXN":
                $scale=2;
                break;
            case "USD":
                $scale=2;
                break;
            case "BTC":
                $scale=8;
                break;
            case "FAC":
                $scale=8;
                break;
            case "PLN":
                $scale=2;
                break;
            case "":
                $scale=0;
                break;
        }
        $this-> scale=$scale;
    }

    /**
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param mixed $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }
}