<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Authentication extends CI_Controller
{

    function __construct()
    {
        parent::__construct();
        $this->load->library('mpi/endeavour');
        $this->load->library('rest/arraytoxml','arraytoxml');
    }

    public function index(){

        parse_str($_POST['MD'],$MD);

        $this->db->where('id', $MD['merchant_id']);
        $merchant = $this->db->get('tblmerchants')->row();


        $Endeavour = new Endeavour();

        if ($merchant->live_mode) {

            $Endeavour->setMID($merchant->threeds_mid);
        }

        $authResponse = $Endeavour->MPIAuthenticate($_POST);

        $arrayToXml = new ArrayToXML();


        if ($authResponse['status'] == 'Y'){


            if ($merchant->live_mode) {
                $url = _LIVE_URL;
            } else {
                $url = _TEST_URL;
            }


            $post_string = '<?xml version="1.0" encoding="UTF-8"?>
<request>
   <authentication>
      <api_id>'.$merchant->api_id.'</api_id>
      <secret_key>'.$merchant->secret_key.'</secret_key>
   </authentication>
   <type>'.$MD['type'].'</type>
   <processor>'.$merchant->default_processor.'</processor>
  <countryId>'.$MD['countryId'].'</countryId>
 <amount>'.$MD['amount'].'</amount>
 <currencyId>'.$MD['currencyId'].'</currencyId>
 <trackingMemberCode>'.$MD['trackingMemberCode'].'</trackingMemberCode>
 <creditCard>
 <cardNumber>'.$MD['creditCard']['cardNumber'].'</cardNumber>
 <cardholder>'.(isset($MD['creditCard']['cardholder']) ? $MD['creditCard']['cardholder'] : null).'</cardholder>
 <cardExpiryMonth>'.$MD['creditCard']['cardExpiryMonth'].'</cardExpiryMonth>
 <cardExpiryYear>'.$MD['creditCard']['cardExpiryYear'].'</cardExpiryYear>
 <cardCvv>'.$MD['creditCard']['cardCvv'].'</cardCvv>
 </creditCard>
 <xid>'.$authResponse['xid'].'</xid>
 <merchantAccountType>1</merchantAccountType>
 <dbaName></dbaName>
 <dbaCity></dbaCity>
 <avsAddress></avsAddress>
 <avsZip></avsZip>
 <additionalInfo>'.json_encode($MD['additionalInfo']).'</additionalInfo>
</request>';

            $postfields = $post_string;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);


            $res = curl_exec($ch);

            if(curl_errno($ch))
            {
                echo curl_error($ch);
            }
            else
            {
                curl_close($ch);

                $xml = @simplexml_load_string($res);

                if($xml) {
                    $response = $arrayToXml->toArray($res);

                } else {
                    $resp = json_decode($res);
                    $response['response_code'] = $resp->response_code;
                    $response['TransactionId'] = $resp->TransactionId;
                }

            }



        } else {

            $response['response_code'] = 2;
            $response['TransactionId'] = '3dsfail';
        }


        redirect('http://map.semitepayment.io/terminal/result/'.$response['response_code'].'/'.$response['TransactionId'].'/1');
    }
}