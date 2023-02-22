<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/23/15
 * Time: 6:44 PM
 */

namespace App\DependencyInjection\Transactions\Core;


use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Document\Transaction;

class AbstractMethod implements MethodInterface, CashInInterface, CashOutInterface{

    /**
     * @var string name
     */
    private $name;

    /**
     * @var string cname
     */
    private $cname;

    /**
     * @var string type
     */
    private $type;

    /**
     * @var string currency
     */
    private $currency;

    /**
     * @var string base64_image
     */
    private $base64_image;

    /**
     * @var string image
     */
    private $image;

    /**
     * @var string emial_required
     */
    private $email_required;

    /**
     * @var string min_tier
     */
    private $min_tier;

    /**
     * @var string min_tier
     */
    private $status = 'available';

    /**
     * @var array fees
     */
    private $fees = 'no fees';

    /**
     * @var array limits
     */
    private $limits = 'unlimited';

    function __construct($name, $cname, $type, $currency, $email_required, $base64_image, $image, $min_tier)
    {
        $this->name = $name;
        $this->cname = $cname;
        $this->currency = $currency;
        $this->type = $type;
        $this->email_required = $email_required;
        $this->base64_image = $base64_image;
        $this->min_tier = $min_tier;
        $this->image = $image;

    }


    public function getName()
    {
        return $this->name;
    }

    public function getCname()
    {
        return $this->cname;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function getBase64Image()
    {
        return $this->base64_image;
    }

    public function getEmailRequired()
    {
        return $this->email_required == "true";
    }

    public function getPayInInfo($account_id, $amount)
    {
        // TODO: Implement getPayInInfo() method.
    }

    public function getPayInStatus($paymentInfo)
    {
        // TODO: Implement getPayInStatus() method.
    }

    public function send($paymentInfo)
    {
        // TODO: Implement send() method.
    }

    public function getPayOutStatus($id)
    {
        // TODO: Implement getPayOutStatus() method.
    }

    public function getPayOutInfo($request)
    {
        // TODO: Implement getPayOutInfo() method.
    }

    public function getMinimumAmount()
    {
        // TODO: Implement getPayOutInfo() method.
    }

    /**
     * @return string
     */
    public function getMinTier()
    {
        return $this->min_tier;
    }

    /**
     * @param string $min_tier
     */
    public function setMinTier($min_tier)
    {
        $this->min_tier = $min_tier;
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @param array $fees
     */
    public function setFees($fees)
    {
        $this->fees = $fees;
    }

    /**
     * @param array $limits
     */
    public function setLimits($limits)
    {
        $this->limits = $limits;
    }
}