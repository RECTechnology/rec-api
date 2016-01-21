<?php
// src/Telepay/FinancialApiBundle/Entity/Client.php

namespace Telepay\FinancialApiBundle\Entity;

use FOS\OAuthServerBundle\Entity\Client as BaseClient;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Client extends BaseClient
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\User")
     */
    private $user;

    /**
     * @ORM\Column(type="string")
     */
    private $swift_list;

    public function __construct()
    {
        parent::__construct();
        // your own logic
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getSwiftList()
    {
        return json_decode($this->swift_list);
    }

    /**
     * @param mixed $swift_list
     */
    public function setSwiftList($swift_list)
    {

        $actual_list = $this->getSwiftList();
        $new_list = array();
        $add_list = array();
        if($actual_list != ''){
            foreach ($swift_list as $swift){
                $matriz = preg_grep('/'.$swift.'/i',$actual_list);

                if(count($matriz) >= 1){
                    $new_list[] = implode('',$matriz);
                }else{
                    $add_list[] = $swift;
                }

            }
            foreach($add_list as $add){
                $new_list[] = $add.':0';
            }

        }else{
            foreach($swift_list as $swift){
                $new_list[] = $swift.':0';
            }
        }

        $this->swift_list = json_encode($new_list);
    }

    /**
     * @param mixed $swift_list
     */
    public function activeSwiftList($swift_list)
    {
        $actual_list = $this->getSwiftList();
        $new_list = array();

        for($i = 0; $i<count($actual_list); $i++){
            $params = explode(':',$actual_list[$i]);
            $actual_list[$i] = $params[0].':0';
        }

        for($j = 0; $j<count($actual_list); $j++){
            foreach($swift_list as $swift){
                if(preg_match('/'.$swift.'/i',$actual_list[$j])){
                    $actual_list[$j] = $swift.':1';
                }
            }
        }


        $this->swift_list = json_encode($actual_list);
    }

    /**
     * @param mixed $cname
     */
    public function addService($cname){
        $new = array($cname);
        $merge = array_merge($this->swift_list, $new);
        $result = array_unique($merge, SORT_REGULAR);
        $this->swift_list = json_encode($result);
    }

    /**
     * @param mixed $cname
     */
    public function removeService($cname){
        $result = array_diff(json_decode($this->swift_list), array($cname));
        $this->swift_list = json_encode(array_values($result));
    }

}