<?php
/**
 * Created by PhpStorm.
 * User: smt2016
 * Date: 16.9.16.
 * Time: 14.33
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Gateway extends CI_Controller {

    public function __construct(){

        parent::__construct();
        $this->load->model('rest_model', 'rest');

        $this->load->library('rest/arraytoxml','arraytoxml');
        $this->load->library('currency','currency');
        $this->load->library('mpi/endeavour');

        $this->cc_validator = new CreditCardValidator();

        $this->response = new Response();

    }

    public function index()
    {

        // grab the request
        $request = trim(file_get_contents('php://input'));

        // Log the request - don't log the request so we don't store CC information
        //$this->log_model->LogRequest($request);

        // find out if the request is valid XML
        $xml = @simplexml_load_string($request);

        // if it is not valid XML...
        if(!$xml) {
            die($this->response->Error(1000));
        }

        // Make an array out of the XML
        $params = $this->arraytoxml->toArray($xml);

        // get the api ID and secret key
        $api_id = $params['authentication']['api_id'];
        $secret_key = $params['authentication']['secret_key'];

        // authenticate the api ID
        $client = $this->rest->Authenticate($api_id, $secret_key);

        // did they authenticate?
        if(!$client) {
            die($this->response->Error(1001));
        }

        $this->db->where('userid',$client->userid);
        $merchant = $this->db->get('tblmerchants');

        if(!$merchant) {
            die($this->response->Error(1001));
        }

        // Make sure it came from a secure connection if SSL is active
        if ($merchant->row()->live_mode) {
            if (!shapeSpace_check_https($merchant->row()->live_mode)) {
                die($this->response->Error(1010));
            }
        }

        // Get the request type
        if(!isset($params['type'])) {

            die($this->response->Error(1002));
        }

        $request_type = $params['type'];

        // Make sure the first letter is capitalized
        $request_type = ucfirst($request_type);

        // Make sure a proper format was passed
        if (isset($params['format'])) {
            $format = $params['format'];
            if(!in_array($format, array('xml', 'json', 'php'))) {
                echo $this->response->Error(1006);
                die();
            }
        } else {
            $format = 'xml';
        }

        $this->response->format = $format;

        // validate the request type
        if (!method_exists($this,$request_type)) {
            die($this->response->Error(1002));
        }

        $this->request_log($merchant,$params);

        if (isset($params['amount'])) {
            if ($params['amount'] == 0 || !is_numeric($params['amount'])) {
                die($this->response->Error(4005));
            }
        }


        $response = $this->$request_type($merchant,$client, $params);

        // handle errors that didn't just kill the code
        if ($response == FALSE) {
            die($this->response->Error(1009));
        }


        // Echo the response
        echo $this->response->FormatResponse($response);
    }


    public function Charge($merchant,$client,$params){

        //Initiate Fraud Prevention System
        $this->load->library('rest/fraudlabspro','fraudlabspro');
        $fraud = $this->fraudlabspro->_initiate($params);


        // Reject transaction if score is higher then set

        if (get_fraudlabs_value('score') < $fraud['fraud_score']){

            die($this->response->Error(9000));
        }

        $params['fraud_trx_id'] = $fraud['fraud_trx_id'];
        $params['fraud_score'] = $fraud['fraud_score'];

        // take XML params and put them in variables
        $creditCard = isset($params['creditCard']) ? $params['creditCard'] : array();
        $amount = $params['amount'];
        $processedAmount = isset($params['amount']) ? $this->currency->convert($params['amount'],Translator::getCurrencyIdFromIsoCode($params['currencyId'],true),$this->currency->getNameById($client->default_currency)) : FALSE;

        // As of version 1.984 we pass any additional parameters along
        // to the charge method for the gateway to handle.
        unset($params['creditCard'],  $params['amount'], $params['authentication']);

        // Activate merchant processor
        $this->db->where('merchant_processor_id',$params['processor']);
        $merchantProcessor = $this->db->get('tblmerchantprocessors')->row();

        // check if merchant owns this credentials
        if ($merchantProcessor->merchant_processor_id != $params['processor'] || $merchant->row()->id != $merchantProcessor->merchant_id){
            die($this->response->Error(5017));
        }

        if ($amount > $merchantProcessor->transactionLimit){
            die($this->response->Error(1002));
        }

        // Start processing request
        $response =  $this->rest->Charge($merchant, $client,$merchantProcessor, $amount,$processedAmount, $creditCard, $params);

        // Return processed result
        return $response;
    }


    public function Authorize($merchant,$client,$params){

        //Initiate Fraud Prevention System
        $this->load->library('rest/fraudlabspro','fraudlabspro');
        $fraud = $this->fraudlabspro->_initiate($params);


        // Reject transaction if score is higher then set

        if (get_fraudlabs_value('score') < $fraud['fraud_score']){

            die($this->response->Error(9000));
        }

        $params['fraud_trx_id'] = $fraud['fraud_trx_id'];
        $params['fraud_score'] = $fraud['fraud_score'];

        // take XML params and put them in variables
        $creditCard = isset($params['creditCard']) ? $params['creditCard'] : array();
        $amount = $params['amount'];
        $processedAmount = isset($params['amount']) ? $this->currency->convert($params['amount'],Translator::getCurrencyIdFromIsoCode($params['currencyId'],true),$this->currency->getNameById($client->default_currency)) : FALSE;

        // As of version 1.984 we pass any additional parameters along
        // to the charge method for the gateway to handle.
        unset($params['creditCard'],  $params['amount'], $params['authentication']);

        // Activate merchant processor
        $this->db->where('merchant_processor_id',$params['processor']);
        $merchantProcessor = $this->db->get('tblmerchantprocessors')->row();

        // check if merchant owns this credentials
        if ($merchantProcessor->merchant_processor_id != $params['processor'] || $merchant->row()->id != $merchantProcessor->merchant_id){
            die($this->response->Error(5017));
        }

        if ($amount > $merchantProcessor->transactionLimit){
            die($this->response->Error(1002));
        }

        // Start processing request
        $response =  $this->rest->Authorize($merchant, $client,$merchantProcessor, $amount,$processedAmount, $creditCard, $params);

        // Return processed result
        return $response;
    }

    public function Payment($merchant,$client,$params){

        //Initiate Fraud Prevention System
        $this->load->library('rest/fraudlabspro','fraudlabspro');
        $fraud = $this->fraudlabspro->_initiate($params);


        // Reject transaction if score is higher then set

        if (get_fraudlabs_value('score') < $fraud['fraud_score']){

            die($this->response->Error(9000));
        }

        $params['fraud_trx_id'] = $fraud['fraud_trx_id'];
        $params['fraud_score'] = $fraud['fraud_score'];

        // take XML params and put them in variables
        $creditCard = isset($params['creditCard']) ? $params['creditCard'] : array();
        $amount = $params['amount'];
        $processedAmount = isset($params['amount']) ? $this->currency->convert($params['amount'],Translator::getCurrencyIdFromIsoCode($params['currencyId'],true),$this->currency->getNameById($client->default_currency)) : FALSE;

        // As of version 1.984 we pass any additional parameters along
        // to the charge method for the gateway to handle.
        unset($params['creditCard'],  $params['amount'], $params['authentication']);

        // Activate merchant processor
        $this->db->where('merchant_processor_id',$params['processor']);
        $merchantProcessor = $this->db->get('tblmerchantprocessors')->row();

        // check if merchant owns this credentials
        if ($merchantProcessor->merchant_processor_id != $params['processor'] || $merchant->row()->id != $merchantProcessor->merchant_id){
            die($this->response->Error(5017));
        }

        if ($amount > $merchantProcessor->transactionLimit){
            die($this->response->Error(1002));
        }

        // Start processing request
        $response =  $this->rest->Charge($merchant, $client,$merchantProcessor, $amount,$processedAmount, $creditCard, $params);

        // Return processed result
        return $response;
    }


    public function Capture($merchant,$client,$params){

        // As of version 1.984 we pass any additional parameters along
        // to the charge method for the gateway to handle.
        unset($params['authentication']);

        // Activate merchant processor
        $this->db->where('merchant_processor_id',$params['processor']);
        $merchantProcessor = $this->db->get('tblmerchantprocessors')->row();

        // check if merchant owns this credentials
        if ($merchantProcessor->merchant_processor_id != $params['processor'] || $merchant->row()->id != $merchantProcessor->merchant_id){
            die($this->response->Error(5017));
        }


        $response =  $this->rest->Capture($merchant, $client,$merchantProcessor, $params);

        return $response;
    }

    public function Void($merchant,$client,$params){

        // As of version 1.984 we pass any additional parameters along
        // to the charge method for the gateway to handle.
        unset($params['authentication']);

        // Activate merchant processor
        $this->db->where('merchant_processor_id',$params['processor']);
        $merchantProcessor = $this->db->get('tblmerchantprocessors')->row();

        // check if merchant owns this credentials
        if ($merchantProcessor->merchant_processor_id != $params['processor'] || $merchant->row()->id != $merchantProcessor->merchant_id){
            die($this->response->Error(5017));
        }

        $response =  $this->rest->Void($merchant, $client,$merchantProcessor, $params);

        return $response;
    }

    public function Refund($merchant,$client,$params){

        // As of version 1.984 we pass any additional parameters along
        // to the charge method for the gateway to handle.
        unset($params['authentication']);

        // Activate merchant processor
        $this->db->where('merchant_processor_id',$params['processor']);
        $merchantProcessor = $this->db->get('tblmerchantprocessors')->row();

        // check if merchant owns this credentials
        if ($merchantProcessor->merchant_processor_id != $params['processor'] || $merchant->row()->id != $merchantProcessor->merchant_id){
            die($this->response->Error(5017));
        }

        $response =  $this->rest->Refund($merchant, $client,$merchantProcessor, $params);

        return $response;
    }


    public function Credit(){
//Cash2Card
        die($this->response->Error(1));
    }

    public function CardFundTransfer(){
//Cash2Card
        die($this->response->Error(1));
    }

    public function ReferralApproval(){
//Cash2Card
        die($this->response->Error(1));
    }

    public function AuthorizeUsingIntegratedMPI(){

        die($this->response->Error(1));
    }

    public function CheckEnrollment($merchant,$client,$params){

        $objDateTime = new DateTime('NOW');

        $Endeavour = new Endeavour();
        if ($merchant->row()->live_mode) {

            $Endeavour->setMID($merchant->row()->threeds_mid);
        }

        $lookupResponse = $Endeavour->MPILookup($params);

        return $lookupResponse;

    }

    protected function request_log($merchant,$params){

        if (isset($params['creditCard']['cardNumber'])) {
            $this->cc_validator->Validate($params['creditCard']['cardNumber']);
            $card_info = $this->cc_validator->GetCardInfo();

            $params['creditCard']['cardNumber'] = $card_info['substring'];
        }

        $requestData = array(
            'type'=>$params['type'],
            'descriptor'=>$merchant->row()->descriptor,
            'log'=>json_encode($params),
            'date_added'=>date('Y-m-d H:s:i')
        );

        $this->db->insert('tblapirequestlogs',$requestData);
    }
} 