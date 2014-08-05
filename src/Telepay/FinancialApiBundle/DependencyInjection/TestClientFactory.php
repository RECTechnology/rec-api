<?php
/**
 * Created by PhpStorm.
 * User: Rick Moreno
 * Date: 7/2/14
 * Time: 10:30 AM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection;

class TestClientFactory {

    protected static $USER_CREDENTIALS = array(
        'ROLE_SUPER_ADMIN'=>array(
            'access-key'=>'edbeb673024f2d0e23752e2814ca1ac4c589f761',
            'access-secret'=>'wlqDEET8uIr5RN00AMuuceI9LLKMTNLpzlETlX3djVg='
        ),
        'ROLE_ADMIN'=>array(
            'access-key'=>'8eb4763b5feda5a966c6c5749231f45841631f28',
            'access-secret'=>'hqTXol8N2fT7Rx2whVElnV5zbyzoRC8M+EX4G7JJdRA='
        ),
        'ROLE_USER'=>array(
            'access-key'=>'96069af2ab5309e649399271b60f4a0c5b617c2e',
            'access-secret'=>'LN0JMADBHyjX+FUbKig0ibNPEuboJAi3PqO3pBH/tRE='
        ),
    );


    public static function get($role){
        return SignatureHeaderBuilder::build(
            static::$USER_CREDENTIALS[$role]['access-key'],
            static::$USER_CREDENTIALS[$role]['access-secret']
        );
    }

}