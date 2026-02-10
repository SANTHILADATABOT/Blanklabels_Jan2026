<?php
/**
 * eWAY Transparent Redirect Payment Module
 *
 * @package paymentMethod
 * @copyright Copyright 2012-2014 Web Active Corporation Pty Ltd
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version GIT: $Id$
 */

class eway_rapid extends base {

  /**
   * string representing the payment method
   * @var string
   */
  var $code;

  /**
   * $title is the displayed name for this payment method
   * @var string
   */
  var $title;

  /**
   * $description is a soft name for this payment method
   * @var string
   */
  var $description;

  /**
   * $enabled determines whether this module shows or not... in catalog.
   * @var boolean
   */
  var $enabled;

  /**
   * The response code from a transaction
   * @var string
   */
  var $auth_code;

  /**
   * The eWAY transaction id of a transaction
   * @var string
   */
  var $transaction_id;

  /**
   *
   * @var boolean
   */
  var $enableDirectPayment = true;

  /**
   * log file folder
   * @var string
   */
  var $_logDir = '';


  /**
   * Constructor
   *
   * @return eway_rapid
   */
  function eway_rapid() {
    global $order, $messageStack;

    $this->code = 'eway_rapid';
    $this->codeTitle = MODULE_PAYMENT_EWAYRAPID_TEXT_TITLE;
    $this->codeVersion = '1.4';
    $this->enableDirectPayment = true;

    if (IS_ADMIN_FLAG === true) {
      $this->title = MODULE_PAYMENT_EWAYRAPID_TEXT_TITLE; // Payment module title in Admin
      if (MODULE_PAYMENT_EWAYRAPID_STATUS == 'True'
              && ( MODULE_PAYMENT_EWAYRAPID_USERNAME == ''
                || MODULE_PAYMENT_EWAYRAPID_PASSWORD == '')) {
        $this->title .=  '<span class="alert"> (Not Configured)</span>';
      } elseif (MODULE_PAYMENT_EWAYRAPID_MODE == 'True') {
        $this->title .= '<span class="alert"> (in Testing mode)</span>';
      }
    } else {
      $this->title = MODULE_PAYMENT_EWAYRAPID_TEXT_CATALOG_TITLE; // Payment module title in Catalog
    }

    $this->description = MODULE_PAYMENT_EWAYRAPID_TEXT_DESCRIPTION;
    $this->sort_order = MODULE_PAYMENT_EWAYRAPID_SORT_ORDER;
    $this->enabled = ((MODULE_PAYMENT_EWAYRAPID_STATUS == 'True') ? true : false);

    if ((int)MODULE_PAYMENT_EWAYRAPID_ORDER_STATUS_ID > 0) {
      $this->order_status = MODULE_PAYMENT_EWAYRAPID_ORDER_STATUS_ID;
    }

    if (is_object($order)) $this->update_status();

    $this->_logDir = defined('DIR_FS_LOGS') ? DIR_FS_LOGS : DIR_FS_SQL_CACHE;
  }

  /**
   * Calculate zone matches and flag settings to determine whether this module should display to customers or not
   */
  function update_status() {
    global $order, $db;
    if ($this->enabled && (int)$this->zone > 0) {
      $check_flag = false;
      $sql = "SELECT zone_id
              FROM " . TABLE_ZONES_TO_GEO_ZONES . "
              WHERE geo_zone_id = :zoneId
              AND zone_country_id = :countryId
              ORDER BY zone_id";
      $sql = $db->bindVars($sql, ':zoneId', $this->zone, 'integer');
      $sql = $db->bindVars($sql, ':countryId', $order->billing['country']['id'], 'integer');
      $check = $db->Execute($sql);
      while (!$check->EOF) {
        if ($check->fields['zone_id'] < 1) {
          $check_flag = true;
          break;
        } elseif ($check->fields['zone_id'] == $order->billing['zone_id']) {
          $check_flag = true;
          break;
        }
        $check->MoveNext();
      }

      if (!$check_flag) {
        $this->enabled = false;
      }
    }
  }

  /**
   * JS validation which does error-checking of data-entry if this module is selected for use
   * (Number, Owner, and CVV Lengths)
   *
   * @return string
  */
  function javascript_validation() {
    return false;
  }

  /**
   * Display Credit Card Information Submission Fields on the Checkout Payment Page
   *
   * @return array
  */
  function selection() {
      return array('id' => $this->code,
                   'module' => $this->title);
  }

  /**
   * Normally evaluates the Credit Card Type for acceptance and the validity of the Credit Card Number & Expiration Date
   * Since this module is not collecting info, it simply skips this step.
   *
   * @return boolean
   */
  function pre_confirmation_check() {
    return false;
  }

  /**
   * Display Credit Card Information on the Checkout Confirmation Page
   * Since none is collected for this module this is skipped
   *
   * @return boolean
   */
  function confirmation() {
      return false;
  }

  /**
   * Build the data and actions to process when the "Submit" button is pressed on the order-confirmation screen.
   * This sends the data to the payment gateway for processing.
   * (These are hidden fields on the checkout confirmation page)
   *
   * @return string
   */
  function process_button() {
        global $db, $order, $currencies, $currency, $messageStack;

        $amount = number_format($order->info['total'], 2, '.', '') * 100;
        $transact_url = zen_href_link(FILENAME_CHECKOUT_PROCESS, 'referer=eway', 'SSL', true, false);
        $customerId = $_SESSION['customer_id'];				// customerId
        $merchantRef = $customerId."-".date("YmdHis");		// merchant reference

        require_once(realpath(dirname(__FILE__).'/eway_rapid/RapidAPI.php'));
        $username = MODULE_PAYMENT_EWAYRAPID_USERNAME;
        $password = MODULE_PAYMENT_EWAYRAPID_PASSWORD;
        $livemode = ( MODULE_PAYMENT_EWAYRAPID_MODE == 'True' ) ? false : true;
        $eway_service = new RapidAPI($livemode, $username, $password);

        // Create AccessCode Request Object
        $request = new CreateAccessCodeRequest();

        $request->Customer->Reference = 'zencart';
        $request->Customer->Title = 'Mr.';
        $request->Customer->FirstName = strval($order->billing['firstname']);
        $request->Customer->LastName  = strval($order->billing['lastname']);
        $request->Customer->CompanyName = '';
        $request->Customer->JobDescription = '';
        $request->Customer->Street1 = strval($order->billing['street_address']);
        $request->Customer->Street2 = strval($order->billing['suburb']);
        $request->Customer->City = strval($order->billing['city']);
        $request->Customer->State = strval($order->billing['state']);
        $request->Customer->PostalCode = strval($order->billing['postcode']);
        $request->Customer->Country = strtolower(strval($order->billing['country']['iso_code_2']));
        $request->Customer->Email = $order->customer['email_address'];
        $request->Customer->Phone = $order->customer['telephone'];
        $request->Customer->Mobile = '';

        // require field
        $request->ShippingAddress->FirstName = strval($order->delivery['firstname']);
        $request->ShippingAddress->LastName  = strval($order->delivery['lastname']);
        $request->ShippingAddress->Street1 = strval($order->delivery['street_address']);
        $request->ShippingAddress->Street2 = strval($order->delivery['suburb']);
        $request->ShippingAddress->City = strval($order->delivery['city']);
        $request->ShippingAddress->State = strval($order->delivery['state']);
        $request->ShippingAddress->PostalCode = strval($order->delivery['postcode']);
        $request->ShippingAddress->Country = strtolower(strval($order->delivery['country']['iso_code_2']));
        $request->ShippingAddress->Email = $order->customer['email_address'];
        $request->ShippingAddress->Phone = $order->customer['telephone'];
        $request->ShippingAddress->ShippingMethod = "Unknown";

        $invoiceDesc = '';
        foreach ($order->products as $product) {
            $item = new EwayLineItem();
            $item->SKU = $product['id'];
            $item->Description = $product['name'];
            $request->Items->LineItem[] = $item;
            $invoiceDesc .= $product['name'] . ', ';
        }
        $invoiceDesc = substr($invoiceDesc, 0, -2);
        if(strlen($invoiceDesc) > 64) $invoiceDesc = substr($invoiceDesc , 0 , 61) . '...';

        $request->Payment->TotalAmount = $amount;
        $request->Payment->InvoiceNumber = $merchantRef;
        $request->Payment->InvoiceDescription = $invoiceDesc;
        $request->Payment->InvoiceReference = '';
        $request->Payment->CurrencyCode = $order->info['currency'];

        $transact_url = str_replace('&amp;', '&', $transact_url);
        $request->RedirectUrl = $transact_url;
        $request->Method = 'ProcessPayment';

        $this->_debugActions($request, 'Submit-Data', '', zen_session_id());

        //Call RapidAPI
        $result = $eway_service->CreateAccessCode($request);

        if (isset($result->Errors)) {
            //Get Error Messages from Error Code. Error Code Mappings are in the Config.ini file
            $ErrorArray = explode(",", $result->Errors);
            $lblError = "";
            foreach ( $ErrorArray as $error ) {
                if (isset($eway_service->APIConfig[$error]))
                    $lblError .= $error." ".$eway_service->APIConfig[$error]."<br>";
                else
                    $lblError .= $error;
            }
        }

        if (isset($lblError) || empty($result)) {

            $this->_debugActions($result, 'Response-Data-Error', '', zen_session_id());

            $messageStack->add_session('checkout_payment', $lblError, 'error');
            //zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
            echo '<h3>'.$lblError.'</h3>';
            echo '<a href="'.zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false).'">click here to go back and try again, or try another payment method</a>';
            die();
        }

        $this->_debugActions($result, 'Response-Data', '', zen_session_id());

        // close previous form
        $process_button_string = '</form><form action="' . $result->FormActionURL . '" method="post" onsubmit="submitonce();" style="margin-left:0px;">';
        $this->form_action_url = $result->FormActionURL;

        $process_button_string .= zen_draw_hidden_field('EWAY_ACCESSCODE', $result->AccessCode);
        $process_button_string .= '<label for="eway_rapid-cc-ownerf" class="inputLabelPayment">Cardholder Name:</label><input type="text" name="EWAY_CARDNAME" value="" id="eway_rapid-cc-ownerf" autocomplete="off" style="margin-left:0px;" /><br class="clearBoth" />' . "\n";
        $process_button_string .= '<label for="eway_rapid-cc-number" class="inputLabelPayment">Credit Card Number:</label><input type="text" name="EWAY_CARDNUMBER" id="eway_rapid-cc-number" autocomplete="off" /><br class="clearBoth" />' . "\n";
        $process_button_string .= '
<label for="eway_rapid-cc-expires-month" class="inputLabelPayment">Credit Card Expiry Date:</label>
<select name="EWAY_CARDEXPIRYMONTH" id="eway_rapid-cc-expires-month">
  <option value="01">January - (01)</option>
  <option value="02">February - (02)</option>
  <option value="03">March - (03)</option>
  <option value="04">April - (04)</option>
  <option value="05">May - (05)</option>
  <option value="06">June - (06)</option>
  <option value="07">July - (07)</option>
  <option value="08">August - (08)</option>
  <option value="09">September - (09)</option>
  <option value="10">October - (10)</option>
  <option value="11">November - (11)</option>
  <option value="12">December - (12)</option>
</select>
&nbsp;<select name="EWAY_CARDEXPIRYYEAR" id="eway_rapid-cc-expires-year">
  <option value="25">2025</option>
  <option value="26">2026</option>
  <option value="27">2027</option>
  <option value="28">2028</option>
  <option value="29">2029</option>
  <option value="30">2030</option>
  <option value="31">2031</option>
  <option value="32">2032</option>
</select>
<br class="clearBoth" />
        ';
        $process_button_string .= '<label for="eway_rapid-cc-cvv" class="inputLabelPayment">CVV</label><input type="text" name="EWAY_CARDCVN" size="4" maxlength="4" id="eway_rapid-cc-cvv" autocomplete="off" /><br class="clearBoth" />';

        return $process_button_string;
    }

  /**
   * Store transaction info to the order and process any results that come back from the payment gateway
   */
  function before_process() {
    global $order, $order_totals, $db, $messageStack;
    if (isset($_GET['referer']) && $_GET['referer'] == 'eway') {
            require_once(realpath(dirname(__FILE__).'/eway_rapid/RapidAPI.php'));
            $username = MODULE_PAYMENT_EWAYRAPID_USERNAME;
            $password = MODULE_PAYMENT_EWAYRAPID_PASSWORD;
            $livemode = ( MODULE_PAYMENT_EWAYRAPID_MODE == 'True' ) ? false : true;
            $eway_service = new RapidAPI($livemode, $username, $password);

            $isError = false;
            $request = new GetAccessCodeResultRequest();
            if ( isset($_GET['amp;AccessCode']) ) {
                $request->AccessCode = $_GET['amp;AccessCode'];
            } else {
                $request->AccessCode = $_GET['AccessCode'];
            }

            $this->_debugActions($request, 'Submit-Data', '', zen_session_id());

            //Call RapidAPI to get the result
            $result = $eway_service->GetAccessCodeResult($request);

            $this->_debugActions($result, 'Response-Data', '', zen_session_id());

            // Check if any error returns
            if(isset($result->Errors)) {
                // Get Error Messages from Error Code. Error Code Mappings are in the Config.ini file
                $ErrorArray = explode(",", $result->Errors);
                $lblError = "";
                $isError = true;
                foreach ( $ErrorArray as $error ) {
                    $error = trim($error);
                    $lblError .= $eway_service->APIConfig[$error]."<br>";
                }
            }

            if (! $isError) {
                if (! $result->TransactionStatus) {
                  if (isset($eway_service->APIConfig[$result->ResponseMessage]))
                    $lblError = "Payment Declined - "." ".$eway_service->APIConfig[$result->ResponseMessage]."<br>";
                  else
                    $lblError = "Payment Declined - " . $result->ResponseCode;
                  $isError = true;
                }
            }

            if ($isError) {
                $messageStack->add_session('checkout_payment', $lblError, 'error');
                zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
                return false;
            }

            // success
            $this->transaction_id = $result->TransactionID;
            $this->auth_code = $result->ResponseCode;
            $_SESSION['eway_transaction_passed'] = true;
            return true;
        } else {
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
        }
    }

  /**
   * Post-processing activities
   *
   * @return boolean
   */
  function after_process() {
        global $insert_id, $db;

        if ($_SESSION['eway_transaction_passed'] != true) {
          unset($_SESSION['eway_transaction_passed']);
          return false;
        } else {
          unset($_SESSION['eway_transaction_passed']);

          $commentString = "eway TransactionID: " . $this->transaction_id;
          $sql_data_array= array(array('fieldName'=>'orders_id', 'value'=>$insert_id, 'type'=>'integer'),
                           array('fieldName'=>'orders_status_id', 'value' => $this->order_status, 'type'=>'integer'),
                           array('fieldName'=>'date_added', 'value'=>'now()', 'type'=>'noquotestring'),
                           array('fieldName'=>'customer_notified', 'value'=>0, 'type'=>'integer'),
                           array('fieldName'=>'comments', 'value'=>$commentString, 'type'=>'string'));
          $db->perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
        }
    }

  /**
   * Used to display error message details
   *
   * @return array
   */
  function get_error() {
      $error = array('title' => MODULE_PAYMENT_LINKPOINT_API_TEXT_ERROR,
                     'error' => stripslashes(urldecode($_GET['error'])));
      return $error;
  }

  /**
   * Check to see whether module is installed
   *
   * @return boolean
   */
  function check() {
      global $db;
      if (!isset($this->_check)) {
        $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_EWAYRAPID_STATUS'");
        $this->_check = !$check_query->EOF;
      }
      return $this->_check;
  }

  /**
   * Install the payment module and its configuration settings
   *
   */
  function install() {
    global $db, $messageStack;

    $db->Execute("insert into " . TABLE_CONFIGURATION . " "
            . "(configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) "
            . "values ('Enable eWAY Payment Module', 'MODULE_PAYMENT_EWAYRAPID_STATUS', 'True', 'Do you want to authorize payment through eWAY Payment?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");

    $db->Execute("insert into " . TABLE_CONFIGURATION . " "
            . "(configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) "
            . "values ('Test Mode', 'MODULE_PAYMENT_EWAYRAPID_MODE', 'True', 'You can set to go to testing mode here.', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");

    $db->Execute("insert into " . TABLE_CONFIGURATION . " "
            . "(configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) "
            . "values ('eWAY API Key', 'MODULE_PAYMENT_EWAYRAPID_USERNAME', '', 'Your eWAY API Key registered when you join eWAY.', '6', '0', now())");

    $db->Execute("insert into " . TABLE_CONFIGURATION . " "
            . "(configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) "
            . "values ('eWay Password', 'MODULE_PAYMENT_EWAYRAPID_PASSWORD', '', 'Your eWAY password registered when you join eWAY.', '6', '0', now())");

    $db->Execute("insert into " . TABLE_CONFIGURATION . " "
            . "(configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) "
            . "values ('Set Order Status', 'MODULE_PAYMENT_EWAYRAPID_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");

    $db->Execute("insert into " . TABLE_CONFIGURATION . " "
            . "(configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) "
            . "values ('Sort order of display.', 'MODULE_PAYMENT_EWAYRAPID_SORT_ORDER', '1', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");

    $db->Execute("insert into " . TABLE_CONFIGURATION . " "
            . "(configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) "
            . "values ('eWAY Debugging', 'MODULE_PAYMENT_EWAYRAPID_DEBUGGING', 'Off', 'Would you like to enable debug mode? Transaction details will be logged to a file', '6', '0', 'zen_cfg_select_option(array(\'Off\', \'On\'), ', now())");
  }

  /**
   * Remove the module and all its settings
   *
   */
  function remove() {
    global $db;
    $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
  }

  /**
   * Internal list of configuration keys used for configuration of the module
   *
   * @return array
   */
  function keys() {
    return array('MODULE_PAYMENT_EWAYRAPID_STATUS', 'MODULE_PAYMENT_EWAYRAPID_MODE', 'MODULE_PAYMENT_EWAYRAPID_USERNAME',
        'MODULE_PAYMENT_EWAYRAPID_PASSWORD', 'MODULE_PAYMENT_EWAYRAPID_ORDER_STATUS_ID', 'MODULE_PAYMENT_EWAYRAPID_SORT_ORDER',
        'MODULE_PAYMENT_EWAYRAPID_DEBUGGING');
  }

  /**
   * Used to do any debug logging / tracking / storage as required.
   */
  function _debugActions($data, $mode, $order_time= '', $sessID = '') {
    global $insert_id;
    if ($order_time == '') $order_time = date("j F, Y, g:i a");
    //$response['url'] = $this->form_action_url;
    //$this->reportable_submit_data['url'] = $this->form_action_url;

    $key = '';

    $errorMessage = date('Y-M-d h:i:s') .
                    "\n=================================\n\n";
    if ($mode == 'Submit-Data') $errorMessage .=
                    'Sent to eWay: ' . print_r($data, true) . "\n\n";
    if ($mode == 'Response-Data') $errorMessage .=
                    'Response from eWAY: ' . print_r($data, true) . "\n\n";
    if ($mode == 'Response-Data-Error') {
      $errorMessage .= 'Response from eWAY: ' . print_r($data, true) . "\n\n";
      $key = 'ERROR_';
    }

    // store log file if log mode enabled
    if ( MODULE_PAYMENT_EWAYRAPID_DEBUGGING == 'On') {
      $key .= $insert_id . '_' .time() .  '_' . zen_create_random_value(4);
      $file = $this->_logDir . '/' . 'eWAY_Debug_' . $key . '.log';
      $fp = @fopen($file, 'a');
      @fwrite($fp, $errorMessage);
      @fclose($fp);
    }

  }

}