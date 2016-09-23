<?php

class Semite_ThreeDSecureOperations_PaymentUsingIntegratedMPI extends Semite_Operation
{
	protected $_action     = 'threedsecureoperations';
	protected $_parameters = array(
            'authentication'            => array(
                'api_id'=>array('required'=>TRUE),
                'secret_key'=>array('required'=>TRUE)
            ),
            'credit_card'=>array(
                'cvv'=>array(),
            ),
            'type'=>array(
                'required'      => TRUE,
                'default_value' => 'PaymentUsingIntegratedMPI' // E-commerce
            ),
            'user_agent'=>array(),
            'country_code'=>array(),
            'processor'=>array('required'=>TRUE),
            'tracking_member_code'=>array(),
			'enrollmentId'  => array(
					'required' => TRUE,
				),
			'enrollmentTrackingMemberCode' => array(
					'required' => TRUE,
				),
			'payerAuthenticationResponse' => array(
					'required' => TRUE, // Required if you would really want this to be a 3dsecure payment
			),
		);

    protected $_result_xid = NULL;
    protected $_result_cavv    = NULL;
    protected $_result_code     = NULL;
    protected $_result_message  = NULL;
    protected $_result_cmpi_message_resp    = array();

    public function setCardValidationCode ($card_vc)
    {
        $this->addData('cvv', $card_vc,'credit_card');
    }

	public function setEnrollmentId ($enrollment_id)
	{
		$this->addData('enrollmentId', $enrollment_id);
	}

	public function setEnrollmentTrackingMemberCode ($enrollment_tracking_member_code)
	{
		$this->addData('enrollmentTrackingMemberCode', $enrollment_tracking_member_code);
	}

	public function setPayerAuthenticationResponse ($payer_authentication_response)
	{
		$this->addData('payerAuthenticationResponse', $payer_authentication_response);
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

    public function getResultXid(){

        return $this->_result_xid;
    }

    public function getResultCavv(){

        return $this->_result_cavv;
    }

    public function getResultCMpiMessageResp(){

        return $this->_result_cmpi_message_resp;
    }


    public function processXmlResult (SimpleXMLElement $simplexml)
    {
        parent::processXmlResult($simplexml);

        if (isset($simplexml->Xid))
        {
            $this->_result_xid = $simplexml->Xid->__toString();
        }

        if (isset($simplexml->Cavv))
        {
            $this->_result_cavv = $simplexml->Cavv->__toString();
        }

        if (isset($simplexml->Centinel_cmpiMessageResp))
        {
            $this->_result_cmpi_message_resp = $simplexml->Centinel_cmpiMessageResp;
        }

        if (isset($simplexml->error))
        {
            $this->_result_error = $simplexml->error->__toString();
        }

        if (isset($simplexml->error_text))
        {
            $this->_result_error_text = $simplexml->error_text->__toString();
        }
    }
}