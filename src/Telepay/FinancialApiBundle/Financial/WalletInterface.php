<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 5/28/15
 * Time: 6:46 PM
 */

namespace Telepay\FinancialApiBundle\Financial;


interface WalletInterface extends CashInInterface, CashOutInterface, MoneyStorageInterface{

}