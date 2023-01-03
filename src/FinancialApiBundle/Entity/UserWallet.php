<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/13/15
 * Time: 6:50 PM
 */

namespace App\FinancialApiBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;


/**
 * @ORM\Entity
 */
class UserWallet {

    const STATUS_ENABLED = 'enabled';
    const STATUS_DISABLED = 'disabled';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"user"})
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     * @Groups({"user"})
     */
    private $currency;

    /**
     * @ORM\Column(type="float")
     * @Groups({"user"})
     */
    private $available;

    /**
     * @ORM\Column(type="float")
     * @Groups({"user"})
     */
    private $balance;

    /**
     * @ORM\Column(type="float")
     */
    private $backup = 0;

    /**
     * @ORM\Column(type="float")
     */
    private $oldBalance = 0;

    /**
     * @ORM\Column(type="float")
     */
    private $blockchain = 0;

    /**
     * @ORM\Column(type="float")
     */
    private $blockchain_pending = 0;

    private $scale;

    /**
     * @ORM\Column(type="string")
     */
    private $status = 'enabled';

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Group", inversedBy="wallets")
     * @Groups({"admin"})
     */
    private $group;


    public function getAvailable()
    {
        return $this->available;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @return mixed
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * @param mixed $balance
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;
    }

    /**
     * @param mixed $available
     */
    public function setAvailable($available)
    {
        $this->available = $available;
    }

    /**
     * @return mixed
     */
    public function getBlockchain()
    {
        return $this->blockchain;
    }

    /**
     * @param mixed $blockchain
     */
    public function setBlockchain($blockchain)
    {
        $this->blockchain = $blockchain;
    }

    /**
     * @return mixed
     */
    public function getBackUp()
    {
        return $this->backup;
    }

    /**
     * @param mixed $backup
     */
    public function setBackup($backup)
    {
        $this->backup = $backup;
    }

    /**
     * @return mixed
     */
    public function getOldBalance()
    {
        return $this->oldBalance;
    }

    /**
     * @param mixed $oldBalance
     */
    public function setOldBalance($oldBalance)
    {
        $this->oldBalance = $oldBalance;
    }

    /**
     * @return mixed
     */
    public function getBlockchainPending()
    {
        return $this->blockchain_pending;
    }

    /**
     * @param mixed $blockchain_pending
     */
    public function setBlockchainPending($blockchain_pending)
    {
        $this->blockchain_pending = $blockchain_pending;
    }

    /**
     * @param mixed $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
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

    public function getWalletView(){
        $this->scale=$this->getScale();
        $wallet['id'] = $this->getId();
        $wallet['currency'] = $this->getCurrency();
        $wallet['available'] = intval($this->getAvailable());
        $wallet['balance'] = intval($this->getBalance());
        $wallet['scale'] = $this->getScale();
        $wallet['status'] = $this->getStatus();

        return $wallet;
    }

    //TODO quitar esto de aqui porque se repite en varios sitios
    public function getScale(){
        $currency=$this->getCurrency();
        $scale=0;
        switch($currency){
            case "EUR":
                $scale=2;
                break;
            case "ROSA":
            case "REC":
                $scale=8;
                break;
            case "":
                $scale=0;
                break;
        }
        return $scale;
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
     * @param mixed $amount
     */
    public function addBalance($amount){

        $this->available = $this->available + $amount;
        $this->balance = $this->balance + $amount;

    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }
}