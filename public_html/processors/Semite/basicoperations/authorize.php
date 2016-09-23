<?php

class Semite_BasicOperations_Authorize extends Semite_Operation
{
    protected $_action     = 'basicoperations';
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
            'default_value' => 'Authorize' // E-commerce
        ),
        'amount'=>array('required'=>TRUE),
        'currency_code'=>array('required'=>TRUE),
        'country_code'=>array('required'=>TRUE),
        'processor'=>array('required'=>TRUE),
        'tracking_member_code'=>array('required'=>TRUE),
        'Xid'=>array(),
        'Cavv'=>array(),
        'merchant_account_type'=>array(
            'required'      => TRUE,
            'default_value' => 1 // E-commerce
        ),
    );

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

    public function setXidAndCavv ($xid, $cavv)
    {
        $this->addData('Xid',$xid);
        $this->addData('Cavv',$cavv);
    }

    public function setCardValidationCode ($card_vc)
    {
        $this->addData('cvv', $card_vc,'credit_card');
    }

}


