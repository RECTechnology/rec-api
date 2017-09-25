<?php

namespace Telepay\FinancialApiBundle\Financial\Driver;


class EthereumDriver
{
    private $proto;
    private $host;
    private $port;
    private $CACertificate;

    // Information and debugging
    public $status;
    public $error;
    public $raw_response;
    public $response;

    private $id = 0;

    /**
     * @param string $host
     * @param int $port
     */
    function __construct($host = 'localhost', $port = 8545)
    {
        $this->host = $host;
        $this->port = $port;

        // Set some defaults
        $this->proto = 'http';
        $this->CACertificate = null;
    }

    /**
     * @param string|null $certificate
     */
    function setSSL($certificate = null)
    {
        $this->proto = 'https'; // force HTTPS
        $this->CACertificate = $certificate;
    }

    function __call($method, $params)
    {
        $this->status = null;
        $this->error = null;
        $this->raw_response = null;
        $this->response = null;

        // If no parameters are passed, this will be an empty array
        $params = array_values($params);

        // The ID should be unique for each call
        $this->id++;

        if($method == "eth_sendTransaction"){
            $request_data = $params;
            $params = array(
                "from" => $request_data[0],
                "to" => $request_data[1],
                "value" => "0x" . dechex($request_data[2])
            );
            // Build the request, it's ok that params might have any empty array
            $request = json_encode(array(
                'jsonrpc' => "2.0",
                'method' => $method,
                'params' => array($params),
                'id' => 1
            ));
        }
        else {
            // Build the request, it's ok that params might have any empty array
            $request = json_encode(array(
                'jsonrpc' => "2.0",
                'method' => $method,
                'params' => $params,
                'id' => 1
            ));
        }
        // Build the cURL session
        $curl = curl_init("{$this->proto}://{$this->host}:{$this->port}");
        $options = array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_FOLLOWLOCATION => TRUE,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_HTTPHEADER => array('Content-type: application/json'),
            CURLOPT_POST => TRUE,
            CURLOPT_POSTFIELDS => $request
        );

        // This prevents users from getting the following warning when open_basedir is set:
        // Warning: curl_setopt() [function.curl-setopt]: CURLOPT_FOLLOWLOCATION cannot be activated when in safe_mode or an open_basedir is set
        if (ini_get('open_basedir')) {
            unset($options[CURLOPT_FOLLOWLOCATION]);
        }

        if ($this->proto == 'https') {
            // If the CA Certificate was specified we change CURL to look for it
            if ($this->CACertificate != null) {
                $options[CURLOPT_CAINFO] = $this->CACertificate;
                $options[CURLOPT_CAPATH] = DIRNAME($this->CACertificate);
            } else {
                // If not we need to assume the SSL cannot be verified so we set this flag to FALSE to allow the connection
                $options[CURLOPT_SSL_VERIFYPEER] = FALSE;
            }
        }

        curl_setopt_array($curl, $options);

        // Execute the request and decode to an array
        $this->raw_response = curl_exec($curl);
        $this->response     = json_decode($this->raw_response, TRUE);

        // If the status is not 200, something is wrong
        $this->status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // If there was no error, this will be an empty string
        $curl_error = curl_error($curl);

        curl_close($curl);

        if (!empty($curl_error)) {
            $this->error = $curl_error;
        }

        if (isset($this->response['error'])) {
            // If bitcoind returned an error, put that in $this->error
            $this->error = $this->response['error']['message'];
        } elseif ($this->status != 200) {
            // If bitcoind didn't return a nice error message, we need to make our own
            switch ($this->status) {
                case 400:
                    $this->error = 'HTTP_BAD_REQUEST';
                    break;
                case 401:
                    $this->error = 'HTTP_UNAUTHORIZED';
                    break;
                case 403:
                    $this->error = 'HTTP_FORBIDDEN';
                    break;
                case 404:
                    $this->error = 'HTTP_NOT_FOUND';
                    break;
            }
        }

        if ($this->error) {
            return FALSE;
        }
        return $this->response['result'];
    }
}