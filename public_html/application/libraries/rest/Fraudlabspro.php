<?php
/* Copyright (C) 2013-2014 FraudLabsPro.com
 * All Rights Reserved
 *
 * This library is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; If not, see <http://www.gnu.org/licenses/>.
 *
 * Purpose: Class to implement fraud checking solution using FraudLabs Pro service.
 * 	        API Key required, and if you do not have an API key, you may sign up free
 * 			at http://www.fraudlabspro.com
 */

class FraudLabsPro {
    private $apiKey;
    public $flpRequest;
    public $flpResponse;		//Will be instantiated on success fraudCheck for returnAs=>string option only

    // Constructor
    public function __construct(){

        $this->ci =& get_instance();
        // Store the api key for calling
        if(!preg_match('/^[A-Z0-9]{32}$/', get_fraudlabs_value('key'))) throw new exception('FraudLabsPro: Invalid API key provided.');

        $this->apiKey = get_fraudlabs_value('key');

        $this->ci->load->library('rest/flp_request');
        $this->ci->load->library('rest/flp_response');

        $this->flpRequest = new Flp_Request();
        $this->flpResponse = NULL;
    }

    // Destructor
    public function __destruct(){
        unset($flpRequest);
        unset($flpResponse);
    }

    ///////////////////////////////////////
    // Purpose: perform fraud check
    // Input:
    //	returnAs:	json - return json result
    //				xml - return xml result
    //				string - return fraud status in string (APPROVE, REVIEW, REJECT, <ERROR MESSAGE>)
    //
    // Output:
    //	Depend on the returnAs param
    ///////////////////////////////////////
    public function fraudCheck($returnAs = 'string'){
        //reset the variable prior to insertion
        unset($this->response);

        // Perform validation (where applicable) and construct the REST queries
        $params = 'key=' . $this->apiKey;

        if (is_null($this->flpRequest->ipAddress)){
            //Default IP Address if null
            $this->flpRequest->ipAddress = $_SERVER['REMOTE_ADDR'];
        }
        $params .= '&ip=' . $this->flpRequest->ipAddress;

        switch ($returnAs){
            case 'string':
                $params .= '&format=json';
                break;

            case 'json':
                $params .= '&format=json';
                break;

            case 'xml':
                $params .= '&format=xml';
                break;

            default:
                $params .= '&format=json';
        }

        if (!is_null($this->flpRequest->firstName)){
            $params .= '&first_name=' . rawurlencode($this->flpRequest->firstName);
        }

        if (!is_null($this->flpRequest->firstName)){
            $params .= '&first_name=' . rawurlencode($this->flpRequest->firstName);
        }

        if (!is_null($this->flpRequest->lastName)){
            $params .= '&bill_city=' . rawurlencode($this->flpRequest->lastName);
        }


        if (!is_null($this->flpRequest->billingCity)){
            $params .= '&bill_city=' . rawurlencode($this->flpRequest->billingCity);
        }

        if (!is_null($this->flpRequest->billingZIPCode)){
            $params .= '&bill_zip_code=' . rawurlencode($this->flpRequest->billingZIPCode);
        }

        if (!is_null($this->flpRequest->billingState)){
            $params .= '&bill_state=' . rawurlencode($this->flpRequest->billingState);
        }

        if (!is_null($this->flpRequest->billingCountry)){
            if(!$this->isCountryCode($this->flpRequest->billingCountry)) throw new exception('FraudLabsPro->fraudCheck(): [billingCountry] Invalid country code.');

            $params .= '&bill_country=' . rawurlencode($this->flpRequest->billingCountry);
        }

        if (!is_null($this->flpRequest->shippingAddress)){
            $params .= '&ship_addr=' . rawurlencode($this->flpRequest->shippingAddress);
        }

        if (!is_null($this->flpRequest->shippingCity)){
            $params .= '&ship_city=' . rawurlencode($this->flpRequest->shippingCity);
        }

        if (!is_null($this->flpRequest->shippingZIPCode)){
            $params .= '&ship_zip_code=' . rawurlencode($this->flpRequest->shippingZIPCode);
        }

        if (!is_null($this->flpRequest->shippingState)){
            $params .= '&ship_state=' . rawurlencode(urlencode($this->flpRequest->shippingState));
        }

        if (!is_null($this->flpRequest->shippingCountry)){
            if(!$this->isCountryCode($this->flpRequest->shippingCountry)) throw new exception('FraudLabsPro->fraudCheck(): [shippingCountry] Invalid country code.');

            $params .= '&ship_country=' . rawurlencode($this->flpRequest->shippingCountry);
        }

        if (!is_null($this->flpRequest->emailAddress)){
            //Validate email address
            if(!filter_var($this->flpRequest->emailAddress, FILTER_VALIDATE_EMAIL)) throw new exception('FraudLabsPro->fraudCheck(): [emailAddress] Invalid email address provided.');

            //Prepare the email adomain and hash for checking
            $params .= '&email_domain=' . rawurlencode(substr($this->flpRequest->emailAddress, strpos($this->flpRequest->emailAddress, '@')+1));
            $params .= '&email=' . rawurlencode($this->flpRequest->emailAddress);
            $params .= '&email_hash=' . rawurlencode($this->doHash($this->flpRequest->emailAddress));
        }

        if (!is_null($this->flpRequest->username)){
            $params .= '&username_hash=' . rawurlencode($this->doHash($this->flpRequest->username));
        }

        if (!is_null($this->flpRequest->password)){
            $params .= '&password_hash=' . rawurlencode($this->doHash($this->flpRequest->password));
        }

        if (!is_null($this->flpRequest->creditCardNumber)){
            $params .= '&bin_no=' . rawurlencode(substr(preg_replace('/\D/', '', $this->flpRequest->creditCardNumber), 0, 6));
            $params .= '&card_hash=' . rawurlencode($this->doHash(preg_replace('/\D/', '', $this->flpRequest->creditCardNumber)));
        }

        if (!is_null($this->flpRequest->phone)){
            $params .= '&user_phone=' . rawurlencode(preg_replace('/\D/', '', $this->flpRequest->phone));
        }

        if (!is_null($this->flpRequest->bankName)){
            $params .= '&bin_bank_name=' . rawurlencode($this->flpRequest->bankName);
        }

        if (!is_null($this->flpRequest->bankPhone)){
            $params .= '&bin_bank_phone=' . rawurlencode(preg_replace('/\D/', '', $this->flpRequest->bankPhone));
        }

        if (!is_null($this->flpRequest->avsResult)){
            $params .= '&avs_result=' . rawurlencode($this->flpRequest->avsResult);
        }

        if (!is_null($this->flpRequest->cvvResult)){
            $params .= '&cvv_result=' . rawurlencode($this->flpRequest->cvvResult);
        }

        if (!is_null($this->flpRequest->orderId)){
            $params .= '&user_order_id=' . rawurlencode($this->flpRequest->orderId);
        }

        if (!is_null($this->flpRequest->amount)){
            $params .= '&amount=' . rawurlencode($this->flpRequest->amount);
        }

        if (!is_null($this->flpRequest->quantity)){
            $params .= '&quantity=' . rawurlencode($this->flpRequest->quantity);
        }

        if (!is_null($this->flpRequest->currency)){
            $params .= '&currency=' . rawurlencode($this->flpRequest->currency);
        }

        if (!is_null($this->flpRequest->department)){
            $params .= '&department=' . rawurlencode(urlencode($this->flpRequest->department));
        }

        if (!is_null($this->flpRequest->paymentMode)){
            if(!$this->isValidPaymentMode($this->flpRequest->paymentMode)) throw new exception('FraudLabsPro->fraudCheck(): [paymentMode] Invalid payment mode. Valid values are creditcard, paypal, googlecheckout, cod, moneyorder, wired, bankdeposit, others');

            $params .= '&payment_mode=' . rawurlencode($this->flpRequest->paymentMode);
        }

        if (!is_null($this->flpRequest->sessionId)){
            $params .= '&session_id=' . rawurlencode($this->flpRequest->sessionId);
        }

        if (!is_null($this->flpRequest->ipAddress)){
            $params .= '&ip_address=' . rawurlencode($this->flpRequest->ipAddress);
        }

        if (!is_null($this->flpRequest->flpChecksum)){
            $params .= '&flp_checksum=' . rawurlencode($this->flpRequest->flpChecksum);
        }

        //Perform fraud check (3 tries on fails)
        $retry = 0;
        while($retry++ < 3){
            $result = $this->http('https://api.fraudlabspro.com/v1/order/screen?' . $params);
            if($result) break;
            sleep(2);
        }

        //Return value to caller
        switch($returnAs){
            case 'string':
                if(!is_null($json = json_decode($result))){
                    //create response object
                    $this->flpResponse = new Flp_Response();
                    $this->flpResponse->decodeJsonResult($result);

                    if (intval($json->fraudlabspro_error_code) == 0)
                        return $json->fraudlabspro_status;
                    else
                        return $json->fraudlabspro_message;
                }
                else
                    return '';

            //return json or xml depends on user defined
            default:
                return $result;
        }
    }

    ///////////////////////////////////////
    // Purpose: feedback the order status
    // Input:
    //	transactionID - transaction ID
    //	action - APPROVE, REJECT
    //	returnAs:	json - return json result
    //				xml - return xml result
    //
    // Output:
    //	Depend on the returnAs param
    ///////////////////////////////////////
    public function feedbackOrder($transactionID, $action, $returnAs = 'json'){
        // Perform validation (where applicable) and construct the REST queries
        $params = 'key=' . $this->apiKey;
        $params .= '&id=' . rawurlencode($transactionID);

        if (in_array($action, array('APPROVE', 'REJECT'))){
            $params .= '&action=' . $action;
        }
        else
            return NULL;

        if (in_array($returnAs, array('json', 'xml'))){
            $params .= '&format=' . $returnAs;
        }
        else
            return NULL;

        //Perform fraud check (3 tries on fails)
        $retry = 0;
        while($retry++ < 3){
            $result = $this->http('https://api.fraudlabspro.com/v1/order/feedback?' . $params);
            if($result) break;
            sleep(2);
        }

        //Return value to caller
        return $result;
    }

    // List of ISO-3166 country codes for validation before sent
    private function isCountryCode($cc){
        if(!$cc) return false;

        return in_array($cc, array('AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AN', 'AO', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AW', 'AX', 'AZ', 'BA', 'BB', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BL', 'BM', 'BN', 'BO', 'BQ', 'BR', 'BS', 'BT', 'BV', 'BW', 'BY', 'BZ', 'CA', 'CC', 'CD', 'CF', 'CG', 'CH', 'CI', 'CK', 'CL', 'CM', 'CN', 'CO', 'CR', 'CS', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ', 'DE', 'DJ', 'DK', 'DM', 'DO', 'DZ', 'EC', 'EE', 'EG', 'EH', 'ER', 'ES', 'ET', 'FI', 'FJ', 'FK', 'FM', 'FO', 'FR', 'GA', 'GB', 'GD', 'GE', 'GF', 'GG', 'GH', 'GI', 'GL', 'GM', 'GN', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU', 'GW', 'GY', 'HK', 'HM', 'HN', 'HR', 'HT', 'HU', 'ID', 'IE', 'IL', 'IM', 'IN', 'IO', 'IQ', 'IR', 'IS', 'IT', 'JE', 'JM', 'JO', 'JP', 'KE', 'KG', 'KH', 'KI', 'KM', 'KN', 'KP', 'KR', 'KW', 'KY', 'KZ', 'LA', 'LB', 'LC', 'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY', 'MA', 'MC', 'MD', 'ME', 'MF', 'MG', 'MH', 'MK', 'ML', 'MM', 'MN', 'MO', 'MP', 'MQ', 'MR', 'MS', 'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ', 'NA', 'NC', 'NE', 'NF', 'NG', 'NI', 'NL', 'NO', 'NP', 'NR', 'NU', 'NZ', 'OM', 'PA', 'PE', 'PF', 'PG', 'PH', 'PK', 'PL', 'PM', 'PN', 'PR', 'PS', 'PT', 'PW', 'PY', 'QA', 'RE', 'RO', 'RS', 'RU', 'RW', 'SA', 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SJ', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'SS', 'ST', 'SV', 'SX', 'SY', 'SZ', 'TC', 'TD', 'TF', 'TG', 'TH', 'TJ', 'TK', 'TL', 'TM', 'TN', 'TO', 'TR', 'TT', 'TV', 'TW', 'TZ', 'UA', 'UG', 'UM', 'US', 'UY', 'UZ', 'VA', 'VC', 'VE', 'VG', 'VI', 'VN', 'VU', 'WF', 'WS', 'XK', 'YE', 'YT', 'ZA', 'ZM', 'ZW'));
    }

    // List of support payment mode
    private function isValidPaymentMode($payment_mode){
        if(!$payment_mode) return false;

        return in_array($payment_mode, array('creditcard', 'paypal', 'googlecheckout', 'cod', 'moneyorder', 'wired', 'bankdeposit', 'others'));
    }

    // Do the hashing. This applies to several params, i.e, email, username, password and credit card number
    private function doHash($s, $prefix='fraudlabspro_'){
        $hash = $prefix . $s;
        for($i=0; $i<65536; $i++) $hash = sha1($prefix . $hash);

        return $hash;
    }

    // Perform the HTTP query
    private function http($url){
        if(!function_exists('curl_init')) throw new exception('FraudLabsPro: cURL extension is not enabled.');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_USERAGENT, 'FraudLabsPro API Client 1.0.0');

        $response = curl_exec($ch);

        if(empty($response) || curl_error($ch) || curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200){
            curl_close($ch);
            return false;
        }

        curl_close($ch);

        return $response;
    }

    public function _initiate($params){

        $this->ci->load->library('currency');
        $Currency = new Currency();

        /////////////////////////////////////////////////
        // Enter sales order information for fraud check
        // For example:
        //    Ship item to US, bill to MY, pay with credit card
        //	  Amount: $123.00 for 1 item
        // Billing Information
        if (isset($params['customer']['billing_city']) && !empty($params['customer']['billing_city'])){
            $this->flpRequest->billingCity = $params['customer']['billing_city'];
        }
        if (isset($params['customer']['billing_zip']) && !empty($params['customer']['billing_zip'])){
            $this->flpRequest->billingZIPCode = $params['customer']['billing_zip'];
        }

        if (isset($params['customer']['billing_state']) && !empty($params['customer']['billing_state'])){
            $this->flpRequest->billingState = $params['customer']['billing_state'];
        }

        if (isset($params['customer']['billing_country_code']) && !empty($params['customer']['billing_country_code'])){
            $this->flpRequest->billingCountry = $params['customer']['billing_country_code'];
        }

        // Shipping Information
        if (isset($params['customer']['shipping_address']) && !empty($params['customer']['shipping_address'])){
            $this->flpRequest->shippingAddress = $params['customer']['shipping_address'];
        } else if ((!isset($params['customer']['shipping_address']) || empty($params['customer']['shipping_address'])) && isset($params['avs_address']) || !empty($params['avs_address'])) {
            $this->flpRequest->shippingAddress = $params['avs_address'];
        }

        if (isset($params['customer']['shipping_city']) && !empty($params['customer']['shipping_city'])){
            $this->flpRequest->shippingCity = $params['customer']['shipping_city'];
        }

        if (isset($params['customer']['shipping_zip']) && !empty($params['customer']['shipping_zip'])){
            $this->flpRequest->shippingZIPCode = $params['customer']['shipping_zip'];
        } else if ((!isset($params['customer']['shipping_zip']) || empty($params['customer']['shipping_zip'])) && isset($params['avs_zip']) || !empty($params['avs_zip'])) {
            $this->flpRequest->shippingZIPCode = $params['avs_zip'];
        }

        if (isset($params['customer']['shipping_state']) && !empty($params['customer']['shipping_state'])){
            $this->flpRequest->shippingState = $params['customer']['shipping_state'];
        }

        if (isset($params['customer']['shipping_country_code']) && !empty($params['customer']['shipping_country_code'])){
            $this->flpRequest->shippingCountry = $params['customer']['shipping_country_code'];
        }

        if (isset($params['customer']['email_address']) && !empty($params['customer']['email_address'])){
            $this->flpRequest->emailAddress = $params['customer']['email_address'];
        }

        $this->flpRequest->ipAddress = $_SERVER['REMOTE_ADDR'];


        $this->flpRequest->creditCardNumber = $params['creditCard']['cardNumber'];
        $this->flpRequest->orderId = $params['trackingMemberCode'];
        $this->flpRequest->amount = $params['amount'];
        $this->flpRequest->quantity = 1;
        $this->flpRequest->shippingAddress = 'Kaludjerica Karadjordjeva';
        $this->flpRequest->shippingCity = '11130';
        $this->flpRequest->currency = Translator::getCurrencyIdFromIsoCode($params['currencyId'],true);
        $this->flpRequest->paymentMode = 'creditcard';

        // Invoke fraud check
        $result = $this->fraudCheck('json');

        $response = json_decode($result);

        return array(
            'fraud_trx_id'=>$response->fraudlabspro_id,
            'fraud_score'=>$response->fraudlabspro_score,
            'fraud_status'=>$response->fraudlabspro_status,
            'fraudlabspro_response'=>$result
        );
    }
}

?>