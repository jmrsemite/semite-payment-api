<?php

class Semite_ThreeDSecureOperations_CheckEnrollment extends Semite_Operation
{
	protected $_action     = 'threedsecureoperations';
	protected $_parameters = array(
        'authentication'            => array(
            'api_id'=>array('required'=>TRUE),
            'secret_key'=>array('required'=>TRUE)
        ),
        'credit_card'=>array(
            'card_num'=>array('required'=>TRUE),
            'exp_month'=>array('required'=>TRUE),
            'exp_year'=>array('required'=>TRUE),
            'cvv'=>array(),
        ),
        'type'=>array(
            'required'      => TRUE,
            'default_value' => 'CheckEnrollment' // E-commerce
        ),
        'user_agent'=>array(),
        'amount'=>array('required'=>TRUE),
        'currency_code'=>array('required'=>TRUE),
        'country_code'=>array('required'=>TRUE),
        'processor'=>array('required'=>TRUE),
        'merchant_account_type'=>array(
            'required'      => TRUE,
            'default_value' => 1 // E-commerce
        ),
        'tracking_member_code'=>array('required'=>TRUE),
		);

    protected $_result_cmpi_lookup_message_resp = NULL;
	protected $_result_enrollment_id = NULL;
	protected $_result_issuer_url    = NULL;
	protected $_result_payment_authentication_request = NULL;

    public function setAmountAndCurrencyId ($amount, $currency_code)
    {
        if (is_numeric($amount) && $amount > 0 && $currency_code)
        {
            $this->addData('amount',     $amount);
            $this->addData('currency_code', $currency_code);
        }
        else
        {
            throw new Semite_Exception('Amount or Currency ID not set or invalid');
        }
    }

    public function setUserAgent($user_agent){

        $this->addData('user_agent',$user_agent);

    }

    public function setCardNumberAndHolder ($card_number, $card_holder = NULL)
    {
        $this->addData('card_num', $card_number,'credit_card');

        if ($card_holder)
            $this->addData('card_holder', $card_holder,'credit_card');
    }

    public function setCardExpiry ($expiry_month, $expiry_year)
    {
        $this->addData('exp_month', $expiry_month,'credit_card');
        $this->addData('exp_year',  $expiry_year,'credit_card');
    }

    public function setCardValidationCode ($card_vc)
    {
        $this->addData('cvv', $card_vc,'credit_card');
    }

    public function getResultCmpiLookupMessage ()
    {
        return $this->_result_cmpi_lookup_message_resp;
    }

	public function getResultEnrollmentId ()
	{
		return $this->_result_enrollment_id;
	}

	public function getResultIssuerUrl ()
	{
		return $this->_result_issuer_url;
	}

	public function getResultPaymentAuthenticationRequest ()
	{
		return $this->_result_payment_authentication_request;
	}

	public function getRedirectHtmlForm ($return_url, $merchant_data = NULL)
	{
		$html =
			'<html>' . PHP_EOL .
			'	<head>' . PHP_EOL .
			'		<title>Payment - 3D secure redirect</title>' . PHP_EOL .
			'	</head>' . PHP_EOL .
			'	<script type="text/javascript">' . PHP_EOL .
			'		function OnLoadEvent()' . PHP_EOL .
			'		{' . PHP_EOL .
			'			// Make the form post as soon as it has been loaded.' . PHP_EOL .
			'			document.threedsecureform.submit();' . PHP_EOL .
			'		}' . PHP_EOL .
			'	</script>' . PHP_EOL .
			'	<body onload="OnLoadEvent();">' . PHP_EOL .
			'		<p>' . PHP_EOL .
			'			If your browser does not start loading the page, press the button below.' . PHP_EOL .
			'			You will be sent back to this site after you authorize the transaction.' . PHP_EOL .
			'		</p>' . PHP_EOL .
			'		<form name="threedsecureform" method="post" action="' . $this->getResultIssuerUrl() . '" target="_top">' . PHP_EOL .
			'		<button type="submit">Go to 3D secure page</button>' . PHP_EOL .
			'		<input type="hidden" name="PaReq" value="' . $this->getResultPaymentAuthenticationRequest() . '" />' . PHP_EOL .
			'		<input type="hidden" name="TermUrl" value="' . $return_url . '" />' . PHP_EOL .
			'		<input type="hidden" name="MD" value="' . $merchant_data . '" />' . PHP_EOL .
			'		</form>' . PHP_EOL .
			'	</body>' . PHP_EOL .
			'</html>';

		return $html;
	}

	public function processXmlResult (SimpleXMLElement $simplexml)
	{
		parent::processXmlResult($simplexml);

		if (isset($simplexml->EnrollmentId))
		{
			$this->_result_enrollment_id = $simplexml->EnrollmentId->__toString();
		}

		if (isset($simplexml->IssuerUrl))
		{
			$this->_result_issuer_url = $simplexml->IssuerUrl->__toString();
		}

		if (isset($simplexml->PaymentAuthenticationRequest))
		{
			$this->_result_payment_authentication_request = $simplexml->PaymentAuthenticationRequest->__toString();
		}

        if (isset($simplexml->CmpiLookupMessageResp))
        {
            $this->_result_cmpi_lookup_message_resp = $simplexml->CmpiLookupMessageResp;
        }
	}
}