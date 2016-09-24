<?php
/**
 * Created by PhpStorm.
 * User: smt2016
 * Date: 16.9.16.
 * Time: 19.33
 */

class Rest_model extends CI_Model
{

    function __construct()
    {

        parent::__construct();

        $this->cc_validator = new CreditCardValidator();

        $this->load->model('currencies_model');
        $this->load->library('currency','currency');

    }

    /**
     * Authenticate the client.
     *
     * Authenticates the client using the api_id and secret_key.  Will only authenticate if the Client is not suspended
     * or deleted.  Returns object containg client details on success or FALSE on authentication failure.
     *
     * @param string $api_id The API identifier used by the client
     * @param string $secret_key
     *
     * @return mixed Object containg all client details on success of FALSE on failure.
     */

    function Authenticate($api_id = '', $secret_key = '')
    {
        // pull the client from the db
        $this->db->where('api_id', (string)$api_id);
        $this->db->limit(1);
        $query = $this->db->get('tblmerchants');


        // make sure it's a valid API ID
        if ($query->num_rows() === 0) {
            return FALSE;
        } // make sure the secret key matches
        else {

            $row = $query->row();

            $this->db->where('userid',$row->userid);
            $this->db->where('active',1);
            $client = $this->db->get('tblclients');

            if ($client->num_rows() === 1 && $secret_key == $row->secret_key) {
                return $client->row();
            } else {
                return FALSE;
            }
        }
    }

    public function Charge($merchant, $client,$merchantProcessor, $originalAmount,$amount, $creditCard = array(),$params=null){

        /*
         * Get clients processor and validate if this processor
         * belongs to the client
         * so we do not charge someone else
        */
        if (!$merchantProcessor || !is_numeric($params['processor'])) {
            die($this->response->Error(2008));
        }

        // is gateway enabled?
        if (!$merchantProcessor || !$merchantProcessor->status) {
            die($this->response->Error(5017));
        }

        // load the gateway
        $this->db->where('processor_id', $merchantProcessor->processor_id);

        $mainProcessor =  $this->db->get('tblgatewayprocessors')->row();
        $this->load->library('processors/'.$mainProcessor->object_name);
        $objName = ucfirst($mainProcessor->object_name);

        $processorObj = new $objName();

        // validate credit card
        if (!$creditCard) {
            die($this->response->Error(1004));
        }

        if (empty($creditCard['cardNumber'])){
            die($this->response->Error(5008));
        }

        $this->cc_validator->Validate($creditCard['cardNumber']);
        $card_info = $this->cc_validator->GetCardInfo();

        // Check the validity of cc provided
        if ($card_info['status'] != 'valid'){
            die($this->response->Error(5008));
        }

        $processorData = json_decode($merchantProcessor->processor_data);

        /*
         * Add Processor restrictions here!
         */

        $supported_cards = array();
        if (isset($processorData->visa)){
            $supported_cards[] = 'visa';
        }
        if (isset($processorData->mastercard)){
            $supported_cards[] = 'mastercard';
        }
        if (isset($processorData->discover)){
            $supported_cards[] = 'discover';
        }
        if (isset($processorData->amex)){
            $supported_cards[] = 'amex';
        }

        // Merchant card types;
        if (!in_array($card_info['type'],$supported_cards)){
            die($this->response->Error(5029));
        }


        if (!isset($creditCard['cardExpiryMonth']) || empty($creditCard['cardExpiryMonth'])) {
            die($this->response->Error(5008));
        }

        if (!isset($creditCard['cardExpiryYear']) || empty($creditCard['cardExpiryYear'])) {
            die($this->response->Error(5008));
        }

        if (strlen($creditCard['cardExpiryYear']) < 4) {
            die($this->response->Error(5026));
        }

        if (strlen($creditCard['cardExpiryMonth']) < 2) {
            die($this->response->Error(5026));
        }

        if ($creditCard['cardExpiryYear'].'-'.$creditCard['cardExpiryMonth'] < date('Y-m')) {
            die($this->response->Error(5026));
        }

        // generate tracking code if not exit
        $trackingCode = (isset($params['trackingMemberCode']) && !empty($params['trackingMemberCode']) ? $params['trackingMemberCode'] : $params['type'].' ' . date('His dmY'));

        // if amount is greater than $0, we require a gateway
        if ($amount > 0) {
            // make the charge
            $clientBaseCurrency = $this->currency->getNameById($client->default_currency);

            $response = $processorObj->$params['type']($merchant,$client,$merchantProcessor,$creditCard,$amount,$clientBaseCurrency,$trackingCode,$params);
        }

        $transactionResponse = json_decode($response['chargeResult']);

        unset($response['chargeResult']);

        $token = token(9);
        $tokenize = new Encryption($token);

        $transactionData = array(
            'fraud_trx_id'=>$params['fraud_trx_id'],
            'fraud_score'=>$params['fraud_score'],
            'descriptor'=>$merchant->row()->descriptor,
            'refid'=>null,
            'userid'=>(int)$client->userid,
            'merchantProcessorId'=>(int)$merchantProcessor->merchant_processor_id,
            'processorId'=>(int)$merchantProcessor->processor_id,
            'cardMask'=>$card_info['substring'],
            'cardType'=>$card_info['type'],
            'amount'=>(float)money_format("%!^i", $originalAmount),
            'processedAmount'=>(float)money_format("%!^i", $amount),
            'conversionRate'=>(float)$this->currencies_model->refresh(Translator::getCurrencyIdFromIsoCode($params['currencyId'],true),$this->currency->getNameById($client->default_currency)),
            'ip'=>$_SERVER['REMOTE_ADDR'],
            'type'=>$params['type'],
            'trackingCode'=>$trackingCode,
            'currencyId'=>$params['currencyId'],
            'countryId'=>$params['countryId'],
            'dbaName'=>(isset($params['dbaName']) ? $params['dbaName'] : null),
            'dbaCity'=>(isset($params['dbaCity']) ? $params['dbaCity'] : null),
            'avsAddress'=>(isset($params['avsAddress']) ? $params['avsAddress'] : null),
            'avsZip'=>(isset($params['avsZip']) ? $params['avsZip'] : null),
            'token'=>$token,
            'charged'=>1,
            'authorized'=>0,
            'refunded'=>0,
            'voided'=>0,
            'captured'=>0,
            'retrived'=>0,
            'enrolled'=>(isset($params['xid']) && !empty($params['xid']) ? 1 : 0),
            'xid'=>(isset($params['xid']) && !empty($params['xid']) ? $params['xid'] : null),
            'additionalInfo'=>(isset($params['additionalInfo']) ? $params['additionalInfo'] : null),
            'acquirerCommission' => get_br_commission_amount($amount,$merchantProcessor->buyRate),
            'processorCommission' => get_sr_commission_amount($amount,$merchantProcessor->buyRate,$merchantProcessor->saleRate),
            'rollbackAmount'=>get_rollback_amount($amount,$merchantProcessor->saleRate,$merchantProcessor->buyRate,$merchantProcessor->rollbackReserve),
            'status'=>$response['response_code'],
            'date_added'=>date('Y-m-d H:s:i')
        );

        $this->db->insert('tbltransactions',$transactionData);
        $transaction_id = $this->db->insert_id();

        $authorizationData = array(
            'transactionid'=>$transaction_id,
            'authorizationid'=>(isset($transactionResponse->result_transaction_id) ? $transactionResponse->result_transaction_id : null),
            'authorization_code'=>(isset($transactionResponse->result_transaction_guid) ? $transactionResponse->result_transaction_guid : null),
            'approval_code'=>(isset($transactionResponse->result_cdc_data->BankApprovalCode) ? $transactionResponse->result_cdc_data->BankApprovalCode : 0),
            'response'=>json_encode($transactionResponse),
            'tokenize'=>$tokenize->encrypt(json_encode($creditCard))
        );

        $this->db->insert('tbltransactionauthorization',$authorizationData);
        $transaction_authorization_id = $this->db->insert_id();

        $objDateTime = new DateTime('NOW');

        $Cdc = array(

        );

        // pass back some values
        $response['type'] = $params['type'];
        $response['TrackingMemberCode'] = $transactionData['trackingCode'];
        $response['amount'] = money_format("%!^i", $originalAmount);
        $response['TransactionId']= $transaction_id;
        $response['TransactionGuid'] = $transaction_authorization_id;
        $response['TransactionDateTime'] = $objDateTime->format(DateTime::ISO8601);
        $response['Cdc'] = $Cdc;

        return $response;

    }

    public function Authorize($merchant, $client,$merchantProcessor, $originalAmount,$amount, $creditCard = array(),$params=null){

        /*
         * Get clients processor and validate if this processor
         * belongs to the client
         * so we do not charge someone else
        */
        if (!$merchantProcessor || !is_numeric($params['processor'])) {
            die($this->response->Error(2008));
        }

        // is gateway enabled?
        if (!$merchantProcessor || !$merchantProcessor->status) {
            die($this->response->Error(5017));
        }

        // load the gateway
        $this->db->where('processor_id', $merchantProcessor->processor_id);

        $mainProcessor =  $this->db->get('tblgatewayprocessors')->row();
        $this->load->library('processors/'.$mainProcessor->object_name);
        $objName = ucfirst($mainProcessor->object_name);

        $processorObj = new $objName();

        // validate credit card
        if (!$creditCard) {
            die($this->response->Error(1004));
        }

        if (empty($creditCard['cardNumber'])){
            die($this->response->Error(5008));
        }

        $this->cc_validator->Validate($creditCard['cardNumber']);
        $card_info = $this->cc_validator->GetCardInfo();

        // Check the validity of cc provided
        if ($card_info['status'] != 'valid'){
            die($this->response->Error(5008));
        }

        $processorData = json_decode($merchantProcessor->processor_data);

        /*
         * Add Processor restrictions here!
         */

        $supported_cards = array();
        if (isset($processorData->visa)){
            $supported_cards[] = 'visa';
        }
        if (isset($processorData->mastercard)){
            $supported_cards[] = 'mastercard';
        }
        if (isset($processorData->discover)){
            $supported_cards[] = 'discover';
        }
        if (isset($processorData->amex)){
            $supported_cards[] = 'amex';
        }

        // Merchant card types;
        if (!in_array($card_info['type'],$supported_cards)){
            die($this->response->Error(5029));
        }


        if (!isset($creditCard['cardExpiryMonth']) || empty($creditCard['cardExpiryMonth'])) {
            die($this->response->Error(5008));
        }

        if (!isset($creditCard['cardExpiryYear']) || empty($creditCard['cardExpiryYear'])) {
            die($this->response->Error(5008));
        }

        if (strlen($creditCard['cardExpiryYear']) < 4) {
            die($this->response->Error(5026));
        }

        if (strlen($creditCard['cardExpiryMonth']) < 2) {
            die($this->response->Error(5026));
        }

        if ($creditCard['cardExpiryYear'].'-'.$creditCard['cardExpiryMonth'] < date('Y-m')) {
            die($this->response->Error(5026));
        }

        // generate tracking code if not exit
        $trackingCode = (isset($params['trackingMemberCode']) && !empty($params['trackingMemberCode']) ? $params['trackingMemberCode'] : $params['type'].' ' . date('His dmY'));

        // if amount is greater than $0, we require a gateway
        if ($amount > 0) {
            // make the charge
            $clientBaseCurrency = $this->currency->getNameById($client->default_currency);

            $response = $processorObj->$params['type']($merchant,$client,$merchantProcessor,$creditCard,$amount,$clientBaseCurrency,$trackingCode,$params);
        }

        $transactionResponse = json_decode($response['authorizeResult']);

        unset($response['authorizeResult']);

        $token = token(9);
        $tokenize = new Encryption($token);

        $transactionData = array(
            'fraud_trx_id'=>$params['fraud_trx_id'],
            'fraud_score'=>$params['fraud_score'],
            'descriptor'=>$merchant->row()->descriptor,
            'refid'=>null,
            'userid'=>(int)$client->userid,
            'merchantProcessorId'=>(int)$merchantProcessor->merchant_processor_id,
            'processorId'=>(int)$merchantProcessor->processor_id,
            'cardMask'=>$card_info['substring'],
            'cardType'=>$card_info['type'],
            'amount'=>(float)money_format("%!^i", $originalAmount),
            'processedAmount'=>(float)money_format("%!^i", $amount),
            'conversionRate'=>(float)$this->currencies_model->refresh(Translator::getCurrencyIdFromIsoCode($params['currencyId'],true),$this->currency->getNameById($client->default_currency)),
            'ip'=>$_SERVER['REMOTE_ADDR'],
            'type'=>$params['type'],
            'trackingCode'=>$trackingCode,
            'currencyId'=>$params['currencyId'],
            'countryId'=>$params['countryId'],
            'dbaName'=>(isset($params['dbaName']) ? $params['dbaName'] : null),
            'dbaCity'=>(isset($params['dbaCity']) ? $params['dbaCity'] : null),
            'avsAddress'=>(isset($params['avsAddress']) ? $params['avsAddress'] : null),
            'avsZip'=>(isset($params['avsZip']) ? $params['avsZip'] : null),
            'token'=>$token,
            'charged'=>0,
            'authorized'=>1,
            'refunded'=>0,
            'voided'=>0,
            'captured'=>0,
            'retrived'=>0,
            'enrolled'=>(isset($params['xid']) && !empty($params['xid']) ? 1 : 0),
            'xid'=>(isset($params['xid']) && !empty($params['xid']) ? $params['xid'] : null),
            'additionalInfo'=>(isset($params['additionalInfo']) ? $params['additionalInfo'] : null),
            'acquirerCommission' => get_br_commission_amount($amount,$merchantProcessor->buyRate),
            'processorCommission' => get_sr_commission_amount($amount,$merchantProcessor->buyRate,$merchantProcessor->saleRate),
            'rollbackAmount'=>get_rollback_amount($amount,$merchantProcessor->saleRate,$merchantProcessor->buyRate,$merchantProcessor->rollbackReserve),
            'status'=>$response['response_code'],
            'date_added'=>date('Y-m-d H:s:i')
        );

        $this->db->insert('tbltransactions',$transactionData);
        $transaction_id = $this->db->insert_id();

        $authorizationData = array(
            'transactionid'=>$transaction_id,
            'authorizationid'=>(isset($transactionResponse->result_transaction_id) ? $transactionResponse->result_transaction_id : null),
            'authorization_code'=>(isset($transactionResponse->result_transaction_guid) ? $transactionResponse->result_transaction_guid : null),
            'approval_code'=>(isset($transactionResponse->result_cdc_data->BankApprovalCode) ? $transactionResponse->result_cdc_data->BankApprovalCode : 0),
            'response'=>json_encode($transactionResponse),
            'tokenize'=>$tokenize->encrypt(json_encode($creditCard))
        );

        $this->db->insert('tbltransactionauthorization',$authorizationData);
        $transaction_authorization_id = $this->db->insert_id();

        $objDateTime = new DateTime('NOW');

        $Cdc = array(

        );

        // pass back some values
        $response['type'] = $params['type'];
        $response['TrackingMemberCode'] = $transactionData['trackingCode'];
        $response['amount'] = money_format("%!^i", $originalAmount);
        $response['TransactionId']= $transaction_id;
        $response['TransactionGuid'] = $transaction_authorization_id;
        $response['TransactionDateTime'] = $objDateTime->format(DateTime::ISO8601);
        $response['Cdc'] = $Cdc;

        return $response;

    }

    public function Capture($merchant, $client,$merchantProcessor,$params=null){

        /*
         * Get clients processor and validate if this processor
         * belongs to the client
         * so we do not charge someone else
        */
        if (!$merchantProcessor || !is_numeric($params['processor'])) {
            die($this->response->Error(2008));
        }

        // is gateway enabled?
        if (!$merchantProcessor || !$merchantProcessor->status) {
            die($this->response->Error(5017));
        }

        // load the gateway
        $this->db->where('processor_id', $merchantProcessor->processor_id);

        $mainProcessor =  $this->db->get('tblgatewayprocessors')->row();
        $this->load->library('processors/'.$mainProcessor->object_name);
        $objName = ucfirst($mainProcessor->object_name);

        $processorObj = new $objName();

        // generate tracking code if not exit
        $trackingCode = (isset($params['trackingMemberCode']) && !empty($params['trackingMemberCode']) ? $params['trackingMemberCode'] : $params['type'].' ' . date('His dmY'));


        $this->db->where('transactionid',$params['transactionId']);
        $transaction = $this->db->get('tbltransactions')->row();

        if ($transaction && $transaction->captured){
            die($this->response->Error(4004));
        }

        $this->db->where('transactionid',$params['transactionId']);
        $transactionAuthorization = $this->db->get('tbltransactionauthorization')->row();

        if (!$transactionAuthorization){
            die($this->response->Error(4004));
        }

        $amount = $transaction->processedAmount;

        $clientBaseCurrency = $this->currency->getNameById($client->default_currency);

        $response = $processorObj->$params['type']($merchant,$client,$merchantProcessor,$transactionAuthorization,$amount,$clientBaseCurrency,$trackingCode,$params);


        $transactionResponse = json_decode($response['captureResult']);

        unset($response['captureResult']);

        $token = token(9);
        $tokenize = new Encryption($token);

        $transactionData = array(
            'fraud_trx_id'=>$transaction->fraud_trx_id,
            'fraud_score'=>$transaction->fraud_score,
            'descriptor'=>$transaction->descriptor,
            'refid'=>$transaction->transactionid,
            'userid'=>(int)$transaction->userid,
            'merchantProcessorId'=>(int)$transaction->merchantProcessorId,
            'processorId'=>(int)$transaction->processorid,
            'cardMask'=>$transaction->cardMask,
            'cardType'=>$transaction->cardType,
            'amount'=>(float)$transaction->amount,
            'processedAmount'=>(float)$transaction->processedAmount,
            'conversionRate'=>(float)$this->currencies_model->refresh(Translator::getCurrencyIdFromIsoCode($transaction->currencyId,true),$this->currency->getNameById($client->default_currency)),
            'ip'=>$_SERVER['REMOTE_ADDR'],
            'type'=>$params['type'],
            'trackingCode'=>$trackingCode,
            'currencyId'=>$transaction->currencyId,
            'countryId'=>$transaction->countryId,
            'dbaName'=>$transaction->dbaName,
            'dbaCity'=>$transaction->dbaCity,
            'avsAddress'=>$transaction->avsAddress,
            'avsZip'=>$transaction->avsZip,
            'token'=>$token,
            'charged'=>0,
            'authorized'=>0,
            'refunded'=>0,
            'voided'=>0,
            'captured'=>1,
            'retrived'=>0,
            'enrolled'=>(isset($params['xid']) && !empty($params['xid']) ? 1 : 0),
            'xid'=>(isset($params['xid']) && !empty($params['xid']) ? $params['xid'] : null),
            'additionalInfo'=>$transaction->additionalInfo,
            'additionalInfo'=>0,
            'acquirerCommission' =>0,
            'processorCommission' => 0,
            'rollbackAmount'=>0,

            'status'=>$response['response_code'],
            'date_added'=>date('Y-m-d H:s:i')
        );

        $this->db->insert('tbltransactions',$transactionData);
        $transaction_id = $this->db->insert_id();

        $authorizationData = array(
            'transactionid'=>$transaction_id,
            'authorizationid'=>(isset($transactionResponse->result_transaction_id) ? $transactionResponse->result_transaction_id : null),
            'authorization_code'=>(isset($transactionResponse->result_transaction_guid) ? $transactionResponse->result_transaction_guid : null),
            'approval_code'=>(isset($transactionResponse->result_cdc_data->BankApprovalCode) ? $transactionResponse->result_cdc_data->BankApprovalCode : 0),
            'response'=>json_encode($transactionResponse),
            'tokenize'=>$transactionAuthorization->tokenize
        );

        $this->db->insert('tbltransactionauthorization',$authorizationData);
        $transaction_authorization_id = $this->db->insert_id();

        $objDateTime = new DateTime('NOW');

        $Cdc = array(

        );

        // pass back some values
        $response['type'] = $params['type'];
        $response['TrackingMemberCode'] = $transactionData['trackingCode'];
        $response['amount'] = money_format("%!^i", $amount);
        $response['TransactionId']= $transaction_id;
        $response['TransactionGuid'] = $transaction_authorization_id;
        $response['TransactionDateTime'] = $objDateTime->format(DateTime::ISO8601);
        $response['Cdc'] = $Cdc;

        return $response;

    }

    public function Void($merchant, $client,$merchantProcessor,$params=null){

        /*
         * Get clients processor and validate if this processor
         * belongs to the client
         * so we do not charge someone else
        */
        if (!$merchantProcessor || !is_numeric($params['processor'])) {
            die($this->response->Error(2008));
        }

        // is gateway enabled?
        if (!$merchantProcessor || !$merchantProcessor->status) {
            die($this->response->Error(5017));
        }

        // load the gateway
        $this->db->where('processor_id', $merchantProcessor->processor_id);

        $mainProcessor =  $this->db->get('tblgatewayprocessors')->row();
        $this->load->library('processors/'.$mainProcessor->object_name);
        $objName = ucfirst($mainProcessor->object_name);

        $processorObj = new $objName();

        // generate tracking code if not exit
        $trackingCode = (isset($params['trackingMemberCode']) && !empty($params['trackingMemberCode']) ? $params['trackingMemberCode'] : $params['type'].' ' . date('His dmY'));


        $this->db->where('transactionid',$params['transactionId']);
        $transaction = $this->db->get('tbltransactions')->row();

        if ($transaction && $transaction->captured){
            die($this->response->Error(4004));
        }

        $this->db->where('transactionid',$params['transactionId']);
        $transactionAuthorization = $this->db->get('tbltransactionauthorization')->row();

        if (!$transactionAuthorization){
            die($this->response->Error(4004));
        }

        $amount = $transaction->processedAmount;

        $clientBaseCurrency = $this->currency->getNameById($client->default_currency);

        $response = $processorObj->$params['type']($merchant,$client,$merchantProcessor,$transactionAuthorization,$amount,$clientBaseCurrency,$trackingCode,$params);


        $transactionResponse = json_decode($response['voidResult']);

        unset($response['voidResult']);

        $token = token(9);
        $tokenize = new Encryption($token);

        $transactionData = array(
            'fraud_trx_id'=>$transaction->fraud_trx_id,
            'fraud_score'=>$transaction->fraud_score,
            'descriptor'=>$transaction->descriptor,
            'refid'=>$transaction->transactionid,
            'userid'=>(int)$transaction->userid,
            'merchantProcessorId'=>(int)$transaction->merchantProcessorId,
            'processorId'=>(int)$transaction->processorid,
            'cardMask'=>$transaction->cardMask,
            'cardType'=>$transaction->cardType,
            'amount'=>(float)$transaction->amount,
            'processedAmount'=>(float)$transaction->processedAmount,
            'conversionRate'=>(float)$this->currencies_model->refresh(Translator::getCurrencyIdFromIsoCode($transaction->currencyId,true),$this->currency->getNameById($client->default_currency)),
            'ip'=>$_SERVER['REMOTE_ADDR'],
            'type'=>$params['type'],
            'trackingCode'=>$trackingCode,
            'currencyId'=>$transaction->currencyId,
            'countryId'=>$transaction->countryId,
            'dbaName'=>$transaction->dbaName,
            'dbaCity'=>$transaction->dbaCity,
            'avsAddress'=>$transaction->avsAddress,
            'avsZip'=>$transaction->avsZip,
            'token'=>$token,
            'charged'=>0,
            'authorized'=>0,
            'refunded'=>0,
            'voided'=>1,
            'captured'=>0,
            'retrived'=>0,
            'enrolled'=>(isset($params['xid']) && !empty($params['xid']) ? 1 : 0),
            'xid'=>(isset($params['xid']) && !empty($params['xid']) ? $params['xid'] : null),
            'additionalInfo'=>$transaction->additionalInfo,
            'additionalInfo'=>0,
            'acquirerCommission' =>0,
            'processorCommission' => 0,
            'rollbackAmount'=>0,

            'status'=>$response['response_code'],
            'date_added'=>date('Y-m-d H:s:i')
        );

        $this->db->insert('tbltransactions',$transactionData);
        $transaction_id = $this->db->insert_id();

        $authorizationData = array(
            'transactionid'=>$transaction_id,
            'authorizationid'=>(isset($transactionResponse->result_transaction_id) ? $transactionResponse->result_transaction_id : null),
            'authorization_code'=>(isset($transactionResponse->result_transaction_guid) ? $transactionResponse->result_transaction_guid : null),
            'approval_code'=>(isset($transactionResponse->result_cdc_data->BankApprovalCode) ? $transactionResponse->result_cdc_data->BankApprovalCode : 0),
            'response'=>json_encode($transactionResponse),
            'tokenize'=>$transactionAuthorization->tokenize
        );

        $this->db->insert('tbltransactionauthorization',$authorizationData);
        $transaction_authorization_id = $this->db->insert_id();

        $objDateTime = new DateTime('NOW');

        $Cdc = array(

        );

        // pass back some values
        $response['type'] = $params['type'];
        $response['TrackingMemberCode'] = $transactionData['trackingCode'];
        $response['amount'] = money_format("%!^i", $amount);
        $response['TransactionId']= $transaction_id;
        $response['TransactionGuid'] = $transaction_authorization_id;
        $response['TransactionDateTime'] = $objDateTime->format(DateTime::ISO8601);
        $response['Cdc'] = $Cdc;

        return $response;


    }

    public function Refund($merchant, $client,$merchantProcessor,$params=null){

        /*
         * Get clients processor and validate if this processor
         * belongs to the client
         * so we do not charge someone else
        */
        if (!$merchantProcessor || !is_numeric($params['processor'])) {
            die($this->response->Error(2008));
        }

        // is gateway enabled?
        if (!$merchantProcessor || !$merchantProcessor->status) {
            die($this->response->Error(5017));
        }

        // load the gateway
        $this->db->where('processor_id', $merchantProcessor->processor_id);

        $mainProcessor =  $this->db->get('tblgatewayprocessors')->row();
        $this->load->library('processors/'.$mainProcessor->object_name);
        $objName = ucfirst($mainProcessor->object_name);

        $processorObj = new $objName();

        // generate tracking code if not exit
        $trackingCode = (isset($params['trackingMemberCode']) && !empty($params['trackingMemberCode']) ? $params['trackingMemberCode'] : $params['type'].' ' . date('His dmY'));


        $this->db->where('transactionid',$params['transactionId']);
        $transaction = $this->db->get('tbltransactions')->row();

        if ($transaction && $transaction->captured){
            die($this->response->Error(4004));
        }

        $this->db->where('transactionid',$params['transactionId']);
        $transactionAuthorization = $this->db->get('tbltransactionauthorization')->row();

        if (!$transactionAuthorization){
            die($this->response->Error(4004));
        }

        $amount = $transaction->processedAmount;

        $clientBaseCurrency = $this->currency->getNameById($client->default_currency);

        $response = $processorObj->$params['type']($merchant,$client,$merchantProcessor,$transactionAuthorization,$amount,$clientBaseCurrency,$trackingCode,$params);


        $transactionResponse = json_decode($response['refundResult']);

        unset($response['refundResult']);

        $token = token(9);
        $tokenize = new Encryption($token);

        $transactionData = array(
            'fraud_trx_id'=>$transaction->fraud_trx_id,
            'fraud_score'=>$transaction->fraud_score,
            'descriptor'=>$transaction->descriptor,
            'refid'=>$transaction->transactionid,
            'userid'=>(int)$transaction->userid,
            'merchantProcessorId'=>(int)$transaction->merchantProcessorId,
            'processorId'=>(int)$transaction->processorid,
            'cardMask'=>$transaction->cardMask,
            'cardType'=>$transaction->cardType,
            'amount'=>(float)$transaction->amount,
            'processedAmount'=>(float)$transaction->processedAmount,
            'conversionRate'=>(float)$this->currencies_model->refresh(Translator::getCurrencyIdFromIsoCode($transaction->currencyId,true),$this->currency->getNameById($client->default_currency)),
            'ip'=>$_SERVER['REMOTE_ADDR'],
            'type'=>$params['type'],
            'trackingCode'=>$trackingCode,
            'currencyId'=>$transaction->currencyId,
            'countryId'=>$transaction->countryId,
            'dbaName'=>$transaction->dbaName,
            'dbaCity'=>$transaction->dbaCity,
            'avsAddress'=>$transaction->avsAddress,
            'avsZip'=>$transaction->avsZip,
            'token'=>$token,
            'charged'=>0,
            'authorized'=>0,
            'refunded'=>1,
            'voided'=>0,
            'captured'=>0,
            'retrived'=>0,
            'enrolled'=>(isset($params['xid']) && !empty($params['xid']) ? 1 : 0),
            'xid'=>(isset($params['xid']) && !empty($params['xid']) ? $params['xid'] : null),
            'additionalInfo'=>$transaction->additionalInfo,
            'additionalInfo'=>0,
            'acquirerCommission' =>0,
            'processorCommission' => 0,
            'rollbackAmount'=>0,

            'status'=>$response['response_code'],
            'date_added'=>date('Y-m-d H:s:i')
        );

        $this->db->insert('tbltransactions',$transactionData);
        $transaction_id = $this->db->insert_id();

        $authorizationData = array(
            'transactionid'=>$transaction_id,
            'authorizationid'=>(isset($transactionResponse->result_transaction_id) ? $transactionResponse->result_transaction_id : null),
            'authorization_code'=>(isset($transactionResponse->result_transaction_guid) ? $transactionResponse->result_transaction_guid : null),
            'approval_code'=>(isset($transactionResponse->result_cdc_data->BankApprovalCode) ? $transactionResponse->result_cdc_data->BankApprovalCode : 0),
            'response'=>json_encode($transactionResponse),
            'tokenize'=>$transactionAuthorization->tokenize
        );

        $this->db->insert('tbltransactionauthorization',$authorizationData);
        $transaction_authorization_id = $this->db->insert_id();

        $objDateTime = new DateTime('NOW');

        $Cdc = array(

        );

        // pass back some values
        $response['type'] = $params['type'];
        $response['TrackingMemberCode'] = $transactionData['trackingCode'];
        $response['amount'] = money_format("%!^i", $amount);
        $response['TransactionId']= $transaction_id;
        $response['TransactionGuid'] = $transaction_authorization_id;
        $response['TransactionDateTime'] = $objDateTime->format(DateTime::ISO8601);
        $response['Cdc'] = $Cdc;

        return $response;


    }

}