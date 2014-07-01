<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 7/1/14
 * Time: 1:16 AM
 */

namespace Arbaf\FinancialApiBundle\Tests\RequestBuilder;

use Symfony\Component\Security\Core\Util\SecureRandom;

class SignatureHeaderBuilder {
    public static function build($access_key, $access_secret_b64){
        $access_secret = base64_decode($access_secret_b64);
        $generator = new SecureRandom();
        $nonce = md5($generator->nextBytes(32));
        $timestamp = time();
        $algorithm = "SHA256";
        $stringToEncrypt = $access_key.$nonce.$timestamp;
        $signature = hash_hmac($algorithm, $stringToEncrypt, $access_secret);
        return 'Signature '
            ."access-key=\"$access_key\", "
            ."nonce=\"$nonce\", "
            ."timestamp=\"$timestamp\", "
            ."algorithm=\"$algorithm\", "
            ."signature=\"$signature\"";
    }
}