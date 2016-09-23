<?php
 
 /**
 * Sentir Development
 *
 * @category   Sentir Web Development
 * @package    CRM - GATEWAY
 * @copyright  Copyright 2014-2016 Sentir Development
 * @license    http://sentir.solutions/license/
 * @version    1.0.15.10
 * @author     Ahmet GOUDENOGLU <ahmet.gudenoglu@sentir-development.com>
 */

include APPPATH."libraries/mpi/library/Centinel_client.php";
require(APPPATH."libraries/mpi/library/Xml_parser.php");
include APPPATH."libraries/mpi/library/Centinel_errors.php";
include APPPATH."libraries/mpi/library/Centinel_utility.php";

class Cardinal {

    private $centinel_msg_version = '1.7';
    private $centinel_processor_id;
    private $centinel_merchant_id;
    private $centinel_transaction_password;
    private $centinel_maps_url;
    private $centinel_term_url;
    private $user_agent;
    private $browser_header;
    private $centinel_timeout_connect = 5000;
    private $centinel_timeout_read = 15000;
    private $centinel_authentication_messaging = 'For your security, please fill out the form below to complete your order.</b><br/>Do not click the refresh or back button or this transaction may be interrupted or cancelled.';
    private $centinel_merchant_logo;
    private $merchant_data = array();
    private $centinel_response;

    public function __construct(){

        $this->instance = & get_instance();
        $this->centinel_processor_id = '202-10';
        $this->centinel_merchant_id = 'ahmet.gudenoglu@semitepayment.com';
        $this->centinel_maps_url = 'https://centineltest.cardinalcommerce.com/maps/txns.asp';
        $this->centinel_transaction_password = 'd2aed0d7e602433';

    }

    public function getCredinalConfig(){

        return array(
            'CentinelMsgVersion'=>$this->centinel_msg_version,
            'ProcessorId'=>$this->centinel_processor_id,
            'MerchantId'=>$this->centinel_merchant_id,
            'TransactionUrl'=>$this->centinel_maps_url,
            'TransactionPassword'=>$this->centinel_transaction_password,
            'CentinelTimeputConnect'=>$this->centinel_timeout_connect,
            'CentinelTimeoutRead'=>$this->centinel_timeout_read
        );
    }

    public function setCentinelResponse($response){

        $this->centinel_response = $response;
    }

    public function getCentinelResponse(){

        return $this->centinel_response;
    }

    public function MPILookup($data){

        /*******************************************************************************/
        /*                                                                             */
        /*Using the local variables and constants, build the Centinel message using the*/
        /*Centinel Thin Client.                                                        */
        /*                                                                             */
        /*******************************************************************************/

        $centinelClient = new CentinelClient;

        $centinelClient->add("MsgType", "cmpi_lookup");
        $centinelClient->add("Version", $this->centinel_msg_version);
        $centinelClient->add("ProcessorId", $this->centinel_processor_id);
        $centinelClient->add("MerchantId", $this->centinel_merchant_id);
        $centinelClient->add("TransactionPwd", $this->centinel_transaction_password);
        $centinelClient->add("UserAgent", 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:48.0) Gecko/20100101 Firefox/48.0');
        $centinelClient->add("BrowserHeader", 'HTTP_ACCEPT=text/xml,application/xml');
        $centinelClient->add("TransactionType", 'C'); // C - Payer Authentication
        $centinelClient->add('IPAddress', $_SERVER['REMOTE_ADDR']);

        // Standard cmpi_lookup fields
        $centinelClient->add('OrderNumber', $data['trackingMemberCode']);
        $centinelClient->add('Amount', str_replace('.','',$data['amount']));
        $centinelClient->add('CurrencyCode', 'USD');
        $centinelClient->add('OrderChannel', '1CLICK');
        $centinelClient->add('TransactionMode','S');


        // Payer Authentication specific fields
        $centinelClient->add('CardNumber', $data['creditCard']['cardNumber']);
        $centinelClient->add('CardExpMonth', $data['creditCard']['cardExpiryMonth']);
        $centinelClient->add('CardExpYear', $data['creditCard']['cardExpiryYear']);
        $centinelClient->add('Password', 'ahm671et');


        /**********************************************************************************/
        /*                                                                                */
        /*Send the XML Msg to the MAPS Server, the Response is the CentinelResponse Object*/
        /*                                                                                */
        /**********************************************************************************/

        $centinelClient->sendHttp($this->centinel_maps_url, $this->centinel_timeout_connect, $this->centinel_timeout_read);

        $centienl_response = array(
            'Centinel_Enrolled'=>$centinelClient->getValue("Enrolled"),
            'Centinel_TransactionId'=>$centinelClient->getValue("TransactionId"),
            'Centinel_OrderId'=>$centinelClient->getValue("OrderId"),
            'Centinel_ACSUrl'=>$centinelClient->getValue("ACSUrl"),
            'Centinel_Payload'=>$centinelClient->getValue("Payload"),
            'Centinel_ErrorNo'=>$centinelClient->getValue("ErrorNo"),
            'Centinel_ErrorDesc'=>$centinelClient->getValue("ErrorDesc"),
            'Centinel_ErrorDesc'=>$centinelClient->getValue("ErrorDesc"),

        );

        /******************************************************************************/
        /*                                                                            */
        /*                          Result Processing Logic                           */
        /*                                                                            */
        /******************************************************************************/

        return $centienl_response;


    }

    public function MPIAuthenticate($PARes,$lookup_data){

        if (strcasecmp('', $PARes )!= 0 && $PARes != null) {

            $centinelClient = new CentinelClient;

            $centinelClient->add('MsgType', 'cmpi_authenticate');
            $centinelClient->add("Version", $this->centinel_msg_version);
            $centinelClient->add("ProcessorId", $this->centinel_processor_id);
            $centinelClient->add("MerchantId", $this->centinel_merchant_id);
            $centinelClient->add("TransactionPwd", $this->centinel_transaction_password);
            $centinelClient->add('TransactionType', 'C');
            $centinelClient->add('OrderId', $lookup_data['enrollmentTrackingMemberCode']);
            $centinelClient->add('TransactionId', $lookup_data['Centinel_TransactionId']);
            $centinelClient->add('PAResPayload',$PARes);

            $centinelClient->add('Password','ahm671et');

            $centinelClient->sendHttp($this->centinel_maps_url, $this->centinel_timeout_connect, $this->centinel_timeout_read);

            $centienl_response = array(
                'Centinel_cmpiMessageResp'=>$centinelClient->response,
                'Centinel_PAResStatus'=>$centinelClient->getValue("Centinel_PAResStatus"),
                'Centinel_SignatureVerification'=>$centinelClient->getValue("Centinel_SignatureVerification"),
                'Centinel_ErrorNo'=>$centinelClient->getValue("Centinel_ErrorNo"),
                'Centinel_ErrorDesc'=>$centinelClient->getValue("Centinel_ErrorDesc"),

            );
        }
        else {
            $centienl_response = array(
                'Centinel_ErrorNo'=>0,
                'Centinel_ErrorDesc'=>'NO PARES RETURNED',

            );
        }

        return $centienl_response;
    }
}


/* End of file Cardinal.php */ 