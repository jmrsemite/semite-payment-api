<?php
require(__DIR__ . '/../semite_autoload.php');

try
{
    $client = new Semite_Client(Semite_Client::ENV_TEST);

    $authorize = new Semite_BasicOperations_Authorize();
    $authorize->setMember('7CF13CE63', '2FF9618E-A98E-5DDD-8BCB-DF4C6ABD46A7');
    $authorize->setCountryCode('NLD');

    $authorize->setCardNumberAndHolder('4775889400000171');
    $authorize->setCardExpiry('12', '2018');
    $authorize->setCardValidationCode('313');

    $authorize->setAmountAndCurrencyId(3, 'EUR');
    $authorize->setProcessor(30);

    $authorize->setTrackingMemberCode('Authorize ' . date('His dmY'));
    $authorize->setMerchantAccountType(1);

    $client->call($authorize);

    var_dump(
        $authorize->getResultState(),
        $authorize->getResultCode(),
        $authorize->getResultMessage(),
        $authorize->getResultTransactionId(),
        $authorize->getResultAuthorizationId(),
        $authorize->getResultTrackingMemberCode()
    );
}
catch (Semite_Exception $e)
{
    echo $e->getMessage();
}