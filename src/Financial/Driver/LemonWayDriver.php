<?php

namespace App\Financial\Driver;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class LemonWayDriver
 * @package App\Financial\Driver
 */
class LemonWayDriver implements LemonWayInterface {

    private $url;
    private $login;
    private $pass;
    private $version;
    private $admin_account;
    private $language;
    private $ua;
    private $ssl;

    /**
     * LemonWayDriver constructor.
     * @param $url
     * @param $login
     * @param $pass
     * @param $version
     * @param $admin_account
     */
    function __construct($url, $login, $pass, $version, $admin_account){
        $this->url = $url;
        $this->login = $login;
        $this->pass = $pass;
        $this->version = $version;
        $this->admin_account = $admin_account;
        $this->language = 'en';
        $this->ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'ua';
        /**
         * Only activate it if your PHP server knows how to verify the certifcates.
         * (You will have to configure the  the CURLOPT_CAINFO option or the CURLOPT_CAPATH option)
         * https://curl.haxx.se/libcurl/c/CURLOPT_SSL_VERIFYPEER.html
         * https://stackoverflow.com/a/18972719/347051
         */
        $this->ssl = false;
    }

    function getUserIP() {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        else {
            $ip = "127.0.0.1";
        }
        return $ip;
    }

    function callService($serviceName, $parameters) {
        // add missing required parameters
        $parameters['wlLogin'] = $this->login;
        $parameters['wlPass'] = $this->pass;
        $parameters['version'] = $this->version;
        $parameters['walletIp'] = $this->getUserIP();
        $parameters['walletUa'] = $this->ua;
        // wrap to 'p'
        $request = json_encode(array('p' => $parameters));
        $serviceUrl = $this->url . '/' . $serviceName;
        $headers = array("Content-type: application/json;charset=utf-8",
            "Accept: application/json",
            "Cache-Control: no-cache",
            "Pragma: no-cache"
            //"Content-Length:".strlen($request)
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $serviceUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->ssl);

        $response = curl_exec($ch);
        $network_err = curl_errno($ch);
        if ($network_err) {
            error_log('curl_err: ' . $network_err);
            throw new HttpException(403, 'LemonError code: ' . $network_err);
        }
        else {
            $httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($httpStatus == 200)  {
                $unwrapResponse = json_decode($response)->d;
                $businessErr = $unwrapResponse->E;
                if ($businessErr) {
                    //error_log($businessErr->Code." - ".$businessErr->Msg." - Technical info: ".$businessErr->Error);
                    //throw new HttpException(403, $businessErr->Code." - ".$businessErr->Msg);
                    $methodName = strtoupper($serviceName);
                    return array(
                        $methodName => array(
                            'STATUS' => '-1',
                            'ERROR' => $businessErr->Code,
                            'MESSAGE' => $businessErr->Msg
                        )
                    );
                }
                return $unwrapResponse;
            }
            else {
                throw new HttpException($httpStatus, "Service return HttpStatus" . $httpStatus);
            }
        }
    }
}