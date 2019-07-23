<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 7/1/14
 * Time: 1:16 AM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection;


class SignatureHeaderBuilder {
    /**
     * @param $access_key
     * @param $access_secret_b64
     * @return string
     * @throws \Exception
     */
    public static function build($access_key, $access_secret_b64){
        $access_secret = base64_decode($access_secret_b64);
        $nonce = md5(random_bytes(32));
        $timestamp = time();
        $version = 1;
        $algorithm = "SHA256";
        $stringToEncrypt = $access_key.$nonce.$timestamp;
        $signature = hash_hmac($algorithm, $stringToEncrypt, $access_secret);
        return 'Signature '
            ."access-key=\"$access_key\", "
            ."nonce=\"$nonce\", "
            ."timestamp=\"$timestamp\", "
            ."version=\"$version\", "
            ."signature=\"$signature\"";
    }
}