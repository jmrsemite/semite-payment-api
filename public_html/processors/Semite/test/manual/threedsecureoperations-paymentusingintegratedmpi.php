<?php
	require(__DIR__ . '/../semite_autoload.php');


	try
	{
		$client = new Semite_Client(Semite_Client::ENV_TEST);

		$payment = new Semite_ThreeDSecureOperations_PaymentUsingIntegratedMPI;
		$payment->setMember('7CF13CE63', '2FF9618E-A98E-5DDD-8BCB-DF4C6ABD46A7');
		$payment->setCountryCode('SRB');
        $payment->setProcessor(30);

		$payment->setTrackingMemberCode('PaymentUsingIntegratedMPI ' . date('His dmY'));

		$payment->setCardValidationCode('323');

		// ID returned in the Enrollment check: $operation->getResultEnrollmentId()
		$payment->setEnrollmentId($_GET['enrollmentId']);

		// Tracking Member Code used in the Enrollment check
		$payment->setEnrollmentTrackingMemberCode($_GET['OrderId']);

		// Provided in redirection after 3D secure check: $_POST['PaRes']
		$payment->setPayerAuthenticationResponse($_POST['PaRes']);

		$client->call($payment);

        echo '<pre>';
        var_dump(
            $payment->getResultMessage(),
            $payment->getResultCMpiMessageResp()
        );

	}
	catch (Semite_Exception $e)
	{
		echo $e->getMessage();
	}