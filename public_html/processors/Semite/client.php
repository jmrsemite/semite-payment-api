<?php

class Semite_Client
{
	const ENV_TEST = 'test';
	const ENV_LIVE = 'live';

	protected $_environment    = NULL;

    protected $_ca_certificates_file = null;

	protected $_processor_host = array(
			'test' => 'http://processor.crm.io/',
		);

	public function __construct ($environment = self::ENV_LIVE)
	{
		$this->_environment = $environment;
        $this->xml_to_array = new ArrayToXML();

		if ( ! function_exists('curl_init'))
		{
			throw new Semite_Exception('cURL library is required to run this library');
		}
	}

    public function setCaCertificatesFile ($file)
    {
        if (file_exists($file))
        {
            $this->_ca_certificates_file = $file;
        }

        return false;
    }

	public function call (Semite_Operation $operation)
	{
		if ($operation->checkRequiredData() !== TRUE)
		{
			throw new Semite_Exception('Not all required fields are filled');
		}

		$response = $this->_performRequest($operation);

		// XML to Object
		try
		{
			$simplexml = new SimpleXMLElement($response);
		}
		catch (Exception $e)
		{
			throw new Semite_Exception('Result from Semite is not XML (XML parsing failed: ' . $e->getMessage() . ')');
		}

		try
		{
			$operation->processXmlResult($simplexml);
		}
		catch (Semite_Exception $e)
		{
			throw new Semite_Exception('Unable to process XML data in ' . get_class($operation) . ' (' . $e->getMessage() . ')');
		}

		return TRUE;
	}

	protected function _performRequest (Semite_Operation $operation)
	{

		$url  = $this->_processor_host[$this->_environment] .
			'v2/gateway/';

        $post_string = str_replace('ResultSet','request',$this->xml_to_array->toXML($operation->getDataAsArray()));

        $postfields = $post_string;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);


        return $data = curl_exec($ch);



	}
}