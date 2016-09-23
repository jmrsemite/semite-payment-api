<?php

class Semite_Operation
{
	protected $_operation  = NULL;

	protected $_parameters = array();
	protected $_data       = array();

	protected $_result_object   = NULL;
	protected $_result_code     = NULL;
	protected $_result_message  = NULL;
    protected $_result_error   = NULL;
    protected $_result_error_text     = NULL;
	protected $_result_tracking_member_code = NULL;
	protected $_result_transaction_id       = NULL;
	protected $_result_authorization_id     = NULL;
    protected $_result_reason     = NULL;

	public function __construct ()
	{
		$this->_generateDefaultData();
	}

	public function getOperationName ()
	{
		return $this->_operation;
	}

	public function getDataAsArray ()
	{
		return $this->_data;
	}

	public function addData ($name, $value,$node=NULL)
	{
		if (isset($this->_data[$name]) || isset($this->_data[$node]))
		{
            if (isset($this->_data[$node])){
                return $this->_data[$node][$name] = $value;
            } else {
                return $this->_data[$name] = $value;
            }
		}
		else {
			throw new Semite_Exception("Variable '{$name}' is not available'");
		}
	}

	public function setMember ($api_id, $secret_key)
	{
		$this->addData('api_id',   $api_id,'authentication');
		$this->addData('secret_key', $secret_key,'authentication');
	}

	public function setCountryCode ($country_code)
	{
		$this->addData('country_code', $country_code);
	}

	public function setTrackingMemberCode ($tracking_member_code)
	{
		$this->addData('tracking_member_code', $tracking_member_code);
	}

    public function setMerchantAccountType ($merchant_account_type)
    {
        $this->addData('merchant_account_type', $merchant_account_type);
    }

    public function setProcessor ($processor)
    {
        $this->addData('processor', $processor);
    }

	public function getResultState ()
	{
        return ($this->_result_code === '0');
	}

	public function getResultCode ()
	{
        if (!$this->_result_error) {
            return $this->_result_code;
        } else {
            return $this->_result_error;
        }
	}

	public function getResultMessage ()
	{
		if (!$this->_result_error_text){
            return $this->_result_message;
        } else {
            return $this->_result_error_text;
        }
	}

	public function getResultTrackingMemberCode ()
	{
        if (!$this->_result_error_text){
		return $this->_result_tracking_member_code;
        } else {
            return '0000-0000-0000-0000-0000';
        }

	}

	public function getResultTransactionId ()
	{
        if (!$this->_result_error_text) {
            return $this->_result_transaction_id;
        } else {
            return 0;
        }
	}

    public function getResultReason ()
    {
        if (!$this->_result_reason) {
            return $this->_result_reason;
        } else {
            return null;
        }
    }

	public function getResultAuthorizationId ()
	{
        if (!$this->_result_error_text) {
            return $this->_result_authorization_id;
        } else {
            return 0;
        }
	}


	protected function _generateDefaultData ()
	{
		foreach ($this->_parameters as $name => $settings)
		{
			$this->_data[$name] = (isset($settings['default_value']) ? $settings['default_value'] :'');
		}
	}

	public function checkRequiredData ()
	{
		foreach ($this->_parameters as $name => $settings)
		{
			// Check if required field is set (not empty)
			if (isset($settings['required']) && $settings['required'] === TRUE
				&& $this->_data[$name] == '')
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Save generic information in object from XML object returned by Semite
	 *
	 * @param SimpleXMLElement $simplexml
	 */
	public function processXmlResult (SimpleXMLElement $simplexml)
	{
		if (isset($simplexml->response_code))
		{
			$this->_result_code = $simplexml->response_code->__toString();
		}

        if (isset($simplexml->error))
        {
            $this->_result_error = $simplexml->error->__toString();
        }

        if (isset($simplexml->error_text))
        {
            $this->_result_error_text = $simplexml->error_text->__toString();
        }

		if (isset($simplexml->response_text))
		{
			$this->_result_message = $simplexml->response_text->__toString();
		}

		if (isset($simplexml->tracking_code))
		{
			$this->_result_tracking_member_code = $simplexml->tracking_code->__toString();
		}

		if (isset($simplexml->transaction_id))
		{
			$this->_result_transaction_id = $simplexml->transaction_id->__toString();
		}

		if (isset($simplexml->authorization_id))
		{
			$this->_result_authorization_id = $simplexml->authorization_id->__toString();
		}

        if (isset($simplexml->reason))
        {
            $this->_result_reason = $simplexml->reason->__toString();
        }

	}
}