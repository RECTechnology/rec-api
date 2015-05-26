<?php
namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs;
use SOPGClassicMerchantClient;

/**
	* 
	*/
	class PaysafecardPayment
	{
		private $username;
        private $password;
        private $sysLang = 'en';
        //Debug true/false
        private $debug = false;
        //Display Debug true/fam_close(fam)
        private $show_debug = false;
        //Display Errors true/false
        private $show_error = false;
        //AutoCorrect true/false
        private $autoCorrect = true;
        private $env_url;
        private $base_url;

		function __construct($user, $pass, $env_url, $base_url)
		{
			$this->username = $user;
			$this->password = $pass;
            $this->env_url = $env_url;
            $this->base_url = $base_url;
		}

		public function request($mtid,$currency,$amount,$okUrl,$nokUrl,$mCId,$url_final){
            $pnUrl=$this->base_url.$this->env_url.$url_final;
            error_reporting( E_ALL );
            header( "Content-Type: text/html; charset=utf-8" );
            $start = microtime( true );
            // |---------| Systemtest |---------|
            if(phpversion() < 5){ echo 'PHP erfüllt mit der Version '.phpversion().' nicht die Voraussetzungen'; exit;}
            $extensions = get_loaded_extensions();
            if(!in_array('soap',$extensions)){ echo 'Die php_soap.dll wurde nicht geladen.'; exit;}
            //laden der PSC-Klasse
            include_once ( 'lib/class.php' );

            if($this->env_url==''){
                $mode='live';
            }else{
                $mode='test';
            }

            $test = new SOPGClassicMerchantClient( $this->debug, $this->sysLang, $this->autoCorrect, $mode );

            //if the PHP file is called from psc,the range for the PN URL is executed.
            if ( isset( $_GET['pn'] ) )
            {
                //enter the access data
                $test->merchant( $this->username, $this->password );
                //get current status
                $status = $test->getSerialNumbers( $_GET['mtid'], $_GET['cur'], $subId = '' );
                //If the return is 'execute', the amount can be debited (executeDebit)
                if ( $status === 'execute' )
                {
                    $testexecute = $test->executeDebit( $_GET['amo'], '1' );
                    if ( $testexecute === true )
                    {
                        // here user account topup -EXECUTE DEBIT SUCCESSFUL- !!!

                        show_debug();
                    }
                }

                show_debug();
            }
            elseif ( isset( $_GET['ok'] ) )
            {

                //enter the access data
                $test->merchant( $this->username, $this->password );
                //get current status
                $status = $test->getSerialNumbers( $_GET['mtid'], $_GET['cur'], $subId = '' );
                //If the return is 'execute', the amount can be debited (executeDebit)
                if ( $status === 'execute' )
                {
                    $testexecute = $test->executeDebit( $_GET['amo'], '1' );
                    if ( $testexecute === true )
                    {
                        // here the user account topup -EXECUTE DEBIT SUCCESSFUL- !!!
                        show_debug();
                    }
                }
                //regardless whether execute was run or not - a client info must appears in the log with a success or error message
                echo $_GET['mtid'].'<br>'.$_GET['amo'].' '.$_GET['cur'].'<br>';
                echo $test->getLog() . '<br />';

                // DEBUG & ERRORS
                show_debug();
            }
            elseif ( isset( $_GET['nok'] ) )
            {
                //do nok
                echo 'Transaction aborted by user.';
            }
            //The normal first call starts here
            else
            {

                //Set the access data
                $test->merchant( $this->username, $this->password );
                //Enter the information.
                $test->setCustomer( $amount, $currency, $mtid, $mCId );
                //Enter URL´s.
                $test->setUrl( $okUrl, $nokUrl, $pnUrl );
                //createDisposition now creates the transaction under PSC and returns the URL the client can use to make the payment.
                //The URL is generated via getCustomerPanel()!!!
                $paymentPanel = $test->createDisposition();
                if ( $paymentPanel == false )
                {
                    //regardless of the result, an info must be issued for the client.
                    echo $test->getLog() . '<br />';
                    // DEBUG & ERRORS
                    show_debug();
                }
                else
                {
                    //here the creation of the transaction was completed successfully
                    //DB entry

                    //Automatic forwarding either via a link or PHP function header

                    //Header:
                    header("Location:".$paymentPanel);
                    //Link:
                    //echo '<a href="' . $paymentPanel . '" target="_blank">redirecting to Payment Panel</a>';

                    show_debug();
                }
            }
            //echo '<span style="position: absolute; bottom: 0; left: 0; width: 100%; background: #C5C5C5">Processing in: ' . ( microtime( true ) - $start ) . ' seconds</span>';
            function show_debug()
            {
                global $show_debug,$show_error,$test,$debug;
                if($show_debug === true OR $show_error === true)
                {
                    echo '<div style="position: absolute; left: 0; bottom: 20px; height: 300px; width: 100%; overflow: scroll; border: 1px solid black; background: #ACACAC;">';
                }
                if ( $show_debug === true )
                {
                    echo 'DEBUG:<br /> <pre>';
                    var_dump( $test->debug );
                    echo '</pre>';
                }
                if ( $show_error === true )
                {
                    $error = $test->getLog( 'error' );
                    if ( !empty( $error ) )
                    {
                        echo 'DEVELOPMENT-ERRORS:<br />';
                        foreach ( $error as $emsg )
                        {
                            echo $emsg['msg'] . '<br />';
                        }
                    }
                }
                if($show_debug === true OR $show_error === true){echo '</div>';}
                if($debug === true)
                {
                    $line = '|----- DEBUG @'.time().' -----|';
                    foreach($test->debug as $key => $value)
                    {$line .= $key. ' : ' .$value. "\n";}
                }
                if($test->getLog('error') !== 0)
                {
                    if(!isset($line)){$line = '|----- ERROR @'.time().' -----|';}
                    else{$line .= '|----- ERROR @'.time().' -----|';}
                    foreach($test->getLog('error') as $entry)
                    {$line .= serialize($entry)."\n";}
                }
                if(isset($line))
                {
                    $data = fopen('log.txt',"a+");
                    fwrite($data,$line);
                    fclose($data);
                }

            }

		}
	}

