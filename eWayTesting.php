<?php

// Get curl version array
//$version = curl_version();
//echo "CURL Info:";
//echo "<pre>";
//print_r($version);


//echo "PHP info:";
//phpinfo();
//echo "</pre>";

// These are the bitfields that can be used
// to check for features in the curl build
//$bitfields = Array(
//            'CURL_VERSION_IPV6',
//            'CURL_VERSION_KERBEROS4',
//            'CURL_VERSION_SSL',
//            'CURL_VERSION_LIBZ'
//            );


//foreach($bitfields as $feature)
//{
//    echo $feature . ($version['features'] & constant($feature) ? ' matches' : ' does not match');
//    echo PHP_EOL;
//}

//exit();

require_once( 'EwayPayment.php' );

//$eway = new EwayPayment( '16927258', 'https://www.eway.com.au/gateway/xmlpayment.asp' );
$eway = new EwayPayment( '87654321', 'https://www.eway.com.au/gateway/xmltest/testpage.asp' );

//Substitute 'FirstName', 'Lastname' etc for $_POST["FieldName"] where FieldName is the name of your INPUT field on your webpage
$eway->setCustomerFirstname( 'Firstname' );
$eway->setCustomerLastname( 'Lastname' );
$eway->setCustomerEmail( 'cdesai@day3.com.au' );
$eway->setCustomerAddress( '123 Someplace Street, Somewhere ACT' );
$eway->setCustomerPostcode( '2609' );
$eway->setCustomerInvoiceDescription( 'Testing' );
$eway->setCustomerInvoiceRef( 'INV120394' );
$eway->setCardHoldersName( 'John Smith' );
$eway->setCardNumber( '4444333322221111' );
//$eway->setCardNumber( '4111111111111111' );
$eway->setCardExpiryMonth( '12' );
$eway->setCardExpiryYear( '15' );
$eway->setTrxnNumber( '4230' );
$eway->setTotalAmount( 100 );
$eway->setCVN( '' );
$eway->setOption1("option1");
$eway->setOption2("option2");
$eway->setOption3("option3");


$result = $eway->doPayment();

echo $result;

if( $result == EWAY_TRANSACTION_OK ) {
    echo "Transaction Successful: ". $eway->getTrxnStatus()."</br>";
    echo "Transaction Number: " . $eway->getTrxnNumber()."</br>";
    echo "Transaction Reference: " . $eway->getTrxnReference()."</br>";
    echo "Return Amount: " . $eway->getReturnAmount()."</br>";
    echo "Auth Code: " . $eway->getAuthCode()."</br>";
    echo "Option1: " . $eway->getTrxnOption1()."</br>";
    echo "Option2: " . $eway->getTrxnOption2()."</br>";
    echo "Option3: " . $eway->getTrxnOption3()."</br>";
} else {
    echo "Error occurred (".$eway->getError()."): " . $eway->getErrorMessage();
echo "Transaction Successful: ". $eway->getTrxnStatus()."</br>";
    echo "Transaction Number: " . $eway->getTrxnNumber()."</br>";
    echo "Transaction Reference: " . $eway->getTrxnReference()."</br>";
    echo "Return Amount: " . $eway->getReturnAmount()."</br>";
    echo "Auth Code: " . $eway->getAuthCode()."</br>";
    echo "Option1: " . $eway->getTrxnOption1()."</br>";
    echo "Option2: " . $eway->getTrxnOption2()."</br>";
    echo "Option3: " . $eway->getTrxnOption3()."</br>";
}
?>
