<?php
/**
 * Created by PhpStorm.
 * User: Rick Moreno
 * Date: 7/2/14
 * Time: 10:30 AM
 */

namespace App\DependencyInjection;

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
        'user_no_services'=>array(
            'access-key'=>'00d7f2bc9d4a66d6dd0fe964b7f2e9bdb3d4e961',
            'access-secret'=>'KGxlyCWhk0+Z3U5HhwFB/6JHd9VvMMo9L3X8cOLvzYY='
        ),
    );


    public static function get($role){
        return SignatureHeaderBuilder::build(
            static::$USER_CREDENTIALS[$role]['access-key'],
            static::$USER_CREDENTIALS[$role]['access-secret']
        );
    }

}