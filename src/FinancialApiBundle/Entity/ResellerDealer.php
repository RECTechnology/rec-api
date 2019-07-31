<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 5:26 PM
 */

namespace App\FinancialApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Exclude;

/**
 * @ORM\Entity
 * @ORM\Table(name="reseller_dealers")
 * @ExclusionPolicy("none")
 */
class ResellerDealer{

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Group")
     */
    private $company_origin;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Group")
     */
    private $company_reseller;

    /**
     * @ORM\Column(type="string")
     */
    private $fee;

    /**
     * @ORM\Column(type="string")
     */
    private $method;

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
    public function getCompanyOrigin()
    {
        return $this->company_origin;
    }

    /**
     * @param mixed $company_origin
     */
    public function setCompanyOrigin($company_origin)
    {
        $this->company_origin = $company_origin;
    }

    /**
     * @return mixed
     */
    public function getCompanyReseller()
    {
        return $this->company_reseller;
    }

    /**
     * @param mixed $company_reseller
     */
    public function setCompanyReseller($company_reseller)
    {
        $this->company_reseller = $company_reseller;
    }

    /**
     * @return mixed
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * @param mixed $fee
     */
    public function setFee($fee)
    {
        $this->fee = $fee;
    }

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param mixed $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

}