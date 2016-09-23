<?php
	require(__DIR__ . '/../semite_autoload.php');

	try
	{
		$client = new Semite_Client(Semite_Client::ENV_TEST);

		$payment = new Semite_BasicOperations_Payment;
		$payment->setMember('7CF13CE63', '2FF9618E-A98E-5DDD-8BCB-DF4C6ABD46A7');
		$payment->setCountryCode('NLD');

		$payment->setCardNumberAndHolder('4111111111111111');
		$payment->setCardExpiry('10', '2017');
		$payment->setCardValidationCode('123');

		$payment->setAmountAndCurrencyId(3.99, 'EUR');
        $payment->setProcessor(30);

		$payment->setTrackingMemberCode('Payment ' . date('His dmY'));
        $payment->setMerchantAccountType(1);

		$client->call($payment);

		var_dump(
			$payment->getResultState(),
			$payment->getResultCode(),
			$payment->getResultMessage(),
			$payment->getResultTransactionId(),
			$payment->getResultAuthorizationId(),
			$payment->getResultTrackingMemberCode()
		);
	}
	catch (Semite_Exception $e)
	{
		echo $e->getMessage();
	}