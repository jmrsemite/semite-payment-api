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


class Oculus {

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->cc_validator = new CreditCardValidator();
    }

    public function Charge($merchant,$clientData,$merchantProcessor,$creditCard,$amount,$baseCurrency,$trackingCode,$params){

        $processor_data = json_decode($merchantProcessor->processor_data);

        if ($merchant->row()->live_mode){

            $wsdl = 'https://prod.oculusgateway.ge/api/api.asmx?WSDL';
        } else {
            $wsdl = 'https://test.oculusgateway.ge/api/api.asmx?WSDL';
        }

        $trace = true;
        $exceptions = false;

        $namespace = 'https://MyCardStorage.com/';

        //Body of the Soap Header.
        $headerbody = array(
            'UserName' => $processor_data->gateway_username,
            'Password' => $processor_data->gateway_password
        );

        //Create Soap Header.
        $header = new SOAPHeader($namespace, 'AuthHeader', $headerbody);

        $CardType = $this->cc_validator->validate($creditCard['cardNumber']);



        $xml_array['creditCardSale'] = array(
            'ServiceSecurity'=>array(
                'ServiceUserName'=>$processor_data->gateway_service_username,
                'ServicePassword'=>$processor_data->gateway_service_password,
                'MCSAccountID'=>$processor_data->gateway_account_id,
            ),
            'TokenData'=>array(
                'TokenType'=>'0',
                'CardNumber'=>$creditCard['cardNumber'],
                'CardType'=>Translator::getCardIdByIssuer($CardType),
                'ExpirationMonth'=>$creditCard['cardExpiryMonth'],
                'ExpirationYear'=>substr($creditCard['cardExpiryYear'], -2),
                'CVV'=>$creditCard['cardCvv'],
                'XID'=>(isset($params['xid']) && !empty($params['xid']) ? $params['xid'] : null),
                'CAVV'=>(isset($params['cavv']) && !empty($params['cavv']) ? $params['cavv'] : null),
            ),
            'TransactionData'=>array(
                'Amount'=>(float)$amount,
                'MCSTransactionID'=>'0',
                'GatewayID'=>'3',
                'CountryCode'=>Translator::getCountryIdFromIso($params['countryId'],true),
                'CurrencyCode'=>$baseCurrency,
                'PurchaseCardTaxAmount'=>'0',
            )
        );


        try
        {
            $client = new SoapClient($wsdl, array('trace' => $trace, 'exceptions' => $exceptions));

            //set the Headers of Soap Client.
            $client->__setSoapHeaders($header);
            $response = $client->CreditSale_Soap($xml_array);


            /* Converty CB Response*/
            $code = $response->CreditSale_SoapResult->Result->ResultCode;

            $result = array(
                'result_state'=>$code,
                'result_code'=>(!$code ? 1 : 2),
                'result_message'=>$response->CreditSale_SoapResult->Result->ResultDetail,
                'result_transaction_id'=>$response->CreditSale_SoapResult->MCSTransactionID,
                'result_transaction_guid'=>$response->CreditSale_SoapResult->ProcessorApprovalCode,
                'result_transaction_date'=>date('Y-m-d H:s:i'),
                'result_tracking_member_code'=>$params['trackingMemberCode'],
                'result_cdc_data'=>$response->CreditSale_SoapResult
            );

            if (!$code){
                $response_array = array('chargeResult' => json_encode($result));
                $response = $this->ci->response->TransactionResponse(1, $response_array);
            } else {
                $response_array = array('chargeResult' => json_encode($result),'reason' => $response->CreditSale_SoapResult->Result->ResultDetail);
                $response = $this->ci->response->TransactionResponse(2, $response_array);

            }
        }

        catch (Exception $e)
        {

            $response_array = array('reason' => $client->__getLastResponse());
            $response = $this->ci->response->TransactionResponse(2, $response_array);
        }

        return $response;

    }

    public function Refund($merchant,$clientData,$merchantProcessor,$authorization,$amount,$baseCurrency,$trackingCode,$params){

        $processor_data = json_decode($merchantProcessor->processor_data);

        if ($merchant->row()->live_mode){

            $wsdl = 'https://prod.oculusgateway.ge/api/api.asmx?WSDL';
        } else {
            $wsdl = 'https://test.oculusgateway.ge/api/api.asmx?WSDL';
        }


        $trace = true;
        $exceptions = false;

        $namespace = 'https://MyCardStorage.com/';

        //Body of the Soap Header.
        $headerbody = array(
            'UserName' => $processor_data->gateway_username,
            'Password' => $processor_data->gateway_password
        );

        //Create Soap Header.
        $header = new SOAPHeader($namespace, 'AuthHeader', $headerbody);

        $xml_array['creditCardCredit'] = array(
            'ServiceSecurity'=>array(
                'ServiceUserName'=>$processor_data->gateway_service_username,
                'ServicePassword'=>$processor_data->gateway_service_password,
                'MCSAccountID'=>$processor_data->gateway_account_id,
            ),
            'TransactionData'=>array(
                'Amount'=>(float)$amount,
                'MCSTransactionID'=>$authorization->authorization_code,
                'GatewayID'=>'3',
                'CountryCode'=>Translator::getCountryIsoFromId($params['countryId']),
                'CurrencyCode'=>$params['currencyId'],
                'PurchaseCardTaxAmount'=>'0',
            )
        );


        try
        {
            $client = new SoapClient($wsdl, array('trace' => $trace, 'exceptions' => $exceptions));

            //set the Headers of Soap Client.
            $client->__setSoapHeaders($header);

            $response = $client->CreditCredit_Soap($xml_array);

            /* Converty CB Response*/
            $code = $response->CreditCredit_SoapResult->Result->ResultCode;

            $result = array(
                'result_state'=>$code,
                'result_code'=>(!$code ? 1 : 2),
                'result_message'=>$response->CreditCredit_SoapResult->Result->ResultDetail,
                'result_transaction_id'=>$response->CreditCredit_SoapResult->MCSTransactionID,
                'result_transaction_guid'=>$response->CreditCredit_SoapResult->ProcessorApprovalCode,
                'result_transaction_date'=>date('Y-m-d H:s:i'),
                'result_tracking_member_code'=>$params['trackingMemberCode'],
                'result_cdc_data'=>$response->CreditCredit_SoapResult
            );


            if (!$code){
                $response_array = array('refundResult' => json_encode($result));
                $response = $this->ci->response->TransactionResponse(1, $response_array);
            } else {
                $response_array = array('refundResult' => json_encode($result),'reason' => $response->CreditCredit_SoapResult->Result->ResultDetail);
                $response = $this->ci->response->TransactionResponse(2, $response_array);

            }
        }

        catch (Exception $e)
        {
            return $client->__getLastResponse();
        }

        return $response;
    }

    public function Authorize($client,$client_processor,$credit_card,$amount,$base_currency,$tracking_code,$params){

        $processor_data = json_decode($client_processor->processor_data);

        if ($client->live_mode){

            $wsdl = 'https://prod.oculusgateway.ge/api/api.asmx?WSDL';
        } else {
            $wsdl = 'https://test.oculusgateway.ge/api/api.asmx?WSDL';
        }

        $trace = true;
        $exceptions = false;

        $namespace = 'https://MyCardStorage.com/';

        //Body of the Soap Header.
        $headerbody = array(
            'UserName' => $processor_data->gateway_username,
            'Password' => $processor_data->gateway_password
        );

        //Create Soap Header.
        $header = new SOAPHeader($namespace, 'AuthHeader', $headerbody);

        $CardType = $this->cc_validator->validate($credit_card['card_num']);

        $xml_array['creditCardAuth'] = array(
            'ServiceSecurity'=>array(
                'ServiceUserName'=>$processor_data->gateway_service_username,
                'ServicePassword'=>$processor_data->gateway_service_password,
                'MCSAccountID'=>$processor_data->gateway_account_id,
            ),
            'TokenData'=>array(
                'TokenType'=>'5',
                'CardNumber'=>$credit_card['card_num'],
                'CardType'=>Translator::getCardIdByIssuer($CardType),
                'ExpirationMonth'=>$credit_card['exp_month'],
                'ExpirationYear'=>substr($credit_card['exp_year'], -2),
                'CVV'=>$credit_card['cvv'],
                'XID'=>(isset($params['Xid']) && !empty($params['Xid']) ? $params['Xid'] : null),
                'CAVV'=>(isset($params['Cavv']) && !empty($params['Cavv']) ? $params['Cavv'] : null),
            ),
            'TransactionData'=>array(
                'Amount'=>format_money($amount),
                'MCSTransactionID'=>'0',
                'GatewayID'=>'3',
                'CountryCode'=>$params['country_code'],
                'CurrencyCode'=>Translator::getCurrencyIdFromIsoCode($base_currency),
                'PurchaseCardTaxAmount'=>'0',
            )
        );

        try
        {
            $client = new SoapClient($wsdl, array('trace' => $trace, 'exceptions' => $exceptions));

            //set the Headers of Soap Client.
            $client->__setSoapHeaders($header);
            $response = $client->CreditAuth_Soap($xml_array);


            /* Converty CB Response*/
            $code = $response->CreditAuth_SoapResult->Result->ResultCode;

            $result = array(
                'result_state'=>$code,
                'result_code'=>(!$code ? 1 : 2),
                'result_message'=>$response->CreditAuth_SoapResult->Result->ResultDetail,
                'result_transaction_id'=>$response->CreditAuth_SoapResult->MCSTransactionID,
                'result_transaction_guid'=>$response->CreditAuth_SoapResult->ProcessorApprovalCode,
                'result_transaction_date'=>date('Y-m-d H:s:i'),
                'result_tracking_member_code'=>$params['tracking_member_code'],
                'result_cdc_data'=>$response->CreditAuth_SoapResult
            );

            if (!$code){
                $response_array = array('authorizeResult' => json_encode($result));
                $response = $this->ci->response->TransactionResponse(1, $response_array);
            } else {
                $response_array = array('authorizeResult' => json_encode($result),'reason' => $response->CreditAuth_SoapResult->Result->ResultDetail);
                $response = $this->ci->response->TransactionResponse(2, $response_array);

            }
        }

        catch (Exception $e)
        {

            $response_array = array('reason' => $client->__getLastResponse());
            $response = $this->ci->response->TransactionResponse(2, $response_array);
        }

        return $response;

    }

    public function Capture($client,$client_processor,$authorization,$transaction,$amount,$base_currency,$tracking_code,$params){

        $processor_data = json_decode($client_processor->processor_data);

        if ($client->live_mode){

            $wsdl = 'https://prod.oculusgateway.ge/api/api.asmx?WSDL';
        } else {
            $wsdl = 'https://test.oculusgateway.ge/api/api.asmx?WSDL';
        }


        $processor_params = unserialize($client_processor->processor_data);

        $trace = true;
        $exceptions = false;

        $namespace = 'https://MyCardStorage.com/';

        //Body of the Soap Header.
        $headerbody = array(
            'UserName' => $processor_data->gateway_username,
            'Password' => $processor_data->gateway_password
        );

        //Create Soap Header.
        $header = new SOAPHeader($namespace, 'AuthHeader', $headerbody);

        $xml_array['creditCardCapture'] = array(
            'ServiceSecurity'=>array(
                'ServiceUserName'=>$processor_data->gateway_service_username,
                'ServicePassword'=>$processor_data->gateway_service_password,
                'MCSAccountID'=>$processor_data->gateway_account_id,
            ),
            'TransactionData'=>array(
                'Amount'=>format_money($transaction->processed_amount),
                'MCSTransactionID'=>$authorization->authorization_id,
                'GatewayID'=>'3',
                'CountryCode'=>$transaction->country_code,
                'CurrencyCode'=>Translator::getCurrencyIdFromIsoCode($transaction->currency_code),
                'PurchaseCardTaxAmount'=>'0',
            )
        );



        try
        {
            $client = new SoapClient($wsdl, array('trace' => $trace, 'exceptions' => $exceptions));

            //set the Headers of Soap Client.
            $client->__setSoapHeaders($header);

            $response = $client->CreditCapture_Soap($xml_array);

            /* Converty CB Response*/
            $code = $response->CreditCapture_SoapResult->Result->ResultCode;

            $result = array(
                'result_state'=>$code,
                'result_code'=>(!$code ? 1 : 2),
                'result_message'=>$response->CreditCapture_SoapResult->Result->ResultDetail,
                'result_transaction_id'=>$response->CreditCapture_SoapResult->MCSTransactionID,
                'result_transaction_guid'=>$response->CreditCapture_SoapResult->ProcessorApprovalCode,
                'result_transaction_date'=>date('Y-m-d H:s:i'),
                'result_tracking_member_code'=>$params['tracking_member_code'],
                'result_cdc_data'=>$response->CreditCapture_SoapResult
            );


            if (!$code){
                $response_array = array('captureResult' => json_encode($result));
                $response = $this->ci->response->TransactionResponse(1, $response_array);
            } else {
                $response_array = array('captureResult' => json_encode($result),'reason' => $response->CreditCapture_SoapResult->Result->ResultDetail);
                $response = $this->ci->response->TransactionResponse(2, $response_array);

            }
        }

        catch (Exception $e)
        {
            return $client->__getLastResponse();
        }

        return $response;
    }

    public function Void($client,$client_processor,$authorization,$transaction,$amount,$base_currency,$tracking_code,$params){

        $processor_data = json_decode($client_processor->processor_data);

        if ($client->live_mode){

            $wsdl = 'https://prod.oculusgateway.ge/api/api.asmx?WSDL';
        } else {
            $wsdl = 'https://test.oculusgateway.ge/api/api.asmx?WSDL';
        }


        $processor_params = unserialize($client_processor->processor_data);

        $trace = true;
        $exceptions = false;

        $namespace = 'https://MyCardStorage.com/';

        //Body of the Soap Header.
        $headerbody = array(
            'UserName' => $processor_data->gateway_username,
            'Password' => $processor_data->gateway_password
        );

        //Create Soap Header.
        $header = new SOAPHeader($namespace, 'AuthHeader', $headerbody);

        $xml_array['creditCardVoid'] = array(
            'ServiceSecurity'=>array(
                'ServiceUserName'=>$processor_data->gateway_service_username,
                'ServicePassword'=>$processor_data->gateway_service_password,
                'MCSAccountID'=>$processor_data->gateway_account_id,
            ),
            'TransactionData'=>array(
                'Amount'=>format_money($transaction->processed_amount),
                'MCSTransactionID'=>$authorization->authorization_id,
                'GatewayID'=>'3',
                'CountryCode'=>$transaction->country_code,
                'CurrencyCode'=>Translator::getCurrencyIdFromIsoCode($transaction->currency_code),
                'PurchaseCardTaxAmount'=>'0',
            )
        );



        try
        {
            $client = new SoapClient($wsdl, array('trace' => $trace, 'exceptions' => $exceptions));

            //set the Headers of Soap Client.
            $client->__setSoapHeaders($header);

            $response = $client->CreditVoid_Soap($xml_array);

            /* Converty CB Response*/
            $code = $response->CreditVoid_SoapResult->Result->ResultCode;

            $result = array(
                'result_state'=>$code,
                'result_code'=>(!$code ? 1 : 2),
                'result_message'=>$response->CreditVoid_SoapResult->Result->ResultDetail,
                'result_transaction_id'=>$response->CreditVoid_SoapResult->MCSTransactionID,
                'result_transaction_guid'=>$response->CreditVoid_SoapResult->ProcessorApprovalCode,
                'result_transaction_date'=>date('Y-m-d H:s:i'),
                'result_tracking_member_code'=>$params['tracking_member_code'],
                'result_cdc_data'=>$response->CreditVoid_SoapResult
            );


            if (!$code){
                $response_array = array('voidResult' => json_encode($result));
                $response = $this->ci->response->TransactionResponse(1, $response_array);
            } else {
                $response_array = array('voidResult' => json_encode($result),'reason' => $response->CreditVoid_SoapResult->Result->ResultDetail);
                $response = $this->ci->response->TransactionResponse(2, $response_array);

            }
        }

        catch (Exception $e)
        {
            return $client->__getLastResponse();
        }

        return $response;
    }
}


/* End of file Oculus.php */ 