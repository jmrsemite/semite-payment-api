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


class Flp_Request{
    public $ipAddress = NULL;
    public $billingCity = NULL;
    public $billingZIPCode = NULL;
    public $billingState = NULL;
    public $billingCountry = NULL;
    public $shippingAddress = NULL;
    public $shippingCity = NULL;
    public $shippingZIPCode = NULL;
    public $shippingState = NULL;
    public $shippingCountry = NULL;
    public $emailAddress = NULL;
    public $username = NULL;
    public $password = NULL;
    public $creditCardNumber = NULL;
    public $phone = NULL;
    public $bankName = NULL;
    public $bankPhone = NULL;
    public $avsResult = NULL;
    public $cvvResult = NULL;
    public $orderId = NULL;
    public $amount = NULL;
    public $quantity = NULL;
    public $currency = NULL;
    public $department = NULL;
    public $paymentMode = NULL;
    public $sessionId = NULL;
    public $lastName = NULL;
    public $firstName = NULL;
    public $flpChecksum = NULL;


    //Reset the variables
    public function reset(){
        $this->ipAddress = NULL;
        $this->billingCity = NULL;
        $this->billingZIPCode = NULL;
        $this->billingState = NULL;
        $this->billingCountry = NULL;
        $this->shippingAddress = NULL;
        $this->shippingCity = NULL;
        $this->shippingZIPCode = NULL;
        $this->shippingState = NULL;
        $this->shippingCountry = NULL;
        $this->emailAddress = NULL;
        $this->username = NULL;
        $this->password = NULL;
        $this->creditCardNumber = NULL;
        $this->phone = NULL;
        $this->bankName = NULL;
        $this->bankPhone = NULL;
        $this->avsResult = NULL;
        $this->cvvResult = NULL;
        $this->orderId = NULL;
        $this->amount = NULL;
        $this->quantity = NULL;
        $this->currency = NULL;
        $this->department = NULL;
        $this->paymentMode = NULL;
        $this->sessionId = NULL;
        $this->lastName = NULL;
        $this->firstName = NULL;
        $this->flpChecksum = NULL;
    }
}


/* End of file Flp_request.php */ 