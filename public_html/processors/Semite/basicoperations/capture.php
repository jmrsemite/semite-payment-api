<?php

class Semite_BasicOperations_Capture extends Semite_Operation
{
    protected $_action     = 'basicoperations';
    protected $_parameters = array(
        'authentication'            => array(
            'api_id'=>array('required'=>TRUE),
            'secret_key'=>array('required'=>TRUE)
        ),
        'type'=>array(
            'required'      => TRUE,
            'default_value' => 'Capture' // E-commerce
        ),
        'processor'=>array('required'=>TRUE),
        'tracking_member_code'=>array('required'=>TRUE),
        'transaction_id'  => array(
            'required' => TRUE,
        ),
    );

    public function setTransactionId ($transaction_id)
    {
        if ($transaction_id)
        {
            $this->addData('transaction_id', $transaction_id);
        }
        else
        {
            throw new Semite_Exception('Transaction ID not set or invalid');
        }
    }

    public function setAmountAndCurrencyId ($amount, $currency_code)
    {
        if (is_numeric($amount) && $amount > 0 && $currency_code)
        {
            $this->addData('amount',     $amount);
            $this->addData('currency_code', $currency_code);
        }
        else
        {
            throw new Semite_Exception('Amount or Currency Code not set or invalid');
        }
    }
}


