<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/13/15
 * Time: 6:50 PM
 */

namespace Telepay\FinancialApiBundle\Entity;
use Telepay\FinancialApiBundle\Financial\Currency;

use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 */
class BTCWallet implements OwnByUserInterface {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    public function getCurrency()
    {
        return Currency::$BTC;
    }

    /**
     * @ORM\Column(type="string")
     */
    private $cypher_data;

    /**
     * @ORM\OneToOne(targetEntity="Telepay\FinancialApiBundle\Entity\User")
     */
    private $user;

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * Returns the user unique id.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getCypherData()
    {
        return $this->cypher_data;
    }

    /**
     * @param mixed $cypher_data
     */
    public function setCypherData($cypher_data)
    {
        $this->cypher_data = $cypher_data;
    }

    public function getUser()
    {
        // TODO: Implement getUser() method.
    }
}