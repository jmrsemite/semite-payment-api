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

/*
 * Usage;
 *         $this->load->library('processors/payvision');
        $Payvision = new Payvision();

        $Payvision->Authorize();
 */

require(PROCESSORS . 'Payvision/test/payvision_autoload.php');

class Payvision {

    public function __construct(){

        $this->ci =& get_instance();
    }

    public function Charge($merchant,$clientData,$merchantProcessor,$creditCard,$amount,$baseCurrency,$trackingCode,$params){

        $processor_data = json_decode($merchantProcessor->processor_data);

        try
        {
            if ($merchant->row()->live_mode){
                $client = new Payvision_Client(Payvision_Client::ENV_LIVE);
            } else {
                $client = new Payvision_Client(Payvision_Client::ENV_TEST);
            }

            $payment = new Payvision_BasicOperations_Payment();
            $payment->setMember($processor_data->memberId, $processor_data->memberGuid);
            $payment->setCountryId($params['countryId']);

            $payment->setCardNumberAndHolder($creditCard['cardNumber']);
            $payment->setCardExpiry($creditCard['cardExpiryMonth'], $creditCard['cardExpiryYear']);
            $payment->setCardValidationCode($creditCard['cardCvv']);

            if (isset($params['dbaName']) && !empty($params['dbaName'])){

                $descriptor = $params['dbaName'].'|';

            }

            if (isset($params['dbaCity']) && !empty($params['dbaCity'])){

                $descriptor .= $params['dbaCity'];
            }

            if (!empty($descriptor)){

                $payment->setDynamicDescriptor($descriptor);
            }

            $payment->setAmountAndCurrencyId(money_format("%!^i", $amount), Payvision_Translator::getCurrencyIdFromIsoCode($baseCurrency));

            $payment->setTrackingMemberCode($trackingCode);

            $client->call($payment);

            $result = array(
                'result_state'=>$payment->getResultState(),
                'result_code'=>$payment->getResultCode(),
                'result_message'=>$payment->getResultMessage(),
                'result_transaction_id'=>$payment->getResultTransactionId(),
                'result_transaction_guid'=>$payment->getResultTransactionGuid(),
                'result_transaction_date'=>$payment->getResultTransactionDateTime(),
                'result_tracking_member_code'=>$payment->getResultTrackingMemberCode(),
                'result_cdc_data'=>$payment->getResultCdcData()
            );

            if (!$payment->getResultCode()){
                $response_array = array('chargeResult' => json_encode($result));
                $response = $this->ci->response->TransactionResponse(1, $response_array);
            } else {
                $response_array = array('chargeResult' => json_encode($result),'reason' => $result['result_message']);
                $response = $this->ci->response->TransactionResponse(2, $response_array);
            }

        }
        catch (Payvision_Exception $e)
        {
            $response_array = array('reason' => $e->getMessage());
            $response = $this->ci->response->TransactionResponse(2, $response_array);
        }

        return $response;

    }
    public function Refund($merchant,$clientData,$merchantProcessor,$authorization,$amount,$baseCurrency,$trackingCode,$params){

        $processor_data = json_decode($merchantProcessor->processor_data);

        try
        {
            if ($merchant->row()->live_mode){
                $client = new Payvision_Client(Payvision_Client::ENV_LIVE);
            } else {
                $client = new Payvision_Client(Payvision_Client::ENV_TEST);
            }


            $operation = new Payvision_BasicOperations_Refund();
            $operation->setMember($processor_data->memberId, $processor_data->memberGuid);

            $operation->setTransactionIdAndGuid($authorization->authorizationid, $authorization->authorization_code);
            $operation->setAmountAndCurrencyId($amount, Payvision_Translator::getCurrencyIdFromIsoCode($baseCurrency));

            $operation->setTrackingMemberCode($trackingCode);

            $client->call($operation);

            $result = array(
                'result_state'=>$operation->getResultState(),
                'result_code'=>$operation->getResultCode(),
                'result_message'=>$operation->getResultMessage(),
                'result_transaction_id'=>$operation->getResultTransactionId(),
                'result_transaction_guid'=>$operation->getResultTransactionGuid(),
                'result_transaction_date'=>$operation->getResultTransactionDateTime(),
                'result_tracking_member_code'=>$operation->getResultTrackingMemberCode(),
                'result_cdc_data'=>$operation->getResultCdcData()
            );

            if (!$operation->getResultCode()){
                $response_array = array('refundResult' => json_encode($result));
                $response = $this->ci->response->TransactionResponse(1, $response_array);
            } else {
                $response_array = array('refundResult' => json_encode($result),'reason' => $result['result_message']);
                $response = $this->ci->response->TransactionResponse(2, $response_array);
            }
        }
        catch (Payvision_Exception $e)
        {
            $response_array = array('reason' => $e->getMessage());
            $response = $this->ci->response->TransactionResponse(2, $response_array);
        }

        return $response;

    }

    public function Authorize($merchant,$clientData,$merchantProcessor,$creditCard,$amount,$baseCurrency,$trackingCode,$params){

        $processor_data = json_decode($merchantProcessor->processor_data);

        try
        {
            if ($merchant->row()->live_mode){
                $client = new Payvision_Client(Payvision_Client::ENV_LIVE);
            } else {
                $client = new Payvision_Client(Payvision_Client::ENV_TEST);
            }

            $authorize = new Payvision_BasicOperations_Authorize();
            $authorize->setMember($processor_data->memberId, $processor_data->memberGuid);
            $authorize->setCountryId($params['countryId']);

            $authorize->setCardNumberAndHolder($creditCard['cardNumber']);
            $authorize->setCardExpiry($creditCard['cardExpiryMonth'], $creditCard['cardExpiryYear']);
            $authorize->setCardValidationCode($creditCard['cardCvv']);

            if (isset($params['dbaName']) && !empty($params['dbaName'])){

                $descriptor = $params['dbaName'].'|';

            }

            if (isset($params['dbaCity']) && !empty($params['dbaCity'])){

                $descriptor .= $params['dbaCity'];
            }

            if (!empty($descriptor)){

                $authorize->setDynamicDescriptor($descriptor);
            }

            $authorize->setAmountAndCurrencyId(money_format("%!^i", $amount), Payvision_Translator::getCurrencyIdFromIsoCode($baseCurrency));

            $authorize->setTrackingMemberCode($trackingCode);

            $client->call($authorize);

            $result = array(
                'result_state'=>$authorize->getResultState(),
                'result_code'=>$authorize->getResultCode(),
                'result_message'=>$authorize->getResultMessage(),
                'result_transaction_id'=>$authorize->getResultTransactionId(),
                'result_transaction_guid'=>$authorize->getResultTransactionGuid(),
                'result_transaction_date'=>$authorize->getResultTransactionDateTime(),
                'result_tracking_member_code'=>$authorize->getResultTrackingMemberCode(),
                'result_cdc_data'=>$authorize->getResultCdcData()
            );

            if (!$authorize->getResultCode()){
                $response_array = array('authorizeResult' => json_encode($result));
                $response = $this->ci->response->TransactionResponse(1, $response_array);
            } else {
                $response_array = array('authorizeResult' => json_encode($result),'reason' => $result['result_message']);
                $response = $this->ci->response->TransactionResponse(2, $response_array);
            }

        }
        catch (Payvision_Exception $e)
        {
            $response_array = array('reason' => $e->getMessage());
            $response = $this->ci->response->TransactionResponse(2, $response_array);
        }

        return $response;

    }

    public function Capture($merchant,$clientData,$merchantProcessor,$authorization,$amount,$baseCurrency,$trackingCode,$params){

        $processor_data = json_decode($merchantProcessor->processor_data);

        try
        {
            if ($merchant->row()->live_mode){
                $client = new Payvision_Client(Payvision_Client::ENV_LIVE);
            } else {
                $client = new Payvision_Client(Payvision_Client::ENV_TEST);
            }


            $operation = new Payvision_BasicOperations_Capture();
            $operation->setMember($processor_data->memberId, $processor_data->memberGuid);

            $operation->setTransactionIdAndGuid($authorization->authorizationid, $authorization->authorization_code);
            $operation->setAmountAndCurrencyId($amount, Payvision_Translator::getCurrencyIdFromIsoCode($baseCurrency));

            $operation->setTrackingMemberCode($trackingCode);

            $client->call($operation);

            $result = array(
                'result_state'=>$operation->getResultState(),
                'result_code'=>$operation->getResultCode(),
                'result_message'=>$operation->getResultMessage(),
                'result_transaction_id'=>$operation->getResultTransactionId(),
                'result_transaction_guid'=>$operation->getResultTransactionGuid(),
                'result_transaction_date'=>$operation->getResultTransactionDateTime(),
                'result_tracking_member_code'=>$operation->getResultTrackingMemberCode(),
                'result_cdc_data'=>$operation->getResultCdcData()
            );

            if (!$operation->getResultCode()){
                $response_array = array('captureResult' => json_encode($result));
                $response = $this->ci->response->TransactionResponse(1, $response_array);
            } else {
                $response_array = array('captureResult' => json_encode($result),'reason' => $result['result_message']);
                $response = $this->ci->response->TransactionResponse(2, $response_array);
            }
        }
        catch (Payvision_Exception $e)
        {
            $response_array = array('reason' => $e->getMessage());
            $response = $this->ci->response->TransactionResponse(2, $response_array);
        }

        return $response;

    }

    public function Void($merchant,$clientData,$merchantProcessor,$authorization,$amount,$baseCurrency,$trackingCode,$params){

        $processor_data = json_decode($merchantProcessor->processor_data);

        try
        {
            if ($merchant->row()->live_mode){
                $client = new Payvision_Client(Payvision_Client::ENV_LIVE);
            } else {
                $client = new Payvision_Client(Payvision_Client::ENV_TEST);
            }


            $operation = new Payvision_BasicOperations_Void();
            $operation->setMember($processor_data->memberId, $processor_data->memberGuid);

            $operation->setTransactionIdAndGuid($authorization->authorizationid, $authorization->authorization_code);
            $operation->setAmountAndCurrencyId($amount, Payvision_Translator::getCurrencyIdFromIsoCode($baseCurrency));

            $operation->setTrackingMemberCode($trackingCode);

            $client->call($operation);

            $result = array(
                'result_state'=>$operation->getResultState(),
                'result_code'=>$operation->getResultCode(),
                'result_message'=>$operation->getResultMessage(),
                'result_transaction_id'=>$operation->getResultTransactionId(),
                'result_transaction_guid'=>$operation->getResultTransactionGuid(),
                'result_transaction_date'=>$operation->getResultTransactionDateTime(),
                'result_tracking_member_code'=>$operation->getResultTrackingMemberCode(),
                'result_cdc_data'=>$operation->getResultCdcData()
            );

            if (!$operation->getResultCode()){
                $response_array = array('voidResult' => json_encode($result));
                $response = $this->ci->response->TransactionResponse(1, $response_array);
            } else {
                $response_array = array('voidResult' => json_encode($result),'reason' => $result['result_message']);
                $response = $this->ci->response->TransactionResponse(2, $response_array);
            }
        }
        catch (Payvision_Exception $e)
        {
            $response_array = array('reason' => $e->getMessage());
            $response = $this->ci->response->TransactionResponse(2, $response_array);
        }

        return $response;

    }

    public function Payment($merchant,$clientData,$merchantProcessor,$creditCard,$amount,$baseCurrency,$trackingCode,$params){

        $processor_data = json_decode($merchantProcessor->processor_data);

        try
        {
            if ($merchant->row()->live_mode){
                $client = new Payvision_Client(Payvision_Client::ENV_LIVE);
            } else {
                $client = new Payvision_Client(Payvision_Client::ENV_TEST);
            }

            $payment = new Payvision_BasicOperations_Payment();
            $payment->setMember($processor_data->memberId, $processor_data->memberGuid);
            $payment->setCountryId($params['countryId']);

            $payment->setCardNumberAndHolder($creditCard['cardNumber']);
            $payment->setCardExpiry($creditCard['cardExpiryMonth'], $creditCard['cardExpiryYear']);
            $payment->setCardValidationCode($creditCard['cardCvv']);

            $payment->setXid($params['xid']);

            if (isset($params['dbaName']) && !empty($params['dbaName'])){

                $descriptor = $params['dbaName'].'|';

            }

            if (isset($params['dbaCity']) && !empty($params['dbaCity'])){

                $descriptor .= $params['dbaCity'];
            }

            if (!empty($descriptor)){

                $payment->setDynamicDescriptor($descriptor);
            }

            $payment->setAmountAndCurrencyId(money_format("%!^i", $amount), Payvision_Translator::getCurrencyIdFromIsoCode($baseCurrency));

            $payment->setTrackingMemberCode($trackingCode);

            $client->call($payment);

            $result = array(
                'result_state'=>$payment->getResultState(),
                'result_code'=>$payment->getResultCode(),
                'result_message'=>$payment->getResultMessage(),
                'result_transaction_id'=>$payment->getResultTransactionId(),
                'result_transaction_guid'=>$payment->getResultTransactionGuid(),
                'result_transaction_date'=>$payment->getResultTransactionDateTime(),
                'result_tracking_member_code'=>$payment->getResultTrackingMemberCode(),
                'result_cdc_data'=>$payment->getResultCdcData()
            );

            if (!$payment->getResultCode()){
                $response_array = array('chargeResult' => json_encode($result));
                $response = $this->ci->response->TransactionResponse(1, $response_array);
            } else {
                $response_array = array('chargeResult' => json_encode($result),'reason' => $result['result_message']);
                $response = $this->ci->response->TransactionResponse(2, $response_array);
            }

        }
        catch (Payvision_Exception $e)
        {
            $response_array = array('reason' => $e->getMessage());
            $response = $this->ci->response->TransactionResponse(2, $response_array);
        }

        return $response;

    }
}


/* End of file Payvision.php */ 