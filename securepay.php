<?php
define('SECUREPAY_CURRENCIES','USD,AUD,CAD,CHF,DEM,EUR,FRF,GBP,GRD,HKD,ITL,JPY,NZD,SGD'); // CSV of valid SecurePay currencies
define('SECUREPAY_STATUS_UNKNOWN', 0);
define('SECUREPAY_STATUS_OK', 1);
define('SECUREPAY_STATUS_INVALID_USER', 2);
define('SECUREPAY_STATUS_INVALID_PASS', 3);
define('SECUREPAY_STATUS_INVALID_URL', 4);
define('SECUREPAY_STATUS_SERVER_DOWN', 5);
define('SECUREPAY_STATUS_TIMEOUT', 6);
define('SECUREPAY_STATUS_SERVER_ERR', 7);
define('SECUREPAY_STATUS_XML_ERR', 8);
define('SECUREPAY_STATUS_CONNECTION_ERR', 9);
define('SECUREPAY_STATUS_APPROVED', 100);
define('SECUREPAY_STATUS_DECLINED', 101);

define('SECUREPAY_REPEAT_NEVER',-1);
define('SECUREPAY_REPEAT_DAILY',0);
define('SECUREPAY_REPEAT_WEEKLY',1);
define('SECUREPAY_REPEAT_FORTNIGHTLY',2);
define('SECUREPAY_REPEAT_MONTHLY',3);
define('SECUREPAY_REPEAT_QUARTERLY',4);
define('SECUREPAY_REPEAT_HALF_YEARLY',5);
define('SECUREPAY_REPEAT_YEARLY',6);

/**
* SecurePay Credit-Card charging, pre-auth and periodic charging class
* @access public
* @author Matt Carter <m@ttcarter.com>
*/
class SecurePay {
	// Variable Declarations {{{

	/**
	* The account name registed with Securepay.com.au
	* @see Login()
	* @access public
	* @var string
	*/
	public $AccountName;

	/**
	* The account password for the $AccountName
	* @see Login()
	* @see $AccountName
	* @access public
	* @var string
	*/
	public $AccountPassword;

	/**
	* The seperate account password for the $AccountName when in test mode
	* If this is blank and we are in test mode the $AccountPassword is assumed instead
	* @see Login()
	* @see $AccountName
	* @access public
	* @var string
	*/
	public $TestAccountPassword;

	/**
	* The last status code returned by Process()
	* This code (since its a copy of the return of the Process() function)
	* @see Process()
	* @access Public
	* @var int
	*/
	public $Status;

	/**
	* The last error to occur.
	* This can be:
	* * A validation error (e.g. 'Your Credit Card Number is Invalid')
	* * Processing error (e.g. 'You're credit card details were declined')
	*
	* Use this variable as the error message if Valid() or Process() fails
	* @access Public
	* @var string
	*/
	Public $Error;

	/**
	* The merchant name for web logins
	* @see WebLogin()
	* @access Public
	* @var string
	*/
	public $WebMerchantName;

	/**
	* The merchant username for web logins
	* @see WebLogin()
	* @access Public
	* @var string
	*/
	public $WebUserName;

	/**
	* The merchant password for web logins
	* @see WebLogin()
	* @access Public
	* @var string
	*/
	public $WebUserPassword;

	/**
	* The cookie jar used while using the web interface
	* If this is null it can be assumed that we have not yet logged in
	* @access public
	* @see WebSignin()
	* @var string
	*/
	public $WebCookieJar;

	/**
	* The web based merchant ID. Retrieved when requesting reports for the first time.
	* @access public
	* @var string
	*/
	public $WebMerchantId;

	/**
	* Indicates that the class should operate in test mode
	* @access public
	* @var bool
	*/
	public $TestMode;


	/**
	* Indicates if the transaction to be processed is actually a PreAuth rather than a normal charge
	* @access public
	* @var bool
	*/
	public $PreAuth;

	/**
	* The credit card number to work with
	* @access public
	* @var int|string
	*/
	public $Cc;

	/**
	* Short string to denote the expiry date of the credit card
	* This is in the form: 'mm/yy' where the first two are a zero padded month and zero padded year
	* @access public
	* @var char(5)
	*/
	public $ExpiryDate;

	/**
	* The Credit Card Verification number / CV2
	* This is optional as to whether the card type supports it
	* @var char(3)
	* @access public
	*/
	public $Cvv;

	/**
	* The amount (as a float) that should be charged to the card
	* This is listed in the currency specified in the $ChargeCurrency Variable
	* @access public
	* @var float
	* @see $ChargeCurrency
	*/
	public $ChargeAmount;

	/**
	* The currency code relevent to the $ChargeAmount
	* @access public
	* @see $ChargeAmount
	* @var char(3)
	*/
	public $ChargeCurrency;

	/**
	* Unique order number to identify this transaction.
	* This is typically an invoice number
	* Must be at least 1 characters
	* Must be at most 60 characters
	* @access public
	* @var string
	*/
	public $OrderId;

	/**
	* Last message ID requested
	* This code is only really useful for debugging with Securepay.com.au who track these codes
	* @access public
	* @var string
	*/
	public $LastMesageId;

	/**
	* Preauth ID if we are putting though a PreAuth payment (i.e. last call to Process() had $this->PreAuth == True)
	* If a pre-auth is sent though before an actual transaction the preauth code is stored here to reserve the transaction for the next payment
	* @access public
	* @var string
	*/
	public $PreAuthId;

	/**
	 * Last Transaction ID if successful
	 * If a transaction is successful, the transaction ID will be stored here for you to easily retrieve
	 * @access public
	 * @var int
	 * @since 2014-02-13
	 */
	public $TransactionId;

	/**
	* The last dispatched request
	* @access public
	* @var string
	*/
	public $RequestXml;

	/**
	* The XML returned by the server in response to RequestXml
	* @access public
	* @see RequestXml
	* @var array
	*/
	public $ResponseXml;

	/**
	* The XML tree provided from the last transaction
	* @access public
	* @var array
	*/
	public $ResponseTree;

	/**
	* Securepay status code from the last transaction
	* @access public
	* @var int
	*/
	public $StatusCode;

	/**
	* Securepay status code text from the last transaction
	* This is the english equivelent to the above $StatusCode value
	* @access public
	* @var string
	* @see StatusCode
	*/
	public $StatusCodeText;

	/**
	* Securepay response code from the last transaction
	* @access public
	* @var int
	*/
	public $ResponseCode;

	/**
	* Securepay response code text from the last transaction
	* This is the english equivelent to the above $ResponseCode value
	* @access public
	* @var string
	* @see ResponseCode
	*/
	public $ResponseCodeText;

	/**
	* Whether to repeat the transaction as a periodic or once-off payment
	* See the 'SECUREPAY_REPEAT_*' constants for a list of values
	* Default value is SECUREPAY_REPEAT_NEVER
	* @access public
	* @var bool
	*/
	public $Repeat;

	/**
	* If $Repeat=SECUREPAY_REPEAT_DAILY this represents how many days should elapase before the next charge
	* @access public
	* @var int
	*/
	public $RepeatInterval;

	/**
	* Epoc timstamp on when to start the repeat payments
	* @access public
	* @var int
	*/
	public $RepeatStart;

	/**
	* Number of repeating payments to max out on
	* Set to 'zero' for never ending periodic charge
	* @access public
	* @var int
	*/
	public $RepeatCount;

	/**
	* Automatically trigger the repeat payment after processing.
	* The default is TRUE
	* If this is disabled a manual trigger request will need to be made when putting though a repeat payment request
	* @access public
	* @var bool
	*/
	public $RepeatTrigger;

	// End of Variable Declarations }}}

	// General use functionality {{{

	/**
	* Constructor function.
	* This can optionally be passed the account_name and account_password variables.
	* @see Login()
	* @return void
	*/
	function __construct($AccountName = null, $AccountPassword = null, $TestMode = FALSE) {
		if ($AccountName && $AccountPassword)
			$this->Login($AccountName, $AccountPassword, $TestMode);
		$this->ChargeCurrency = 'USD'; // Default currency to USD
		$this->Repeat = SECUREPAY_REPEAT_NEVER;
		$this->RepeatTrigger = TRUE;
	}

	/**
	* Shorthand function to set the account_name and account_password variables
	* @param string $AccountName The account name to use for payments (provided by SecurePay)
	* @param string $AccountPassword The account password to use for payments (provided by SecurePay)
	* @param bool $TestMode Whether to use TestMode with all transactions
	* @see SecurePay()
	* @see $AccountName
	* @see $AccountPassword
	* @return void
	*/
	function Login($AccountName, $AccountPassword, $TestMode = FALSE) {
		$this->AccountName = $AccountName;
		$this->AccountPassword = $AccountPassword;
		$this->TestMode = $TestMode;
	}

	/**
	* Login to the main website login page. This is used when retrieving periodic payment information
	*/
	function WebLogin($WebMerchantName, $WebUserName, $WebUserPassword) {
		$this->WebMerchantName = $WebMerchantName;
		$this->WebUserName = $WebUserName;
		$this->WebUserPassword = $WebUserPassword;
	}

	/**
	* Enable or disable the testing suite
	* When TestMode is enabled all functionality uses the test SecureXML API servers instead
	* @param bool $TestMode Optional indicator as to whether test mode is enabled or not. If omitted TestMode is enabled
	*/
	function TestMode($TestMode = TRUE) {
		$this->TestMode = $TestMode;
	}

	/**
	* Shorthand function to set all relevent fields and process a payment
	* If any field is supplied it overrides the existing model function if not or no parameters are specified the transaction is just dispatched
	* @param float $ChargeAmount Optional amount that is to be deducted from the given Credit Card (e.g. '4.12' => '$4.12')
	* @param string $ChargeCurrency Optional currency that the above $ChargeAmount is specified in (e.g. 'USD')
	* @param string $Cc Optional Credit Card number to use
	* @param string $ExpiryDate Optional expiry date of the Credit Card (e.g. '07/09' => 'July 2009')
	* @param string $Cvv Optional CVV code (if the card has one)
	* @param string $OrderId Optional local order ID reference to use for this transaction. This must be unique
	* @param bool $PreAuth Indicate that this transaction is a preauth
	* @return int Returns the corresponding SECUREPAY_STATUS_* code
	*/
	function Process($ChargeAmount = null, $ChargeCurrency = null, $Cc = null, $ExpiryDate = null, $Cvv = null, $OrderId = null, $PreAuth = FALSE) {
		// Set class variables from function call for later use {{{
		if ($ChargeAmount) $this->ChargeAmount = $ChargeAmount;
		if ($ChargeCurrency) $this->ChargeCurrency = $ChargeCurrency;
		if ($Cc) $this->Cc = $Cc;
		if ($ExpiryDate) $this->ExpiryDate = $ExpiryDate;
		if ($Cvv) $this->Cvv = $Cvv;
		if ($OrderId) $this->OrderId = $OrderId;
		if ($PreAuth) $this->PreAuth = $PreAuth;
		// }}}
		$this->ValidExpiryDate(); // Reformat the expiry date if necessary
		if ($this->Cc)
			$this->Cvv = str_pad($this->Cvv, 3, '0', STR_PAD_LEFT);
		$this->RequestXml = $this->_ComposePayment();
		$this->ResponseXml = $this->_Dispatch($this->RequestXml);
		$this->ResponseTree = simplexml_load_string($this->ResponseXml);
		$this->StatusCode = $this->ResponseTree->Status->statusCode;
		$this->StatusCodeText = $this->ResponseTree->Status->statusDescription;
		$server_code = $this->_TranslateServerCode($this->StatusCode);
		if (isset($this->ResponseTree->Payment->TxnList->Txn->responseCode)) { // Has a response code
			$this->ResponseCode = $this->ResponseTree->Payment->TxnList->Txn->responseCode;
			$this->ResponseCodeText = $this->ResponseTree->Payment->TxnList->Txn->responseText;
			if ($this->PreAuth) // Was requesting a PreAuth...
				$this->PreAuthId = $this->ResponseTree->Payment->TxnList->Txn->preauthID; // Store the PreAuth return code in $this->PreAuth
			$result = $this->_TranslateResponseCode($this->ResponseCode);
			if ($result == SECUREPAY_STATUS_APPROVED && !empty($this->ResponseTree->Payment->TxnList->Txn->txnID))
            	$this->TransactionId = (string) $this->ResponseTree->Payment->TxnList->Txn->txnID;

        } else if(isset($this->ResponseTree->Periodic->PeriodicList->PeriodicItem->responseCode)) { // Has a response code - periodic style
            $this->ResponseCode = $this->ResponseTree->Periodic->PeriodicList->PeriodicItem->responseCode;
            $this->ResponseCodeText = $this->ResponseTree->Periodic->PeriodicList->PeriodicItem->responseText;
            if ($this->PreAuth) // Was requesting a PreAuth...
                $this->PreAuthId = $this->ResponseTree->Payment->TxnList->Txn->preauthID; // Store the PreAuth return code in $this->PreAuth
            $result = $this->_TranslateResponseCode($this->ResponseCode);

		} else { // No success with the response code - return the server code error
			$result = $server_code;
		}
		if ($this->IsRepeat() && $this->RepeatTrigger) // Automatically trigger the response
			$this->Trigger();
		return $this->Status = $result;
	}

	/**
	 * Refunds a Transaction on Secure Pay
	 * If any field is supplied it overrides the existing model function if not or no parameters are specified the transaction is just dispatched
	 * @param string $TransactionId The TransactionId as provided by SecurePay
	 * @param string $OrderId       The order ID you have this transaction. Must match the original transaction
	 * @param float  $ChargeAmount  The amount to refund, up-to the total original transaction amount
	 * @return int 	 Returns the corresponding SECUREPAY_STATUS_* code
	 * @author Phil Hawthorne <me@philhawthorne.com>
	 * @since  2014-02-13
	 */
	function Refund($TransactionId = null,$OrderId = null,$ChargeAmount=null){
		// Set class variables from function call for later use {{{
		if ($TransactionId) $this->TransactionId = $TransactionId;
		if ($ChargeAmount) $this->ChargeAmount = $ChargeAmount;
		if ($OrderId) $this->OrderId = $OrderId;
		// }}}

		$this->RequestXml = $this->_ComposeRefund();
		$this->ResponseXml = $this->_Dispatch($this->RequestXml);
		$this->ResponseTree = simplexml_load_string($this->ResponseXml);
		$this->StatusCode = $this->ResponseTree->Status->statusCode;
		$this->StatusCodeText = $this->ResponseTree->Status->statusDescription;
		$server_code = $this->_TranslateServerCode($this->StatusCode);
		if (isset($this->ResponseTree->Payment->TxnList->Txn->responseCode)) { // Has a response code
			$this->ResponseCode = $this->ResponseTree->Payment->TxnList->Txn->responseCode;
			$this->ResponseCodeText = $this->ResponseTree->Payment->TxnList->Txn->responseText;
			if ($this->PreAuth) // Was requesting a PreAuth...
				$this->PreAuthId = $this->ResponseTree->Payment->TxnList->Txn->preauthID; // Store the PreAuth return code in $this->PreAuth
			$result = $this->_TranslateResponseCode($this->ResponseCode);
			if ($result == SECUREPAY_STATUS_APPROVED && !empty($this->ResponseTree->Payment->TxnList->Txn->txnID))
            	$this->TransactionId = (string) $this->ResponseTree->Payment->TxnList->Txn->txnID;

        } else { // No success with the response code - return the server code error
			$result = $server_code;
		}

		return $this->Status = $result;
	}

	/**
	* Trigger (start) a repeating payment.
	* This function is automatically invoked if $RepeatTrigger is boolean TRUE
	* @param string $OrderID Optional order ID to use for the trigger. If unspecified the OrderID from the previous transaction is used instead
	* @return int Returns the corresponding SECUREPAY_STATUS_* code
	* @see RepeatTrigger
	*/
	function Trigger($OrderId = null) {
		if ($OrderId) $this->OrderId = $OrderId;
		$this->RequestXml = $this->_ComposeTrigger();
		$this->ResponseXml = $this->_Dispatch($this->RequestXml);
		$this->ResponseTree = simplexml_load_string($this->ResponseXml);
		$server_code = $this->_TranslateServerCode($this->ResponseTree->Status->statusCode);
		if (isset($this->ResponseTree->Payment->TxnList->Txn->responseCode)) { // Has a response code
			return $this->_TranslateResponseCode($this->ResponseCode = $this->ResponseTree->Payment->TxnList->Txn->responseCode);
		} else { // No success with the response code - return the server code error
			return $server_code;
		}
	}

	/**
	* Preforms a simple test connection to the Securepay server and returns a boolean on success
	* @return bool TRUE if the server connection and login information returned a correct result
	*/
	function TestConnection() {
		$this->RequestXml = $this->_ComposeEcho();
		$this->ResponseXml = $this->_Dispatch($this->RequestXml);
		$this->ResponseTree = simplexml_load_string($this->ResponseXml);
		return ($this->_TranslateServerCode($this->ResponseTree->Status->statusCode) == SECUREPAY_STATUS_OK);
	}

	/**
	* Signs into the web interface. This should only be done once to prevent unnecessay overhead
	* This function can be called multiple times but will only actually do something the first time round.
	* Thus all methods that use the web interface should start by calling this function
	*/
	function WebSignin() {
		if (!empty($this->WebCookieJar))
			return TRUE;
		if (!function_exists('curl_init'))
			trigger_error('You do not have Curl installed on this server', E_ERROR);
		$tempdir = (function_exists('sys_get_temp_dir')) ? sys_get_temp_dir() : '/tmp'; // Attempt to figure out a temporary directory
		$this->WebCookieJar = tempnam('/tmp', 'securepay-cookie-jar-');
		touch($this->WebCookieJar);
		$this->_WebRetrieve('https://login.securepay.com.au/login/verify.jsp', "merchantId={$this->WebMerchantName}&userName={$this->WebUserName}&password={$this->WebUserPassword}&loginType=M");
		return TRUE; // FIXME: Query if the above succeded and return error if not
	}

	/**
	* Retreieves all transaction information between two optional dates.
	* If no dates are supplied. Today is assumed.
	* The first parameter 'datefrom' also accepts the convenience value 'yesterday' to retrieve information from the previous day (NOTE: Not the previous working day)
	* @param int|string $datefrom Optional date range to work from. This is a Unix Epoc. Also accepts 'yesterday' as a value. Today is assumed if omitted
	* @param int $dateto Optional date range to. Today is assumed if omitted.
	* @return array The transaction history matching the above criteria
	*/
	function GetTransactions($datefrom = null, $dateto = null) {
		$this->WebSignin();
		$output = array();

		$post = 'view_type=screen&card_type=-2&search_when=date_range';
		if ($datefrom == 'yesterday') {
			$datefrom = mktime(0,0,0,0,-1);
			$dateto = mktime(23,59,0,0,-1);
		} elseif (!$datefrom && !$dateto) { // Nulls, assume today
			$datefrom = mktime(0,0,0);
			$dateto = mktime(23,59,59);
		}
		$post .= "&date_from=" . urlencode(date('j M Y', $datefrom)) . "&hours_from_val=" . date('H', $datefrom) . "&minutes_from_val=" . date('i', $datefrom);
		$post .= "&date_to=" . urlencode(date('j M Y', $dateto)) . "&hours_to_val=" . date('H', $dateto) . "&minutes_to_val=" . date('i', $dateto);

		$response = $this->_WebRetrieve('https://login.securepay.com.au/login/login.jsp?id=ntxnlist', $post);
		preg_match_all('!<tr class="listing_payment" ><td >(.+?)</td><td ><a href="login.jsp\?id=txndetail&txn=(.+?)">(.+?)</a></td><td class="centered_col" >(.+?)</td><td class="centered_col" >(.+?)<img src="common/images/newlogos/.+?" height="17" /></td><td class="amount_col" >(.+?)</td><td class="centered_col" >(.+?)</td><td class=".+?" ><span class=".+?" >(.+?)</span><span class=".+?" > (.+?)</span></td><td class="txn_type" >(.+?)</td></tr>!', $response, $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {
			$output[$match[2]] = array(
				'merchantid' => $match[1],
				'transactionid' => $match[2],
				'transactionref' => $match[3],
				'date' => strtotime($match[4]),
				'cc' => $match[5],
				'amount' => $match[6],
				'currency' => $match[7],
				'code' => $match[8],
				'result' => $match[9],
				'type' => $match[10],
			);
		}
		return $output;
	}

	/**
	* Retrieves the client status information from SecurePay
	* If given a specific client id (usually the username) this will return just that relevent hash of information
	* @param int|string $clientid Optional single client ID that should be retrieved
	* @return array Either all client Id's discovered as keys for information or the single client information hash
	*/
	function GetClientInfo($clientid = null) {
		$this->WebSignin();
		if (empty($this->WebMerchantId)) { // Not yet calculated the merchant ID
			$page = $this->_WebRetrieve('https://login.securepay.com.au/login/login.jsp?id=perreport');
			if (preg_match('/<input type="hidden" name="merchid" value="(.+)">/', $page, $matches)) {
				$this->WebMerchantId = $matches[1];
			} else {
				trigger_error('Did not recieve a merchant ID from the periodic billing page. Perhaps SecurePay have changed their API?', E_ERROR);
			}
		}
		$csvdef = array(); // CSV definition holder
		$output = array();

		foreach (explode("\n", $this->_WebRetrieve('https://login.securepay.com.au/login/merchant/periodic/report.jsp', "id=perview&merchid={$this->WebMerchantId}" . (($clientid) ? "&clientid={$clientid}" : '') )) as $line) {
			if (($line = trim($line)) == '') continue; // Ignore blank lines
			$bits = explode(",", $line);
			$set = array();
			if (empty($csvdef)) { // Not yet defined CSV fields
				$csvdef = array_map('strtolower',$bits);
			} else { // Already defined CSV fields
				for ($b = 0; $b < count($bits); $b++)
					$set[$csvdef[$b]] = trim($bits[$b],'"');
				$output[$set['clientid']] = $set;
			}
		}
		return ($clientid) ? current($output) : $output; // If it was a specific request just return that branch
	}

	/**
	* Setup a recurring payment with error checking
	* @param string|int $when The name of the recurring type (i.e. the constants SECUREPAY_REPEAT_* or a simple word e.g. 'weekly')
	* @param int $interval The spacing between the payments (e.g. if 'when'='monthly' and 'interval'=2 - the payment will occur every two weeks).
	* @param int $count The count of the payments to apply (i.e. '3 repeating payments'). Set to zero (the default) for continuous
	*/
	function SetupRepeat($when, $count = 0) {
		if (is_string($when)) { // Translate $when to constant if supported
			$translate = array(
				'daily' => SECUREPAY_REPEAT_DAILY,
				'weekly' => SECUREPAY_REPEAT_WEEKLY,
				'fortnightly' => SECUREPAY_REPEAT_FORTNIGHTLY,
				'monthly' => SECUREPAY_REPEAT_MONTHLY,
				'quarterly' => SECUREPAY_REPEAT_QUARTERLY,
				'half_yearly' => SECUREPAY_REPEAT_HALF_YEARLY,
				'yearly' => SECUREPAY_REPEAT_YEARLY
			);
			if (isset($translate[$when])) {
				$when = $translate[$when];
			} else
				trigger_error("Unknown repeat period '$when'. See the SECUREPAY_REPEAT_* constants for a list of supported time periods", E_USER_ERROR);
		} elseif ($when < 0) {
			trigger_error("Repeat period (\$when) must be above zero", E_USER_ERROR);
		} elseif ($when > 6) {
			trigger_error("Repeat period (\$when) must be below 6 (yearly)", E_USER_ERROR);
		}
		$this->Repeat = $when;
		$this->RepeatCount = $count;
	}

	/**
	* Quick boolean return for if the pending transaction is a repeat transaction
	* @return bool TRUE if the pending transaction is a repeat transaction
	*/
	function IsRepeat() {
		return ($this->Repeat > SECUREPAY_REPEAT_NEVER);
	}

	// End of General use functionality }}}

	// Validation tests {{{
	/**
	* Global validation test that checks all other child validation tests
	* @see Valid*
	* @return bool FALSE if ANY validation test fails, TRUE if all pass successfully
	*/
	function Valid() {
		return (
			$this->ValidCC()
			&& $this->ValidExpiryDate()
			&& $this->ValidCvv()
			&& $this->ValidChargeAmount()
			&& $this->ValidChargeCurrency()
			&& $this->ValidOrderId()
		);
	}

	/**
	* Validates Credit Card number and returns a bool indicating its validity
	* @param string|int $Cc Optional credit card number to validate. If none is specified the objects Credit Card number is used instead
	* @return bool TRUE if the CC number passes validation
	*/
	function ValidCc($Cc = null) {
		$test_cc = ($Cc) ? $Cc : $this->Cc;
		if (preg_match('/[0-9]{12,16}/',$test_cc) > 0) {
			return TRUE;
		} else {
			$this->Error = 'Invalid Credit Card Number';
			return FALSE;
		}
	}

	/**
	* Validates an expiry date and ensures that it confirms to the SecurePay standards
	* @param string $ExpiryDate Optional expiry date to test. If none is specified the objects expiry date is used instead
	* @return bool TRUE if the expiry date passes validation
	*/
	function ValidExpiryDate($ExpiryDate = null) {
		$test_expiry = ($ExpiryDate) ? $ExpiryDate : $this->ExpiryDate;
		if (preg_match('!([0-9]{1,2})/([0-9]{2,4})!',$test_expiry, $matches)) {
			if (strlen($matches[1]) == 1)
				$matches[1] = "0{$matches[1]}";
			if (strlen($matches[2]) == 4)
				$matches[2] = substr($matches[2],-2);
			$this->ExpiryDate = "{$matches[1]}/{$matches[2]}";

			return ( ($matches[1] > 0) && ($matches[1] < 13) && ($matches[2] >= date('y')) && ($matches[2] < date('y') + 30) ); // Check that month and years are valid
		} else {
			$this->Error = 'Invalid Expiry Date';
			return FALSE; // Failed RegExp checks
		}
	}

	/**
	* Validates a CVV code and ensures that it confirms to the SecurePay standards
	* @param string|int $CVV Optional CVV to test. If none is specified the objects CVV is used instead
	* @param bool $ForceValue Optional value indicating that the Cvv HAS to contain a value
	* @return bool TRUE if the CVV passes validation
	*/
	function ValidCvv($Cvv = null, $ForceValue = FALSE) {
		$test_cvv = ($Cvv) ? $Cvv : $this->Cvv;
		if ($test_cvv) {
			return (preg_match('/[0-9]{3,4}/',$test_cvv, $matches));
		} elseif ($ForceValue) { // Has to contain a value but doesn't
			$this->Error = 'Invalid CVV code';
			return FALSE;
		} else // Does not have to contain and value and doesn't
			return TRUE;
	}

	/**
	* Validates a Charge Amount and ensures that it confirms to the SecurePay standards
	* @param float $Amount Optional Charge Amount to test. If none is specified the objects Charge Amount is used instead
	* @return bool TRUE if the Charge Amount passes validation
	*/
	function ValidChargeAmount($ChargeAmount = null) {
		$test_amount = ($ChargeAmount) ? $ChargeAmount : $this->ChargeAmount;
		if ($test_amount > 0) {
			return TRUE;
		} else {
			$this->Error = 'Invalid charge amount';
			return FALSE;
		}
	}

	/**
	* Validates a Charge Currency and ensures that it confirms to the SecurePay standards
	* @param string $ChargeCurrency Optional Charge Currency to test. If none is specified the objects Charge Currency is used instead
	* @return bool TRUE if the Charge Currency passes validation
	*/
	function ValidChargeCurrency($ChargeCurrency = null) {
		$test_currency = ($ChargeCurrency) ? $ChargeCurrency : $this->ChargeCurrency;
		$valid_currencies = explode(',',SECUREPAY_CURRENCIES);
		if (in_array($test_currency, $valid_currencies)) {
			return TRUE;
		} else {
			$this->Error = 'Invalid charge currency';
			return FALSE;
		}
	}

	/**
	* Validates a Order ID and ensures that it confirms to the SecurePay standards
	* @param string|int $Amount Optional Order ID to test. If none is specified the objects Order ID is used instead
	* @return bool TRUE if the Order Id Amount passes validation
	*/
	function ValidOrderId($OrderId = null) {
		$test_order = ($OrderId) ? $OrderId : $this->OrderId;
		if ( (strlen($test_order) > 0) && (strlen($test_order) <= 60) ) {
			return TRUE;
		} else {
			$this->Error = 'Invalid Order ID';
			return FALSE;
		}
	}


	// End of Validation tests }}}

	// Private functions {{{
	/**
	* Generates a new message ID
	* @return string A string of 30 random hex characters
	*/
	function _GetMessageId() {
		$code = '';
		foreach (range(1,30) as $offset)
			$code .= dechex(rand(0,15));
		return $code;
	}

	/**
	* Translates a given SecurePay server status code into a constant equivelent
	* @param int $SpCode The SecurePay status integer code
	* @return const A constant of the SECUREPAY_STATUS_* types
	*/
	function _TranslateServerCode($SpCode) {
		switch($SpCode) {
			case 000:
				$this->Error = '';
				return SECUREPAY_STATUS_OK;
			case 504:
				$this->Error = 'We are currently experiencing technical difficulties (Error: Credential failure with merchant ID). Please try again later';
				return SECUREPAY_STATUS_INVALID_USER;
			case 505:
				$this->Error = 'We are currently experiencing technical difficulties (Error: Invalid SecurePay URL). Please try again later';
				return SECUREPAY_STATUS_INVALID_URL;
			case 510:
				$this->Error = 'The credit card processor is currently experiencing difficulties. Please try again later';
				return SECUREPAY_STATUS_SERVER_DOWN;
			case 512:
				$this->Error = 'The credit card processor is currently experiencing difficulties. Please try again later';
				return SECUREPAY_STATUS_TIMEOUT;
			case 513:
			case 514:
			case 515:
			case 545:
				$this->Error = 'The credit card processor is currently experiencing difficulties. Please try again later';
				return SECUREPAY_STATUS_SERVER_ERR;
			case 516:
			case 517:
			case 518:
			case 575:
			case 577:
			case 580:
				$this->Error = 'We are currently experiencing technical difficulties (Error: XML Processing Fault). Please try again later';
				return SECUREPAY_STATUS_XML_ERR;
			case 524:
				$this->Error = 'We are currently experiencing technical difficulties (Error: Connection fault). Please try again later';
				return SECUREPAY_STATUS_CONNECTION_ERR;
			case 550:
				$this->Error = 'We are currently experiencing technical difficulties (Error: Credential failure with password). Please try again later';
				return SECUREPAY_STATUS_INVALID_PASS;
			case 595:
				$this->Error = 'Credit card declined';
				return SECUREPAY_STATUS_DENIED;
		}
	}

	/**
	* Translates a SecurePay server reposnse code into a constant equivelent
	* @param int $SpCode The SecurePay response integer code
	* @return const A constant of the SECUREPAY_STATUS_* types
	*/
	function _TranslateResponseCode($SpCode) {
		if ( ($SpCode == 0) || ($SpCode == 8) || ($SpCode == 77) ) {
			return SECUREPAY_STATUS_APPROVED;
		} else {
			$this->Error = 'Your credit card details were declined';
			return SECUREPAY_STATUS_DECLINED;
		}
	}


	/**
	* Send the request to SecurePay.com.au via SSL
	* Requires CURL to be installed
	* @param string $Xml The XML request to be sent. This is composed by one of the _Compose* functions
	* @access private
	*/
	function _Dispatch($xml) {
		if (!function_exists('curl_init'))
			trigger_error('You do not have Curl installed on this server', E_USER_ERROR);
		$curl = curl_init();
		if ($this->IsRepeat()) { // Periodic payment
			$url = ($this->TestMode) ? 'https://test.securepay.com.au/xmlapi/periodic' : 'https://api.securepay.com.au/xmlapi/periodic';
		} else // Once-off payment
			$url = ($this->TestMode) ? 'https://test.securepay.com.au/xmlapi/payment' : 'https://api.securepay.com.au/xmlapi/payment';

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE); // Follow redirects
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE); // Return the HTTP response from the curl_exec function

		if (defined('CURL_SSLVERSION_TLSv1_0')) { // TLSv1_0 supported (PHP5.5+)
			// Switch to SSLv3 due to the OpenSSL + Poodle fiascos
			// thanks to cleathley for pointing this out and to beitsafedaniel for fixing the fix
			// @url https://github.com/hash-bang/PHP-SecurePay/issues/6
			curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_0);
			curl_setopt($curl, CURLOPT_SSL_CIPHER_LIST, 'TLSv1');
		} else if (defined('CURL_SSLVERSION_TLSv1')) { // Covers all TLSv1 minor versions
			curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
		} // else - rely on PHP figuring out the best option for CURLOPT_SSLVERSION as per note at http://php.net/manual/en/function.curl-setopt.php

		$response = curl_exec($curl);
		curl_close($curl);
		return $response;
	}

	/**
	* Creates the XML request for a SecurePay Echo
	* @return string The XML string for a SecurePay Echo request
	* @access private
	*/
	function _ComposeEcho() {
		$this->LastMessageId = $this->_GetMessageId();
		$timestamp = date('YdmHis000+Z'); // See Appendix E of the SecureXML standard for more details on this date format
		$password = ($this->TestMode && $this->TestAccountPassword) ? $this->TestAccountPassword : $this->AccountPassword;
		$message = "<?xml version=\"1.0\" encoding=\"UTF-8\"?" . ">\n";
		$message .= "<SecurePayMessage>\n";
		$message .= "\t<MessageInfo>\n";
		$message .= "\t\t<messageID>{$this->LastMessageId}</messageID>\n";
		$message .= "\t\t<messageTimestamp>$timestamp</messageTimestamp>\n";
		$message .= "\t\t<timeoutValue>60</timeoutValue>\n";
		$message .= "\t\t<apiVersion>xml-4.2</apiVersion>\n";
		$message .= "\t</MessageInfo>\n";
		$message .= "\t<MerchantInfo>\n";
		$message .= "\t<merchantID>{$this->AccountName}</merchantID>\n";
		$message .= "\t<password>{$password}</password>\n";
		$message .= "\t</MerchantInfo>\n";
		$message .= "\t<RequestType>Echo</RequestType>\n";
		$message .= "</SecurePayMessage>";
		return $message;
	}

	/**
	* Creates the XML request for a SecurePay trigger
	* @return string The XML string for a SecurePay trigger request
	* @access private
	*/
	function _ComposeTrigger() {
		$this->LastMessageId = $this->_GetMessageId();
        $cents = round($this->ChargeAmount * 100); // Convert to cents
        $timestamp = date('YdmHis000+Z'); // See Appendix E of the SecureXML standard for more details on this date format
        $message = "<?xml version=\"1.0\" encoding=\"UTF-8\"?" . ">\n";
        $password = ($this->TestMode && $this->TestAccountPassword) ? $this->TestAccountPassword : $this->AccountPassword;

		$message .= "<SecurePayMessage>\n";
		$message .= "\t<MessageInfo>\n";
		$message .= "\t\t<messageID>{$this->LastMessageId}</messageID>\n";
		$message .= "\t\t<messageTimestamp>$timestamp</messageTimestamp>\n";
		$message .= "\t\t<timeoutValue>60</timeoutValue>\n";
		$message .= "\t\t<apiVersion>spxml-3.0</apiVersion>\n";
		$message .= "\t</MessageInfo>\n";
		$message .= "\t<MerchantInfo>\n";
		$message .= "\t\t<merchantID>{$this->AccountName}</merchantID>\n";
		$message .= "\t\t<password>{$password}</password>\n";
		$message .= "\t</MerchantInfo>\n";
		$message .= "\t<RequestType>Periodic</RequestType>\n";
		$message .= "\t<Periodic>\n";
		$message .= "\t\t<PeriodicList count=\"1\">\n";
		$message .= "\t\t\t<PeriodicItem ID=\"1\">\n";
		$message .= "\t\t\t\t<actionType>trigger</actionType>\n";
		$message .= "\t\t\t\t<clientID>{$this->OrderId}</clientID>\n"; // FIXME
		$message .= "\t\t\t\t<amount>$cents</amount>\n";
		$message .= "\t\t\t</PeriodicItem>\n";
		$message .= "\t\t</PeriodicList>\n";
		$message .= "\t</Periodic>\n";
		$message .= "</SecurePayMessage>";
		return $message;
	}

	/**
	* Creates the XML request for a SecurePay Echo
	* This function reads $this->PreAuth to determine whether the transaction is a PreAuth rather than a standard payment. If FALSE (the default) a standard payment is produced
	* @return string The XML string for a SecurePay Echo request
	* @access private
	*/
	function _ComposePayment() {
		$this->LastMessageId = $this->_GetMessageId();
		$cents = round($this->ChargeAmount * 100); // Convert to cents
		$timestamp = date('YdmHis000+Z'); // See Appendix E of the SecureXML standard for more details on this date format
		$message = "<?xml version=\"1.0\" encoding=\"UTF-8\"?" . ">\n";
		$password = ($this->TestMode && $this->TestAccountPassword) ? $this->TestAccountPassword : $this->AccountPassword;

		if ($this->IsRepeat()) {
			$message .= "<SecurePayMessage>\n";
			$message .= "\t<MessageInfo>\n";
			$message .= "\t\t<messageID>{$this->LastMessageId}</messageID>\n";
			$message .= "\t\t<messageTimestamp>$timestamp</messageTimestamp>\n";
			$message .= "\t\t<timeoutValue>60</timeoutValue>\n";
			$message .= "\t\t<apiVersion>spxml-3.0</apiVersion>\n";
			$message .= "\t</MessageInfo>\n";
			$message .= "\t<MerchantInfo>\n";
			$message .= "\t\t<merchantID>{$this->AccountName}</merchantID>\n";
			$message .= "\t\t<password>{$password}</password>\n";
			$message .= "\t</MerchantInfo>\n";
			$message .= "\t<RequestType>Periodic</RequestType>\n";
			$message .= "\t<Periodic>\n";
			$message .= "\t\t<PeriodicList count=\"1\">\n";
			$message .= "\t\t\t<PeriodicItem ID=\"1\">\n";
			$message .= "\t\t\t\t<actionType>add</actionType>\n";
			$message .= "\t\t\t\t<clientID>{$this->OrderId}</clientID>\n"; // FIXME
			$message .= "\t\t\t\t<CreditCardInfo>\n";
			$message .= "\t\t\t\t\t<cardNumber>{$this->Cc}</cardNumber>\n";
			$message .= "\t\t\t\t\t<expiryDate>{$this->ExpiryDate}</expiryDate>\n";
			if ($this->Cvv) // Provided with CVV/CV2 number
				$message .= "\t\t\t\t\t<cvv>{$this->Cvv}</cvv>\n";
			$message .= "\t\t\t\t</CreditCardInfo>\n";
			$message .= "\t\t\t\t<amount>$cents</amount>\n";
			$message .= "\t\t\t\t<currency>{$this->ChargeCurrency}</currency>\n";
			if ($this->Repeat == SECUREPAY_REPEAT_DAILY) {
				$message .= "\t\t\t\t<periodicType>2</periodicType>\n";
				if ($this->RepeatInterval)
					$message .= "\t\t\t\t<paymentInterval>{$this->RepeatInterval}</paymentInterval>\n";
			} else {
				$message .= "\t\t\t\t<periodicType>3</periodicType>\n";
				$message .= "\t\t\t\t<paymentInterval>{$this->Repeat}</paymentInterval>\n";
			}
			$message .= "\t\t\t\t<startDate>" . date('Ymd',($this->RepeatStart > 0) ? $this->RepeatStart : mktime()) . "</startDate>\n";
			$message .= "\t\t\t\t<numberOfPayments>" . (($this->RepeatCount > 0) ? $this->RepeatCount : 999) . "</numberOfPayments>\n";
			$message .= "\t\t\t</PeriodicItem>\n";
			$message .= "\t\t</PeriodicList>\n";
			$message .= "\t</Periodic>\n";
			$message .= "</SecurePayMessage>";
		} else { // Once-off payment
			$message .= "<SecurePayMessage>\n";
			$message .= "\t<MessageInfo>\n";
			$message .= "\t\t<messageID>{$this->LastMessageId}</messageID>\n";
			$message .= "\t\t<messageTimestamp>$timestamp</messageTimestamp>\n";
			$message .= "\t\t<timeoutValue>60</timeoutValue>\n";
			$message .= "\t\t<apiVersion>xml-4.2</apiVersion>\n";
			$message .= "\t</MessageInfo>\n";
			$message .= "\t<MerchantInfo>\n";
			$message .= "\t\t<merchantID>{$this->AccountName}</merchantID>\n";
			$message .= "\t\t<password>{$password}</password>\n";
			$message .= "\t</MerchantInfo>\n";
			$message .= "\t<RequestType>Payment</RequestType>\n";
			$message .= "\t<Payment>\n";
			$message .= "\t\t<TxnList count=\"1\">\n"; // In the current API this can only ever be 1
			$message .= "\t\t\t<Txn ID=\"1\">\n"; // Likewise limited to 1
			$message .= "\t\t\t\t<txnType>" . ($this->PreAuth ? ($this->PreAuthId ? '11' : '10') : '0') . "</txnType>\n"; // 0 = Standard payment, 10 = Pre-Auth, 11 - Charge Pre-Auth
			$message .= "\t\t\t\t<txnSource>23</txnSource>\n"; // SecurePay API always demands the value 23
			$message .= "\t\t\t\t<amount>$cents</amount>\n";
			$message .= "\t\t\t\t<currency>{$this->ChargeCurrency}</currency>\n";
			$message .= "\t\t\t\t<purchaseOrderNo>{$this->OrderId}</purchaseOrderNo>\n";
			if ($this->PreAuthId) // Processing a standard payment and the previous transaction reserved a PreAuth code
				$message .= "\t\t\t\t<preauthID>{$this->PreAuthId}</preauthID>\n";
			$message .= "\t\t\t\t<CreditCardInfo>\n";
			if (!$this->PreAuthId) { // Completing a preauth - dont need to send CC details again
				$message .= "\t\t\t\t\t<cardNumber>{$this->Cc}</cardNumber>\n";
				$message .= "\t\t\t\t\t<expiryDate>{$this->ExpiryDate}</expiryDate>\n";
				if ($this->Cvv) // Provided with CVV/CV2 number
					$message .= "\t\t\t\t\t<cvv>{$this->Cvv}</cvv>\n";
			}
			$message .= "\t\t\t\t</CreditCardInfo>\n";
			$message .= "\t\t\t</Txn>\n";
			$message .= "\t\t</TxnList>\n";
			$message .= "\t</Payment>\n";
			$message .= "</SecurePayMessage>";
		}
		return $message;
	}

	/**
	* Creates the XML request for a SecurePay Refund Echo
	* Similar to {@see _ComposePayment}, except it also requires a transaction ID
	* @return string The XML string for a SecurePay Echo request
	* @access private
	* @author Phil Hawthorne <me@philhawthorne.com>
	* @since  2014-02-13
	*/
	function _ComposeRefund() {
		$this->LastMessageId = $this->_GetMessageId();
		$cents = round($this->ChargeAmount * 100); // Convert to cents
		$timestamp = date('YdmHis000+Z'); // See Appendix E of the SecureXML standard for more details on this date format
		$message = "<?xml version=\"1.0\" encoding=\"UTF-8\"?" . ">\n";
		$password = ($this->TestMode && $this->TestAccountPassword) ? $this->TestAccountPassword : $this->AccountPassword;

		$message .= "<SecurePayMessage>\n";
		$message .= "\t<MessageInfo>\n";
		$message .= "\t\t<messageID>{$this->LastMessageId}</messageID>\n";
		$message .= "\t\t<messageTimestamp>$timestamp</messageTimestamp>\n";
		$message .= "\t\t<timeoutValue>60</timeoutValue>\n";
		$message .= "\t\t<apiVersion>xml-4.2</apiVersion>\n";
		$message .= "\t</MessageInfo>\n";
		$message .= "\t<MerchantInfo>\n";
		$message .= "\t\t<merchantID>{$this->AccountName}</merchantID>\n";
		$message .= "\t\t<password>{$password}</password>\n";
		$message .= "\t</MerchantInfo>\n";
		$message .= "\t<RequestType>Payment</RequestType>\n";
		$message .= "\t<Payment>\n";
		$message .= "\t\t<TxnList count=\"1\">\n"; // In the current API this can only ever be 1
		$message .= "\t\t\t<Txn ID=\"1\">\n"; // Likewise limited to 1
		$message .= "\t\t\t\t<txnType>4</txnType>\n"; // 0 = Standard payment, 10 = Pre-Auth, 11 - Charge Pre-Auth
		$message .= "\t\t\t\t<txnSource>23</txnSource>\n"; // SecurePay API always demands the value 23
		$message .= "\t\t\t\t<amount>$cents</amount>\n";
		$message .= "\t\t\t\t<currency>{$this->ChargeCurrency}</currency>\n";
		$message .= "\t\t\t\t<purchaseOrderNo>{$this->OrderId}</purchaseOrderNo>\n";
		$message .= "\t\t\t\t<txnID>{$this->TransactionId}</txnID>\n";
		if ($this->PreAuthId) // Processing a standard payment and the previous transaction reserved a PreAuth code
			$message .= "\t\t\t\t<preauthID>{$this->PreAuthId}</preauthID>\n";

		$message .= "\t\t\t</Txn>\n";
		$message .= "\t\t</TxnList>\n";
		$message .= "\t</Payment>\n";
		$message .= "</SecurePayMessage>";

		return $message;
	}

	/**
	* Makes a POST request to the web interface using CURL
	* This function uses the main objects Cookie Jar
	* @access private
	* @param string $url The URL that should be retrieved
	* @param string $post Optional POST string that should be retrieved
	*/
	function _WebRetrieve($url, $post = null) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_AUTOREFERER, 'https://login.securepay.com.au');
		curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 6.0)');
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE); // Follow redirects
		curl_setopt($curl, CURLOPT_AUTOREFERER, TRUE); // Auto carry referer urls
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE); // Return the HTTP response from the curl_exec function
		curl_setopt($curl, CURLOPT_COOKIEFILE, $this->WebCookieJar);
		curl_setopt($curl, CURLOPT_COOKIEJAR, $this->WebCookieJar);
		if ($post) {
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
		}
		$response = curl_exec($curl); // Sign-in
		curl_close($curl);
		return $response;
	}

	// End of Private functions }}}
}
?>
