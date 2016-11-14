<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 4:47 PM
 */

namespace Telepay\FinancialApiBundle\Entity;


use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Exclude;


/**
 * @ORM\Entity
 * @ORM\Table(name="kyc_limits")
 */
class KYCLimits {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $daily_deposit_fiat;

    /**
     * @ORM\Column(type="integer")
     */
    private $monthly_deposit_fiat;

    /**
     * @ORM\Column(type="integer")
     */
    private $daily_deposit_crypto;

    /**
     * @ORM\Column(type="integer")
     */
    private $monthly_deposit_crypto;

    /**
     * @ORM\Column(type="integer")
     */
    private $daily_withdraw_fiat;

    /**
     * @ORM\Column(type="integer")
     */
    private $monthly_withdraw_fiat;

    /**
     * @ORM\Column(type="integer")
     */
    private $daily_withdraw_crypto;

    /**
     * @ORM\Column(type="integer")
     */
    private $monthly_withdraw_crypto;

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
    public function getDailyDepositFiat()
    {
        return $this->daily_deposit_fiat;
    }

    /**
     * @param mixed $daily_deposit_fiat
     */
    public function setDailyDepositFiat($daily_deposit_fiat)
    {
        $this->daily_deposit_fiat = $daily_deposit_fiat;
    }

    /**
     * @return mixed
     */
    public function getMonthlyDepositFiat()
    {
        return $this->monthly_deposit_fiat;
    }

    /**
     * @param mixed $monthly_deposit_fiat
     */
    public function setMonthlyDepositFiat($monthly_deposit_fiat)
    {
        $this->monthly_deposit_fiat = $monthly_deposit_fiat;
    }

    /**
     * @return mixed
     */
    public function getDailyDepositCrypto()
    {
        return $this->daily_deposit_crypto;
    }

    /**
     * @param mixed $daily_deposit_crypto
     */
    public function setDailyDepositCrypto($daily_deposit_crypto)
    {
        $this->daily_deposit_crypto = $daily_deposit_crypto;
    }

    /**
     * @return mixed
     */
    public function getMonthlyDepositCrypto()
    {
        return $this->monthly_deposit_crypto;
    }

    /**
     * @param mixed $monthly_deposit_crypto
     */
    public function setMonthlyDepositCrypto($monthly_deposit_crypto)
    {
        $this->monthly_deposit_crypto = $monthly_deposit_crypto;
    }

    /**
     * @return mixed
     */
    public function getDailyWithdrawFiat()
    {
        return $this->daily_withdraw_fiat;
    }

    /**
     * @param mixed $daily_withdraw_fiat
     */
    public function setDailyWithdrawFiat($daily_withdraw_fiat)
    {
        $this->daily_withdraw_fiat = $daily_withdraw_fiat;
    }

    /**
     * @return mixed
     */
    public function getMonthlyWithdrawFiat()
    {
        return $this->monthly_withdraw_fiat;
    }

    /**
     * @param mixed $monthly_withdraw_fiat
     */
    public function setMonthlyWithdrawFiat($monthly_withdraw_fiat)
    {
        $this->monthly_withdraw_fiat = $monthly_withdraw_fiat;
    }

    /**
     * @return mixed
     */
    public function getDailyWithdrawCrypto()
    {
        return $this->daily_withdraw_crypto;
    }

    /**
     * @param mixed $daily_withdraw_crypto
     */
    public function setDailyWithdrawCrypto($daily_withdraw_crypto)
    {
        $this->daily_withdraw_crypto = $daily_withdraw_crypto;
    }

    /**
     * @return mixed
     */
    public function getMonthlyWithdrawCrypto()
    {
        return $this->monthly_withdraw_crypto;
    }

    /**
     * @param mixed $monthly_withdraw_crypto
     */
    public function setMonthlyWithdrawCrypto($monthly_withdraw_crypto)
    {
        $this->monthly_withdraw_crypto = $monthly_withdraw_crypto;
    }


}