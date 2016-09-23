<?php
	require(__DIR__ . '/../semite_autoload.php');

	try
	{
		$client = new Semite_Client(Semite_Client::ENV_TEST);

		$operation = new Semite_ThreeDSecureOperations_CheckEnrollment;
		$operation->setMember('7CF13CE63', '2FF9618E-A98E-5DDD-8BCB-DF4C6ABD46A7');
		$operation->setCountryCode('SRB');

		$operation->setCardNumberAndHolder('4000000000000002');
		$operation->setCardExpiry('09', '2019');
        $operation->setCardValidationCode('123');

		$operation->setAmountAndCurrencyId(6.99, 'USD');
        $operation->setProcessor(30);
        $operation->setUserAgent($_SERVER['HTTP_USER_AGENT']);

		$operation->setTrackingMemberCode('CheckEnrollment ' . date('His dmY'));

		if ($client->call($operation))
		{
//            echo '<pre>';
//			print_r(
//                array(
////				$operation->getResultTrackingMemberCode(),
//                $operation->getResultCmpiLookupMessage(),
////				$operation->getResultEnrollmentId(),
////				$operation->getResultIssuerUrl(),
////				$operation->getResultPaymentAuthenticationRequest()
//            )
//        );

            if( (strcasecmp('Y', $operation->getResultCmpiLookupMessage()->Enrolled) == 0) && (strcasecmp('0', $operation->getResultCmpiLookupMessage()->ErrorNo) == 0) ) {
                // Proceed with redirect
                echo $operation->getRedirectHtmlForm('http://map.opengateway.io/test/manual/threedsecureoperations-paymentusingintegratedmpi.php?enrollmentId='.$operation->getResultEnrollmentId().'&OrderId='.$operation->getResultCmpiLookupMessage()->OrderId);

            } else if( (strcasecmp('N', $operation->getResultCmpiLookupMessage()->Enrolled) == 0) && (strcasecmp('0', $operation->getResultCmpiLookupMessage()->ErrorNo) == 0) ) {
                // Card not enrolled, continue to authorization
                exit('Do Authorize');

            } else if( (strcasecmp('U', $operation->getResultCmpiLookupMessage()->Enrolled) == 0) && (strcasecmp('0', $operation->getResultCmpiLookupMessage()->ErrorNo) == 0) ) {
                // Authentication unavailable, continue to authorization
                exit('Do Authorize');
            } else {
                // Authentication unable to complete, continue to authorization
                var_dump($operation->getResultCmpiLookupMessage());
                exit('Do Authorize');

            } // end processing logic
		}
	}
	catch (Semite_Exception $e)
	{
		echo $e->getMessage();
	}