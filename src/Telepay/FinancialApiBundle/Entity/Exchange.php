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
 * @ORM\Table(name="exchange")
 */
class Exchange {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;


    /**
     * @ORM\Column(type="integer")
     */
    private $eur;

    /**
     * @ORM\Column(type="float")
     */
    private $usd;

    /**
     * @ORM\Column(type="float")
     */
    private $mxn;

    /**
     * @ORM\Column(type="float")
     */
    private $btc;

    /**
     * @ORM\Column(type="float")
     */
    private $fac;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getEur()
    {
        return $this->eur;
    }

    /**
     * @param mixed $eur
     */
    public function setEur($eur)
    {
        $this->eur = $eur;
    }

    /**
     * @return mixed
     */
    public function getUsd()
    {
        return $this->usd;
    }

    /**
     * @param mixed $usd
     */
    public function setUsd($usd)
    {
        $this->usd = $usd;
    }

    /**
     * @return mixed
     */
    public function getMxn()
    {
        return $this->mxn;
    }

    /**
     * @param mixed $mxn
     */
    public function setMxn($mxn)
    {
        $this->mxn = $mxn;
    }

    /**
     * @return mixed
     */
    public function getBtc()
    {
        return $this->btc;
    }

    /**
     * @param mixed $btc
     */
    public function setBtc($btc)
    {
        $this->btc = $btc;
    }

    /**
     * @return mixed
     */
    public function getFac()
    {
        return $this->fac;
    }

    /**
     * @param mixed $fac
     */
    public function setFac($fac)
    {
        $this->fac = $fac;
    }

    /**
     * @param $currency
     * @return mixed
     */
    public function getExchange($currency)
    {
        $exchange=null;
        switch($currency){
            case 'EUR':
                $exchange=$this->getEur();
                break;
            case 'USD':
                $exchange=$this->getUsd();
                break;
            case 'MXN':
                $exchange=$this->getMxn();
                break;
            case 'BTC':
                $exchange=$this->getBtc();
                break;
            case 'FAC':
                $exchange=$this->getFac();
                break;
        }
        return $exchange;
    }


}