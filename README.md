Very simple [SecurePay.com.au](http://www.securepay.com.au) API for PHP.

The secure pay service is a reasonably reliable and well documented credit card gateway.

You will require an existing username and password (or a test account) to use this module.


Installation
============
Download this GIT repository and copy into your application directory.

Alternatively, install with [Composer](http://getcomposer.org).


Examples
========

Full Example
------------
	<?php
	include('securepay.php');

	$sp = new SecurePay('username','password');
	$sp->TestMode(); // Remove this line to actually preform a transaction

	$sp->TestConnection();
	print_r($sp->ResponseXml);

	$sp->Cc = 462834666666;
	$sp->ExpiryDate = '07/09';
	$sp->ChargeAmount = 123;
	$sp->ChargeCurrency = 'USD';
	$sp->Cvv = 321;
	$sp->OrderId = 'ORD34234';
	if ($sp->Valid()) { // Is the above data valid?
		$response = $sp->Process();
		if ($response == SECUREPAY_STATUS_APPROVED) {
			echo "Transaction was a success\n";
		} else {
			echo "Transaction failed with the error code: $response\n";
			echo "XML Dump: " . print_r($sp->ResponseXml,1) . "\n";
		}
	} else {
		die("Your data is invalid\n");
	}
	?>

Global
------
The following examples all the basic PHP setup to kick things off.
Obviously we need to load the library first:

	<?php
	include('securepay.php');


Create a new SecurePay object
-------------------------------------------
	$sp = new SecurePay('username','password');
	
OR

	$sp = new SecurePay();
	$sp->Login('username','password');


Test the connection to the server
--------------------------------------
	if ($sp->TestConnection()) {
		echo "Server is working\n";
	} else {
		echo "Server is Down\n";
	}


Enable Test Mode (Optional)
----------------------------------
	$sp->TestMode();

OR

	$sp->TestMode(TRUE);


Check if all provided Data is valid (Quick Method)
--------------------------------------------------
	if ($sp->Valid()) {
		echo "Everything is valid\n";
	} else {
		echo "Something is wrong\n";
	}

OR you can break each validity test down individually

	if (!$sp->ValidCc()) {
		echo "Credit Card Number is invalid\n";
	} elseif (!$sp->ValidExpiryDate()) {
		echo "Expiry Date is invalid\n";
	} elseif (!$sp->ValidCvv()) {
		echo "CVV is invalid\n";
	} elseif (!$sp->ValidChargeAmount()) {
		echo "Charge Amount is invalid\n";
	} elseif (!$sp->ValidChargeCurrency()) {
		echo "Charge Currency is invalid\n";
	} elseif (!$sp->ValidOrderId()) {
		echo "Order ID is invalid\n";
	} else {
		echo "All data is valid\n";
	}



Process a payment
-----------------
	// Charge the credit card '462834666666' '$123' US dollars. The card expires on '07/09', has the CVV code '32' and the local order ID is 'ORD34234'.
	$sp->Process(123, 'USD', '462834666666', '07/09', '321', 'ORD34234');

OR

	// Charge the credit card '462834666666' '$123' US dollars. The card expires on '07/09', has the CVV code '32' and the local order ID is 'ORD34234'.
	$sp->Cc = 462834666666;
	$sp->ExpiryDate = '07/09';
	$sp->ChargeAmount = 123;
	$sp->ChargeCurrency = 'USD';
	$sp->Cvv = 321;
	$sp->OrderId = 'ORD34234';
	$sp->Process();


Pre-authorize a payment
-----------------------
	// This is the same process as passing a regular payment but the last parameter indicates that it should be treated as a PreAuth transaction
	$sp->Process(123, 'USD', '462834666666', '07/09', '321', 'ORD34234', TRUE);

OR

	// Exactly the same as a regular charge but with PreAuth = 1
	$sp->PreAuth = 1;
	$sp->Cc = 462834666666;
	$sp->ExpiryDate = '07/09';
	$sp->ChargeAmount = 123;
	$sp->ChargeCurrency = 'USD';
	$sp->Cvv = 321;
	$sp->OrderId = 'ORD34234';
	$sp->Process();
	$preauthid = $sp->PreAuthId; // $preauthid contains the ID used by SecurePay (use this when finalizing a PreAuth)


Charge a PreAuth payment
------------------------
	$sp->PreAuth = 1;
	$sp->PreAuthID = $preauthid; // The ID we got in the above process
	$sp->ChargeAmount = 123;
	$sp->ChargeCurrency = 'USD';
	$sp->OrderId = 'ORD34234'; // NOTE this must match the OrderID used in the above process
	$sp->Process();


Setup a repeating payment
-------------------------
	// Charge the credit card '462834666666' '$123' US dollars every month for 6 months.
	$sp->Cc = 462834666666;
	$sp->ExpiryDate = '07/09';
	$sp->ChargeAmount = 123;
	$sp->ChargeCurrency = 'USD';
	$sp->Cvv = 321;
	$sp->OrderId = 'ORD34234';
	$sp->SetupRepeat('monthly', 6); // Second arg is the interval between months
	$sp->Process();

OR

	// Charge the credit card '462834666666' '$123' US dollars every week for 6 weeks starting on 01/01/10
	$sp->Cc = 462834666666;
	$sp->ExpiryDate = '07/09';
	$sp->ChargeAmount = 123;
	$sp->ChargeCurrency = 'USD';
	$sp->Cvv = 321;
	$sp->OrderId = 'ORD34234';
	$sp->SetupRepeat(SECUREPAY_REPEAT_WEEKLY, 6);
	$sp->RepeatStart = strtotime('01/01/10');
	$sp->Process();

OR

	// Charge the credit card '462834666666' '$123' US dollars every two days for 60 days starting on 01/01/10
	$sp->Cc = 462834666666;
	$sp->ExpiryDate = '07/09';
	$sp->ChargeAmount = 123;
	$sp->ChargeCurrency = 'USD';
	$sp->Cvv = 321;
	$sp->OrderId = 'ORD34234';
	$sp->Repeat = SECUREPAY_REPEAT_DAILY;
	$sp->RepeatInterval = 2;
	$sp->RepeatCount = 60;
	$sp->RepeatStart = strtotime('01/01/10');
	$sp->Process();


Setup a repeating payment with a manual trigger
-----------------------------------------------
	// Charge the credit card '462834666666' '$123' US dollars every week for 6 weeks starting on 01/01/10
	// Then manually trigger the response
	$sp->Cc = 462834666666;
	$sp->ExpiryDate = '07/09';
	$sp->ChargeAmount = 123;
	$sp->ChargeCurrency = 'USD';
	$sp->Cvv = 321;
	$sp->OrderId = 'ORD34234';
	$sp->SetupRepeat(SECUREPAY_REPEAT_WEEKLY, 6);
	$sp->RepeatStart = strtotime('01/01/10');
	$sp->RepeatTrigger = FALSE;
	$sp->Process();
	// ... The repeat payment is now setup
	$sp->Trigger(); // Now start it


Refund a Transaction
--------------------
	//We are assuming that we have previously charged a credit card with the OrderId `ABC123` and the TransactionId was `123456`
	$sp->TransactionId = 123456;
	$sp->ChargeAmount = 123; //Must be less or equal to the transaction amount
	$sp->OrderId = 'ABC123'; //Must be the same as the original transaction

	$sp->Refund(); //Refund it!


Retrieve all client information
-------------------------------
	$sp->WebLogin('username','admin','password'); // Web login details
	$details = $sp->GetClientInfo();


Retrieve a specific clients information
---------------------------------------
	$sp->WebLogin('username','admin','password'); // Web login details
	$details = $sp->GetClientInfo('a_client_id');


Retrieve a list of transactions (today)
---------------------------------------
	$sp->WebLogin('username','admin','password');
	print_r($sp->GetTransactions());

OR for yesterday:

	print_r($sp->GetTransactions('yesterday'));

OR for any date range:

	$sp->WebLogin('username','admin','password');
	print_r($sp->GetTransactions(strtotime('1/1/2008'), strtotime('30/1/2008'))); // Retrieves all transactions between 1/1/2008 and 30/1/2008


Thanks
======
Thanks to Chris Bosdriesz for pointing out that CVV is optional with some cards.
