<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/23/15
 * Time: 6:44 PM
 */

namespace App\FinancialApiBundle\DependencyInjection\Transactions\Core;


use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Document\Transaction;

class AbstractService implements ServiceInterface, ServiceLifeCycle {

    /**
     * @var
     */
    private $cash_direction;
    /**
     * @var
     */
    private $currency;

    public function getFields(){
        return array();
    }
    public function create(Transaction $t){
        throw new HttpException(501, "Method not implemented");
    }
    public function update(Transaction $t, $data){
        throw new HttpException(501, "Method not implemented");
    }
    public function check(Transaction $t){
        throw new HttpException(501, "Method not implemented");
    }
    public function notificate(Transaction $transaction, $data){
        throw new HttpException(501, "Method not implemented");
    }

    /**
     * @var string name
     */
    private $name;
    /**
     * @var string role
     */
    private $role;
    /**
     * @var string cname
     */
    private $cname;
    /**
     * @var string base64_image
     */
    private $base64_image;



    function __construct($name, $cname, $role, $cash_direction, $currency, $base64_image)
    {
        $this->name = $name;
        $this->role = $role;
        $this->cname = $cname;
        $this->base64_image = $base64_image;
        $this->cash_direction = $cash_direction;
        $this->currency = $currency;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @return string
     */
    public function getCname()
    {
        return $this->cname;
    }

    /**
     * @return string
     */
    public function getBase64Image()
    {
        return $this->base64_image;
    }

    /**
     * @return mixed
     */
    public function getCashDirection()
    {
        return $this->cash_direction;
    }

    /**
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->currency;
    }

}