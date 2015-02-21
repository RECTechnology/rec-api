<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/13/15
 * Time: 5:49 PM
 */

namespace Telepay\FinancialApiBundle\Entity;

use Telepay\FinancialApiBundle\Financial\MoneyStorage;
use Telepay\FinancialApiBundle\Financial\CashIn;
use Telepay\FinancialApiBundle\Financial\CashOut;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class AbstractWallet
 * @ORM\MappedSuperclass
 */
abstract class AbstractWallet implements MoneyStorage, CashIn, CashOut {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\User")
     */
    protected $user;

}