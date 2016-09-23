<?php
//Drop-in replacement for PHP's SoapClient class supporting connect and response/transfer timeout
//Usage: Exactly as PHP's SoapClient class, except that 3 new options are available:
//	timeout			The response/transfer timeout in milliseconds; 0 == default SoapClient / CURL timeout
//	connecttimeout	The connection timeout; 0 == default SoapClient / CURL timeout
//	sslverifypeer	FALSE to stop SoapClient from verifying the peer's certificate
class Soapclienttimeout extends SoapClient
{
    private $timeout = 0;
    private $connecttimeout = 0;
    private $sslverifypeer = true;

    public function __construct($wsdl, $options) {
        //"POP" our own defined options from the $options array before we call our parent constructor
        //to ensure we don't pass unknown/invalid options to our parent
        if (isset($options['timeout'])) {
            $this->__setTimeout($options['timeout']);
            unset($options['timeout']);
        }
        if (isset($options['connecttimeout'])) {
            $this->__setConnectTimeout($options['connecttimeout']);
            unset($options['connecttimeout']);
        }
        if (isset($options['sslverifypeer'])) {
            $this->__setSSLVerifyPeer($options['sslverifypeer']);
            unset($options['sslverifypeer']);
        }
        //Now call parent constructor
        parent::__construct($wsdl, $options);
    }

    public function __setTimeout($timeoutms)
    {
        if (!is_int($timeoutms) && !is_null($timeoutms) || $timeoutms<0)
            throw new Exception("Invalid timeout value");

        $this->timeout = $timeoutms;
    }

    public function __getTimeout()
    {
        return $this->timeout;
    }

    public function __setConnectTimeout($connecttimeoutms)
    {
        if (!is_int($connecttimeoutms) && !is_null($connecttimeoutms) || $connecttimeoutms<0)
            throw new Exception("Invalid connecttimeout value");

        $this->connecttimeout = $connecttimeoutms;
    }

    public function __getConnectTimeout()
    {
        return $this->connecttimeout;
    }

    public function __setSSLVerifyPeer($sslverifypeer)
    {
        if (!is_bool($sslverifypeer))
            throw new Exception("Invalid sslverifypeer value");

        $this->sslverifypeer = $sslverifypeer;
    }

    public function __getSSLVerifyPeer()
    {
        return $this->sslverifypeer;
    }

    public function __doRequest($request, $location, $action, $version, $one_way = FALSE)
    {
        if (($this->timeout===0) && ($this->connecttimeout===0))
        {
            // Call via parent because we require no timeout
            $response = parent::__doRequest($request, $location, $action, $version, $one_way);
        }
        else
        {
            // Call via Curl and use the timeout
            $curl = curl_init($location);
            if ($curl === false)
                throw new Exception('Curl initialisation failed');

            $options = array(
                CURLOPT_VERBOSE => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $request,
                CURLOPT_HEADER => false,
                CURLOPT_NOSIGNAL => true,	//http://www.php.net/manual/en/function.curl-setopt.php#104597
                CURLOPT_HTTPHEADER => array(sprintf('Content-Type: %s', $version == 2 ? 'application/soap+xml' : 'text/xml'), sprintf('SOAPAction: %s', $action)),
                CURLOPT_SSL_VERIFYPEER => $this->sslverifypeer
            );

            if ($this->timeout>0) {
                if (defined('CURLOPT_TIMEOUT_MS')) {	//Timeout in MS supported?
                    $options[CURLOPT_TIMEOUT_MS] = $this->timeout;
                } else	{ //Round(up) to second precision
                    $options[CURLOPT_TIMEOUT] = ceil($this->timeout/1000);
                }
            }
            if ($this->connecttimeout>0) {
                if (defined('CURLOPT_CONNECTTIMEOUT_MS')) {	//ConnectTimeout in MS supported?
                    $options[CURLOPT_CONNECTTIMEOUT_MS] = $this->connecttimeout;
                } else { //Round(up) to second precision
                    $options[CURLOPT_CONNECTTIMEOUT] = ceil($this->connecttimeout/1000);
                }
            }

            if (curl_setopt_array($curl, $options) === false)
                throw new Exception('Failed setting CURL options');

            $response = curl_exec($curl);

            if (curl_errno($curl))
                throw new Exception(curl_error($curl));

            curl_close($curl);
        }

        // Return?
        if (!$one_way)
            return ($response);
    }
}