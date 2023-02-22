<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 4:42 AM
 */

namespace App\Financial\Methods;

use App\Document\Transaction;
use FOS\OAuthServerBundle\Util\Random;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\DependencyInjection\Transactions\Core\BaseMethod;
use App\Financial\Currency;
use App\Financial\Buffer;
use App\Financial\Encoder;

class RecMethod extends BaseMethod {

    private $driver;
    /** @var ContainerInterface $container */
    private $container;

    private $min_confirmations;
    private $OP_RETURN_BTC_FEE;
    private $OP_RETURN_BTC_DUST;
    private $OP_RETURN_MAX_BYTES;

    public function __construct($name, $cname, $type, $currency, $email_required, $base64Image, $image, $container, $driver, $min_tier, $default_fixed_fee, $default_variable_fee, $minimum){
        parent::__construct($name, $cname, $type, $currency, $email_required, $base64Image, $image, $container, $min_tier, $default_fixed_fee, $default_variable_fee);
        $this->driver = $driver;
        $this->container = $container;
        $this->minimum = $minimum;

        $this->min_confirmations = $this->container->getParameter('rec_min_confirmations');
        $this->OP_RETURN_BTC_FEE = floatval($this->container->getParameter('OP_RETURN_BTC_FEE')); // BTC fee to pay per transaction
        $this->OP_RETURN_BTC_DUST = 0.00001; // omit BTC outputs smaller than this
        $this->OP_RETURN_MAX_BYTES = 1000; // maximum bytes in an OP_RETURN (80 as of Bitcoin 0.11)(1000 for Crea forks)
    }

    public function validateaddress($address){
        $address_verification = $this->driver->validateaddress($address);
        return $address_verification['isvalid'];
    }

    public function getnewaddress($account){
        $address = $this->driver->getaccountaddress((string)$account);
        return $address;
    }

    //PAY IN
    public function getPayInInfo($account_id, $amount){
        $address = $this->getnewaddress($account_id);
        if(!$address) throw new Exception('Service Temporally unavailable', 503);
        $response = array(
            'amount'    =>  $amount,
            'currency'  =>  $this->getCurrency(),
            'scale' =>  Currency::$SCALE[$this->getCurrency()],
            'address' => $address,
            'expires_in' => intval(1200),
            'received' => 0.0,
            'min_confirmations' => intval($this->min_confirmations),
            'confirmations' => 0,
            'status'    =>  'created',
            'final'     =>  false
        );
        return $response;
    }

    public function getPayInInfoWithData($data){
        $address = $data;
        if(!$address) throw new Exception('Service Temporally unavailable', 503);
        $response = array(
            'amount'    =>  $data['amount'],
            'currency'  =>  $this->getCurrency(),
            'scale' =>  Currency::$SCALE[$this->getCurrency()],
            'address' => $data['address'],
            'expires_in' => intval(1200),
            'received' => $data['amount'],
            'txid' => $data['txid'],
            'min_confirmations' => intval($this->min_confirmations),
            'confirmations' => 0,
            'status'    =>  'received',
            'final'     =>  false
        );
        if(isset($data['internal_tx']) && $data['internal_tx'] == '1' && isset($data['destionation_id'])){
            $response['destionation_id']=$data['destionation_id'];
        }
        return $response;
    }


    public function getCurrency(){
        return $this->container->getParameter("crypto_currency");
    }

    public function getConfirmations($txid){
        //$data = $this->driver->gettransaction($txid);
        //return $data['confirmations'];
        return 1;
    }

    public function getPayInStatus($paymentInfo){
        if(isset($paymentInfo['txid'])) {
            $confirmations = $this->getConfirmations($paymentInfo['txid']);
            $paymentInfo['confirmations'] = $confirmations;
            if ($paymentInfo['confirmations'] >= $paymentInfo['min_confirmations']) {
                $status = 'success';
                $final = true;
                $paymentInfo['final'] = $final;
            } else {
                $status = 'received';
            }
            $paymentInfo['status'] = $status;
            return $paymentInfo;
        }

        $allReceived = $this->driver->listreceivedbyaddress($this->min_confirmations, true);
        $amount = $paymentInfo['amount'];
        $address = $paymentInfo['address'];

        if($amount <= 100)
            $margin = 0;
        else
            $margin = 100;

        $allowed_amount = $amount - $margin;
        foreach($allReceived as $cryptoData){
            if($cryptoData['address'] === $address){
                $paymentInfo['received'] = doubleval($cryptoData['amount'])*1e8;
                if(doubleval($cryptoData['amount'])*1e8 >= $allowed_amount){
                    $paymentInfo['confirmations'] = $cryptoData['confirmations'];
                    if($paymentInfo['confirmations'] >= $paymentInfo['min_confirmations']){
                        $status = 'success';
                        $final = true;
                        $paymentInfo['final'] = $final;
                    }else{
                        $status = 'received';
                    }
                }else{
                    $status = 'created';
                }
                $paymentInfo['status'] = $status;
                return $paymentInfo;
            }
        }
    }

    //PAY OUT
    public function getPayOutInfo($request){
        $paramNames = array(
            'amount',
            'address'
        );

        $params = array();
        foreach($paramNames as $param){
            if(!$request->request->has($param)) throw new HttpException(400, 'Parameter '.$param.' not found');
            if($request->request->get($param) == null) throw new Exception( 'Parameter '.$param.' can\'t be null', 404);
            $params[$param] = $request->request->get($param);
        }

        $address_verification = $this->driver->validateaddress($params['address']);
        if(!$address_verification['isvalid']) throw new Exception('Invalid address.', 400);

        if($request->request->has('concept')){
            $params['concept'] = $request->request->get('concept');
        }else{
            $params['concept'] = 'Rec out Transaction';
        }

        $params['find_token'] = $find_token = substr(Random::generateToken(), 0, 6);
        $params['currency'] = $this->getCurrency();
        $params['scale'] = Currency::$SCALE[$this->getCurrency()];
        $params['final'] = false;
        $params['status'] = false;
        return $params;
    }

    public function getPayOutInfoData($data){
        $paramNames = array(
            'amount',
            'address'
        );
        $params = array();

        foreach($paramNames as $param){
            if(!array_key_exists($param, $data)) throw new HttpException(404, 'Parameter '.$param.' not found');
            if($data[$param] == null) throw new Exception( 'Parameter '.$param.' can\'t be null', 404);
            $params[$param] = $data[$param];
        }

        //$address_verification = $this->driver->validateaddress($params['address']);
        //if(!$address_verification['isvalid']) throw new Exception('Invalid address.', 400);

        //$saldo = $this->getReceivedByAddress($data['orig_address'],1);
        //if(($saldo + 0.01) < ($params['amount'] / 1e8)) throw new HttpException(403, 'Not enough balance in your account(' . $saldo . ' < ' . $params['amount']/1e8 . ')');
        //if($saldo < ($params['amount'] / 1e8)) $params['amount'] = $saldo * 1e8;

        if(array_key_exists('concept', $data)) {
            $params['concept'] = $data['concept'];
        }else{
            $params['concept'] = 'Rec out Transaction';
        }

        $params['find_token'] = $find_token = substr(Random::generateToken(), 0, 6);
        $params['currency'] = $this->getCurrency();
        $params['scale'] = Currency::$SCALE[$this->getCurrency()];
        $params['final'] = false;
        $params['status'] = false;

        return $params;
    }

    public function getMinimumAmount(){
        return $this->minimum;
    }

    public function send($paymentInfo){
        $orig_address= $paymentInfo['orig_address'];
        $dest_address = $paymentInfo['dest_address'];
        $amount = $paymentInfo['amount'];

        $response = array();
        $response['address'] = $paymentInfo['address'];
        $response['amount'] = $paymentInfo['amount'];

        $treasure_address = $this->container->getParameter('treasure_address');
        $root_group_address = $this->container->getParameter('root_group_address');

        if($orig_address==$treasure_address && $dest_address!=$root_group_address){
            $response['status'] = Transaction::$STATUS_FAILED;
            $response['error'] = 'Forbidden transaction';
            $response['final'] = true;
            return $response;
        }

        $admin_key = $this->container->getParameter('admin_key');
        $saved_data_version = $this->container->getParameter('saved_data_version');
        $saved_data_subversion = $this->container->getParameter('saved_data_subversion');
        $random_pass = substr(Random::generateToken(), 0, 24);
        $orig_nif = $paymentInfo['orig_nif'];
        $orig_group_nif = $paymentInfo['orig_group_nif'];
        $orig_group_public = $paymentInfo['orig_group_public']?"1":"0";
        $orig_key = $paymentInfo['orig_key'];
        $dest_group_nif = $paymentInfo['dest_group_nif'];
        $dest_group_public = $paymentInfo['dest_group_public']?"1":"0";
        $dest_key = $paymentInfo['dest_key'];

        $data_users = array($orig_nif, $orig_group_nif, $dest_group_nif);

        $encoder = new Encoder();
        $em_pass = $encoder->encrypt($random_pass, $orig_key);
        $rec_pass = $encoder->encrypt($random_pass, $dest_key);
        $ad_pass = $encoder->encrypt($random_pass, $admin_key);
        $tx_data = $encoder->encrypt(json_encode($data_users), $random_pass);

        $data = $saved_data_version . "," . $saved_data_subversion . "," . $orig_group_public . "," . $dest_group_public  . "," . $em_pass . "," . $rec_pass . "," . $ad_pass . "," . $tx_data;
        if (strlen($data) == 0){
            $response['status'] = Transaction::$STATUS_FAILED;
            $response['final'] = true;
            $response['error'] = 'Some data is required to be stored';
            return $response;
        }

        //$crypto = $this->send_with_OP_RETURN_data($orig_address, $dest_address, $amount/1e8, $data);
        $crypto['txid'] = substr(Random::generateToken(), 0, 48);

        if(isset($crypto['error'])){
            $response['status'] = Transaction::$STATUS_FAILED;
            $response['error'] = $crypto['error'];
        }else{
            $response['txid'] = $crypto['txid'];
            $response['status'] = 'sent';
        }

        if(isset($crypto['inputs'])) {
            $response['inputs'] = $crypto['inputs'];
            $response['outputs'] = $crypto['outputs'];
            $response['metadata_len'] = $crypto['metadata_len'];
            $response['input_total'] = $crypto['input_total'];
        }
        if(isset($crypto['message'])){
            $response['message'] = $crypto['message'];
            $response['len_message'] = $crypto['len_message'];
            $response['raw'] = $crypto['raw'];
            $response['len'] = $crypto['len'];
        }
        $response['final'] = true;

        return $response;
    }

    private function send_with_OP_RETURN_data($orig_address, $send_address, $send_amount, $metadata){
        $result = $this->driver->validateaddress($send_address);
        if (!$result['isvalid']) {
            return array('error' => 'Send address could not be validated: ' . $send_address);
        }

        $metadata_len=strlen($metadata);

        if ($metadata_len > 65536 || $metadata_len > $this->OP_RETURN_MAX_BYTES) {
            return array('error' => 'Metadata too large');
        }

        $orig_account = $this->driver->getaccount($orig_address);

        //	Calculate amounts and choose inputs
        $output_amount=$send_amount+$this->OP_RETURN_BTC_FEE;
        $inputs_spend=$this->select_inputs($orig_account, $output_amount);

        if (isset($inputs_spend['error'])) {
            return $inputs_spend;
        }

        $change_amount=$inputs_spend['total']-$output_amount;
        //$change_address = $this->driver->getrawchangeaddress($orig_account);
        $change_address = $orig_address;
        $outputs=array($send_address => (float)$send_amount);

        if ($change_amount >= $this->OP_RETURN_BTC_DUST) {
            $outputs[$change_address] = round($change_amount,8);
        }
        $raw_txn=$this->create_txn($inputs_spend['inputs'], $outputs, $metadata, count($outputs));
        if($raw_txn=='00000000000000000000'){
            $data = array($inputs_spend['inputs'], $outputs, $metadata, count($outputs));
            return array(
                'error' => 'Create txn error',
                'message' => json_encode($data),
                'len_message' => strlen(json_encode($data)),
                'raw' => $raw_txn,
                'len' => strlen($metadata),
                'inputs' => count($inputs_spend['inputs']),
                'outputs' => count($outputs),
                'metadata_len' => $metadata_len,
                'input_total' => $inputs_spend['total']
            );
        }

        //	Sign and send the transaction, return result
        $sent_info = $this->sign_send_txn($raw_txn);

        //  Set account for new addresses
        if(isset($sent_info['txid'])) {
            foreach ($outputs as $output_address => $output_amount) {
                if(strcmp((string)$send_address, (string)$output_address)!=0){
                    if(strlen($this->driver->getaccount($output_address))<1){
                        $this->driver->setaccount($output_address, $orig_account);
                    }
                }
            }
        }
        $sent_info['inputs'] = count($inputs_spend['inputs']);
        $sent_info['outputs'] = count($outputs);
        $sent_info['metadata_len'] = $metadata_len;
        $sent_info['input_total'] = $inputs_spend['total'];
        return $sent_info;
    }

    private function select_inputs($account_name, $total_amount){
        //	List and sort unspent inputs by priority
        $unspent_inputs = $this->driver->listunspent($this->min_confirmations);

        if (!is_array($unspent_inputs)) {
            return array('error' => 'Could not retrieve list of inputs');
        }

        //	Identify which inputs should be spent
        $inputs_spend=array();
        $input_amount=0;
        foreach ($unspent_inputs as $unspent_input) {
            if(strcmp((string)$unspent_input['account'], (string)$account_name)==0){
                $inputs_spend[]=$unspent_input;
                $input_amount+=$unspent_input['amount'];
                if ($input_amount>=$total_amount) {
                    break;
                }
            }
        }

        if(count($inputs_spend)<1) {
            return array('error' => 'Could not retrieve list of unspent inputs.');
        }

        if ($input_amount<$total_amount) {
            return array('error' => 'Not enough funds are available to cover the amount and fee');
        }

        return array(
            'inputs' => $inputs_spend,
            'total' => $input_amount,
        );
    }

    private function create_txn($inputs, $outputs, $metadata, $metadata_pos){
        $raw_txn=$this->driver->createrawtransaction($inputs, $outputs);
        //$fundata=$this->driver->fundrawtransaction($raw_txn, "", "", false, true, true);
        //$txn_unpacked=$this->unpack_txn(pack('H*', $fundata));
        $txn_unpacked=$this->unpack_txn(pack('H*', $raw_txn));

        $metadata_len=strlen($metadata);

        if ($metadata_len<=75)
            $payload=chr($metadata_len).$metadata; // length byte + data (https://en.bitcoin.it/wiki/Script)
        elseif ($metadata_len<=256)
            $payload="\x4c".chr($metadata_len).$metadata; // OP_PUSHDATA1 format
        else
            $payload="\x4d".chr($metadata_len%256).chr(floor($metadata_len/256)).$metadata; // OP_PUSHDATA2 format

        $metadata_pos=min(max(0, $metadata_pos), count($txn_unpacked['vout'])); // constrain to valid values

        array_splice($txn_unpacked['vout'], $metadata_pos, 0, array(array(
            'value' => 0,
            'scriptPubKey' => '6a'.reset(unpack('H*', $payload)), // here's the OP_RETURN
        )));

        return reset(unpack('H*', $this->pack_txn($txn_unpacked)));
    }

    private function unpack_txn($binary){
        $buffer = new Buffer($binary);
        return $this->unpack_txn_buffer($buffer);
    }

    private function unpack_txn_buffer(Buffer $buffer){
        $txn=array();

        $txn['version']=$buffer->shift_unpack(4, 'V'); // small-endian 32-bits
        for ($inputs=$buffer->shift_varint(); $inputs>0; $inputs--) {
            $input=array();
            $input['txid']=$buffer->shift_unpack(32, 'H*', true);
            $input['vout']=$buffer->shift_unpack(4, 'V');
            $length=$buffer->shift_varint();
            $input['scriptSig']=$buffer->shift_unpack($length, 'H*');
            $input['sequence']=$buffer->shift_unpack(4, 'V');
            $txn['vin'][]=$input;
        }

        for ($outputs=$buffer->shift_varint(); $outputs>0; $outputs--) {
            $output=array();
            $output['value']=$buffer->shift_uint64()/100000000;
            $length=$buffer->shift_varint();
            $output['scriptPubKey']=$buffer->shift_unpack($length, 'H*');

            $txn['vout'][]=$output;
        }

        $txn['locktime']=$buffer->shift_unpack(4, 'V');

        return $txn;
    }

    private function pack_txn($txn){
        $binary='';
        $binary.=pack('V', $txn['version']);
        $binary.=$this->pack_varint(count($txn['vin']));
        foreach ($txn['vin'] as $input) {
            $binary.=strrev(pack('H*', $input['txid']));
            $binary.=pack('V', $input['vout']);
            $binary.=$this->pack_varint(strlen($input['scriptSig'])/2); // divide by 2 because it is currently in hex
            $binary.=pack('H*', $input['scriptSig']);
            $binary.=pack('V', $input['sequence']);
        }
        $binary.=$this->pack_varint(count($txn['vout']));
        foreach ($txn['vout'] as $output) {
            $binary.=$this->pack_uint64(round($output['value']*100000000));
            $binary.=$this->pack_varint(strlen($output['scriptPubKey'])/2); // divide by 2 because it is currently in hex
            $binary.=pack('H*', $output['scriptPubKey']);
        }
        $binary.=pack('V', $txn['locktime']);
        return $binary;
    }

    private function pack_varint($integer){
        if ($integer>0xFFFFFFFF)
            $packed="\xFF".$this->pack_uint64($integer);
        elseif ($integer>0xFFFF)
            $packed="\xFE".pack('V', $integer);
        elseif ($integer>0xFC)
            $packed="\xFD".pack('v', $integer);
        else
            $packed=pack('C', $integer);
        return $packed;
    }

    private function pack_uint64($integer){
        $upper=floor($integer/4294967296);
        $lower=$integer-$upper*4294967296;
        return pack('V', $lower).pack('V', $upper);
    }

    private function sign_send_txn($raw_txn){
        $signed_txn = $this->driver->signrawtransaction($raw_txn);
        if (!$signed_txn['complete']) {
            return array('error' => 'Could not sign the transaction');
        }
        elseif ($signed_txn['hex']=='00000000000000000000'){
            return array(
                'error' => 'Error signing the transaction',
                'message' => json_encode($signed_txn),
                'len_message' => strlen($signed_txn),
                'raw' => $raw_txn,
                'len' => strlen($signed_txn['hex'])
            );
        }

        $send_txid = $this->driver->sendrawtransaction($signed_txn['hex']);
        if (strlen($send_txid)!=64) {
            return array(
                'error' => 'Could not send the transaction.',
                'message' => $send_txid,
                'len_message' => strlen($send_txid),
                'raw' => $signed_txn['hex'],
                'len' => strlen($signed_txn['hex'])
            );
        }
        return array('txid' => $send_txid);
    }

    public function getPayOutStatus($id){
        // TODO: Implement getPayOutStatus() method.
    }

    public function cancel($payment_info){
        throw new Exception('Method not implemented', 409);
    }

    public function getReceivedByAddress($address, $min_confirmations = -1){
        if ($min_confirmations < 0) {
            $min_confirmations = $this->min_confirmations;
        }

        $account = $this->driver->getaccount($address);
        $unspent_inputs = $this->driver->listunspent($min_confirmations);

        if (!is_array($unspent_inputs)) {
            return 0;
        }

        $input_amount=0;
        foreach ($unspent_inputs as $index => $unspent_input) {
            if(strcmp((string)$unspent_input['account'], (string)$account)==0){
                $input_amount+=$unspent_input['amount'];
            }
        }
        return $input_amount;
    }

    public function getInfo(){
        $info = $this->driver->getinfo();
        return $info;
    }

    //	Sort-by utility functions
    private function sort_by(&$array, $by1, $by2=null){
        global $sort_by_1, $sort_by_2;
        $sort_by_1=$by1;
        $sort_by_2=$by2;
        uasort($array, array($this, 'sort_by_fn'));
    }

    private function sort_by_fn($a, $b){
        global $sort_by_1, $sort_by_2;
        $compare=$this->sort_cmp($a[$sort_by_1], $b[$sort_by_1]);
        if (($compare==0) && $sort_by_2)
            $compare=$this->sort_cmp($a[$sort_by_2], $b[$sort_by_2]);
        return $compare;
    }

    private function sort_cmp($a, $b){
        if (is_numeric($a) && is_numeric($b)) // straight subtraction won't work for floating bits
            return ($a==$b) ? 0 : (($a<$b) ? -1 : 1);
        else
            return strcasecmp($a, $b); // doesn't do UTF-8 right but it will do for now
    }
}