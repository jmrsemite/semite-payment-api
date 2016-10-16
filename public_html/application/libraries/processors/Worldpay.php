<?php

/**
 * Created by PhpStorm.
 * User: root
 * Date: 10/15/16
 * Time: 4:43 PM
 */
require(PROCESSORS . 'Worldpay/init.php');

class Worldpay
{
    public function __construct()
    {
        $this->ci =& get_instance();
        $this->cc_validator = new CreditCardValidator();
    }

    public function Charge($merchant,$clientData,$merchantProcessor,$creditCard,$amount,$baseCurrency,$trackingCode,$params){


        $processor_data = json_decode($merchantProcessor->processor_data);

        $worldpay = new WorldpayAPI($processor_data->serviceKey);

        $result = array();
        try {
            $response = $worldpay->createOrder(array(
                'paymentMethod'=>array(
                    'type'=>'Card',
                    'name'=>(isset($creditCard['cardholder']) ? $creditCard['cardholder'] : null),
                    'expiryMonth'=>$creditCard['cardExpiryMonth'],
                    'expiryYear'=>$creditCard['cardExpiryYear'],
                    'cardNumber'=>$creditCard['cardNumber'],
                    'cvc'=>$creditCard['cardCvv'],
                    'issuer'=>'1'
                ),
                'name'=>(isset($creditCard['cardholder']) ? $creditCard['cardholder'] : null),
                'orderType'=>'ECOM',
                'orderDescription'=>$trackingCode,
                'amount'=>$amount * 100,
                'currencyCode'=>Translator::getCurrencyIdFromIsoCode($params['currencyId'],true)
            ));

            $objDateTime = new DateTime('NOW');

            if ($response['paymentStatus'] === 'SUCCESS') {
                $worldpayOrderCode = $response['orderCode'];


                $result = array(
                    'result_state'=>1,
                    'result_code'=>1,
                    'result_message'=>$response['paymentStatus'],
                    'result_transaction_id'=>$response['orderCode'],
                    'result_transaction_guid'=>$response['token'],
                    'result_transaction_date'=>$objDateTime->format(DateTime::ISO8601),
                    'result_tracking_member_code'=>$response['orderDescription'],
                    'result_cdc_data'=>$response['resultCodes']
                );

                $response_array = array('chargeResult' => json_encode($result));
                $response = $this->ci->response->TransactionResponse(1, $response_array);

            } else {

                $response_array = array('chargeResult' => json_encode($result),'reason' => $result['result_message']);
                $response = $this->ci->response->TransactionResponse(2, $response_array);
            }
        } catch (WorldpayException $e) {
            $response_array = array('reason' => $e->getMessage());
            $response = $this->ci->response->TransactionResponse(2, $response_array);
        } catch (Exception $e) {
            $response_array = array('reason' => $e->getMessage());
            $response = $this->ci->response->TransactionResponse(2, $response_array);
        }

        return $response;
    }

    public function Authorize($merchant,$clientData,$merchantProcessor,$creditCard,$amount,$baseCurrency,$trackingCode,$params){


        $processor_data = json_decode($merchantProcessor->processor_data);

        $worldpay = new WorldpayAPI($processor_data->serviceKey);

        $result = array();
        try {
            $res = $worldpay->createOrder(array(
                'paymentMethod'=>array(
                    'type'=>'Card',
                    'name'=>(isset($creditCard['cardholder']) ? $creditCard['cardholder'] : null),
                    'expiryMonth'=>$creditCard['cardExpiryMonth'],
                    'expiryYear'=>$creditCard['cardExpiryYear'],
                    'cardNumber'=>$creditCard['cardNumber'],
                    'cvc'=>$creditCard['cardCvv'],
                    'issuer'=>'1',
                ),
                'name'=>(isset($creditCard['cardholder']) ? $creditCard['cardholder'] : null),
                'orderType'=>'ECOM',
                'orderDescription'=>$trackingCode,
                'amount'=>$amount * 100,
                'currencyCode'=>Translator::getCurrencyIdFromIsoCode($params['currencyId'],true),
                'authorizeOnly' => true
            ));

            $objDateTime = new DateTime('NOW');


            if ($res['paymentStatus'] === 'AUTHORIZED') {

                $worldpayOrderCode = $res['orderCode'];

                $result = array(
                    'result_state'=>0,
                    'result_code'=>1,
                    'result_message'=>$res['paymentStatus'],
                    'result_transaction_id'=>$res['orderCode'],
                    'result_transaction_guid'=>$res['token'],
                    'result_transaction_date'=>$objDateTime->format(DateTime::ISO8601),
                    'result_tracking_member_code'=>$res['orderDescription'],
                    'result_cdc_data'=>$res['resultCodes']
                );

                $response_array = array('authorizeResult' => json_encode($result));
                $response = $this->ci->response->TransactionResponse(1, $response_array);

            } else {

                    $result = array(
                        'result_state'=>1,
                        'result_code'=>2,
                        'result_message'=>$res['paymentStatus'],
                        'result_transaction_id'=>$res['orderCode'],
                        'result_transaction_guid'=>$res['token'],
                        'result_transaction_date'=>$objDateTime->format(DateTime::ISO8601),
                        'result_tracking_member_code'=>$res['orderDescription'],
                        'result_cdc_data'=>$res['resultCodes']
                    );

                $response_array = array('authorizeResult' => json_encode($result),'reason' => $result['result_message']);
                $response = $this->ci->response->TransactionResponse(2, $response_array);
            }
        } catch (WorldpayException $e) {

            $response_array = array('reason' => $e->getMessage());
            $response = $this->ci->response->TransactionResponse(2, $response_array);

        } catch (Exception $e) {

            $response_array = array('reason' => $e->getMessage());
            $response = $this->ci->response->TransactionResponse(2, $response_array);
        }


        return $response;
    }

    public function Payment($merchant,$clientData,$merchantProcessor,$creditCard,$amount,$baseCurrency,$trackingCode,$params){


        $processor_data = json_decode($merchantProcessor->processor_data);

        $worldpay = new WorldpayAPI($processor_data->serviceKey);

        $result = array();
        try {
            $response = $worldpay->createOrder(array(
                'paymentMethod'=>array(
                    'type'=>'Card',
                    'name'=>(isset($creditCard['cardholder']) ? $creditCard['cardholder'] : null),
                    'expiryMonth'=>$creditCard['cardExpiryMonth'],
                    'expiryYear'=>$creditCard['cardExpiryYear'],
                    'cardNumber'=>$creditCard['cardNumber'],
                    'cvc'=>$creditCard['cardCvv'],
                    'issuer'=>'1'
                ),
                'name'=>(isset($creditCard['cardholder']) ? $creditCard['cardholder'] : null),
                'orderType'=>'ECOM',
                'orderDescription'=>$trackingCode,
                'amount'=>$amount * 100,
                'currencyCode'=>Translator::getCurrencyIdFromIsoCode($params['currencyId'],true),
            ));

            $objDateTime = new DateTime('NOW');

            if ($response['paymentStatus'] === 'SUCCESS') {
                $worldpayOrderCode = $response['orderCode'];

                $result = array(
                    'result_state'=>1,
                    'result_code'=>1,
                    'result_message'=>$response['paymentStatus'],
                    'result_transaction_id'=>$response['orderCode'],
                    'result_transaction_guid'=>$response['token'],
                    'result_transaction_date'=>$objDateTime->format(DateTime::ISO8601),
                    'result_tracking_member_code'=>$response['orderDescription'],
                    'result_cdc_data'=>$response['resultCodes']
                );

                $response_array = array('chargeResult' => json_encode($result));
                $response = $this->ci->response->TransactionResponse(1, $response_array);


            } else {

                $response_array = array('chargeResult' => json_encode($result),'reason' => $result['result_message']);
                $response = $this->ci->response->TransactionResponse(2, $response_array);
            }
        } catch (WorldpayException $e) {
            $response_array = array('reason' => $e->getMessage());
            $response = $this->ci->response->TransactionResponse(2, $response_array);
        } catch (Exception $e) {
            $response_array = array('reason' => $e->getMessage());
            $response = $this->ci->response->TransactionResponse(2, $response_array);
        }



        return $response;
    }

    public function Refund($merchant,$clientData,$merchantProcessor,$authorization,$amount,$baseCurrency,$trackingCode,$params){

        $processor_data = json_decode($merchantProcessor->processor_data);

        $worldpay = new WorldpayAPI($processor_data->serviceKey);

        try {

            $response = $worldpay->refundOrder($authorization->authorizationid);

            $objDateTime = new DateTime('NOW');


                $worldpayOrderCode = $response['orderCode'];

                $result = array(
                    'result_state'=>0,
                    'result_code'=>1,
                    'result_message'=>$response['paymentStatus'],
                    'result_transaction_id'=>$response['orderCode'],
                    'result_transaction_guid'=>$response['token'],
                    'result_transaction_date'=>$objDateTime->format(DateTime::ISO8601),
                    'result_tracking_member_code'=>$response['orderDescription'],
                    'result_cdc_data'=>$response['resultCodes']
                );

                $response_array = array('refundResult' => json_encode($result));
                $response = $this->ci->response->TransactionResponse(1, $response_array);

        } catch (WorldpayException $e) {
            $response_array = array('reason' => $e->getMessage());
            $response = $this->ci->response->TransactionResponse(2, $response_array);
        } catch (Exception $e) {
            $response_array = array('reason' => $e->getMessage());
            $response = $this->ci->response->TransactionResponse(2, $response_array);
        }

        return $response;

    }

    public function Capture($merchant,$clientData,$merchantProcessor,$authorization,$amount,$baseCurrency,$trackingCode,$params){

        $processor_data = json_decode($merchantProcessor->processor_data);

        $worldpay = new WorldpayAPI($processor_data->serviceKey);

        try {

            $response = $worldpay->captureOrder($authorization->authorizationid,$amount * 100);

            $objDateTime = new DateTime('NOW');


            $worldpayOrderCode = $response['orderCode'];

            $result = array(
                'result_state'=>0,
                'result_code'=>1,
                'result_message'=>$response['paymentStatus'],
                'result_transaction_id'=>$response['orderCode'],
                'result_transaction_guid'=>$response['token'],
                'result_transaction_date'=>$objDateTime->format(DateTime::ISO8601),
                'result_tracking_member_code'=>$response['orderDescription'],
                'result_cdc_data'=>$response['resultCodes']
            );

            $response_array = array('captureResult' => json_encode($result));
            $response = $this->ci->response->TransactionResponse(1, $response_array);

        } catch (WorldpayException $e) {
            $response_array = array('reason' => $e->getMessage());
            $response = $this->ci->response->TransactionResponse(2, $response_array);
        } catch (Exception $e) {
            $response_array = array('reason' => $e->getMessage());
            $response = $this->ci->response->TransactionResponse(2, $response_array);
        }

        return $response;

    }

    public function Void($merchant,$clientData,$merchantProcessor,$authorization,$amount,$baseCurrency,$trackingCode,$params){

        $processor_data = json_decode($merchantProcessor->processor_data);

        $worldpay = new WorldpayAPI($processor_data->serviceKey);

        try {

            $response = $worldpay->voidOrder($authorization->authorizationid);

            $objDateTime = new DateTime('NOW');


            $worldpayOrderCode = $response['orderCode'];

            $result = array(
                'result_state'=>0,
                'result_code'=>1,
                'result_message'=>$response['paymentStatus'],
                'result_transaction_id'=>$response['orderCode'],
                'result_transaction_guid'=>$response['token'],
                'result_transaction_date'=>$objDateTime->format(DateTime::ISO8601),
                'result_tracking_member_code'=>$response['orderDescription'],
                'result_cdc_data'=>$response['resultCodes']
            );

            $response_array = array('voidResult' => json_encode($result));
            $response = $this->ci->response->TransactionResponse(1, $response_array);

        } catch (WorldpayException $e) {
            $response_array = array('reason' => $e->getMessage());
            $response = $this->ci->response->TransactionResponse(2, $response_array);
        } catch (Exception $e) {
            $response_array = array('reason' => $e->getMessage());
            $response = $this->ci->response->TransactionResponse(2, $response_array);
        }

        return $response;

    }
}