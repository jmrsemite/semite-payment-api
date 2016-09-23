<?php
require(__DIR__ . '/../semite_autoload.php');

try
{
    $client = new Semite_Client(Semite_Client::ENV_TEST);

    $operation = new Semite_BasicOperations_Void();
    $operation->setMember('7CF13CE63', '2FF9618E-A98E-5DDD-8BCB-DF4C6ABD46A7');

    $operation->setTransactionId('1404');
    $operation->setProcessor(30);

    $operation->setTrackingMemberCode('Void ' . date('His dmY'));

    $client->call($operation);

    var_dump(
        $operation->getResultState(),
        $operation->getResultCode(),
        $operation->getResultMessage(),
        $operation->getResultTransactionId(),
        $operation->getResultAuthorizationId(),
        $operation->getResultTrackingMemberCode()
    );
}
catch (Semite_Exception $e)
{
    echo $e->getMessage();
}