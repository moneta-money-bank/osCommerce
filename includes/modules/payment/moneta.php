<?php
/**
 * Moneta Payment
 *
 * Contains Checkout Payment Logic
 *
 */
require_once "moneta/payments.php";
class moneta
{
    /**
     * parameters to initiate the SDK payment.
     *
     */
    protected $environment_params;
    /**
     * payment constructor.
     */
    public function __construct()
    {
        $this->code = 'moneta';
        $this->title = MODULE_PAYMENT_MONETA_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_MONETA_TEXT_DESCRIPTION;
        $this->sort_order = MODULE_PAYMENT_MONETA_SORT_ORDER;
        $this->merchant_id = MODULE_PAYMENT_MONETA_MERCHANT_ID;
        $this->merchant_passwd = MODULE_PAYMENT_MONETA_MERCHANT_PASSWD;
        $this->merchant_brand_id = MODULE_PAYMENT_MONETA_BRAND_ID;
        $this->enabled = ((MODULE_PAYMENT_MONETA_STATUS == 'True') ? true : false);
        $this->public_title = MODULE_PAYMENT_MONETA_PUBLIC_TITLE;
        
        //Order status
        $this->auth_status_id = '';
        $this->complete_status_id = '';
        $this->refunded_status_id = '';
        $this->voided_status_id = '';
        $this->failed_status_id = '';
        if (is_object($order))$this->update_status();
        
        //Gateway urls
        $this->payment_moneta_test_token_url = 'https://apiuat.test.monetaplatebnisluzby.cz/token';
        $this->payment_moneta_test_payments_url = 'https://apiuat.test.monetaplatebnisluzby.cz/payments';
        $this->payment_moneta_test_javascript_url = 'https://cashierui-apiuat.test.monetaplatebnisluzby.cz/js/api.js';
        $this->payment_moneta_test_cashier_url = 'https://cashierui-apiuat.test.monetaplatebnisluzby.cz/ui/cashier';
        $this->payment_moneta_token_url = 'https://api.monetaplatebnisluzby.cz/token';
        $this->payment_moneta_payments_url = 'https://api.monetaplatebnisluzby.cz/payments';
        $this->payment_moneta_javascript_url = 'https://cashierui-api.monetaplatebnisluzby.cz/js/api.js';
        $this->payment_moneta_cashier_url = 'https://cashierui-api.monetaplatebnisluzby.cz/ui/cashier';
        
        //clicking on the Confirm Order button will redirect the payment to the below URL to proceed
        if(MODULE_PAYMENT_MONETA_PAYMENT_MODE == 'iframe'){
            $this->form_action_url = tep_href_link('ext/modules/payment/moneta/iframe.php', '', 'SSL', false);
        }else{
            if(MODULE_PAYMENT_MONETA_SANDBOX_ACTION == 'True'){
                $this->form_action_url = $this->payment_moneta_test_cashier_url;
            }else{
                $this->form_action_url = $this->payment_moneta_cashier_url;
            }
        }
        
        
        
    }
    // class methods
    function update_status()
    {
        global $order;
    }
    function check() {
        if (!isset($this->_check)) {
            $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_MONETA_STATUS'");
            $this->_check = tep_db_num_rows($check_query);
        }
        return $this->_check;
    }
    
    /**
     * Get a list with all available Admin Settings for the Module
     * @return array
     */
    function keys() {
        return array('MODULE_PAYMENT_MONETA_STATUS',
            'MODULE_PAYMENT_MONETA_SANDBOX_ACTION',
            'MODULE_PAYMENT_MONETA_MERCHANT_ID',
            'MODULE_PAYMENT_MONETA_MERCHANT_PASSWD',
            'MODULE_PAYMENT_MONETA_BRAND_ID',
            'MODULE_PAYMENT_MONETA_PAYMENT_MODE',
            'MODULE_PAYMENT_MONETA_PAYMENT_ACTION',
            'MODULE_PAYMENT_MONETA_SORT_ORDER',
            'MODULE_PAYMENT_MONETA_AUTH_ORDER_STATUS_ID',
            'MODULE_PAYMENT_MONETA_SUCCESS_ORDER_STATUS_ID',
            'MODULE_PAYMENT_MONETA_REFUND_ORDER_STATUS_ID',
            'MODULE_PAYMENT_MONETA_VOID_ORDER_STATUS_ID',
            'MODULE_PAYMENT_MONETA_FAIL_ORDER_STATUS_ID',
            
        );
    }
    /**
     * Uninstall Module, will be called when admin click uninstall module
     * @return void
     */
    function remove() {
        tep_db_query("DELETE FROM " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
        tep_db_query("DROP TABLE IF EXISTS `moneta_transactions`");
        tep_db_query("DROP TABLE IF EXISTS `moneta_order`");
//         tep_db_query("DELETE FROM " . TABLE_ORDERS_STATUS . " WHERE orders_status_name IN ('On-hold [Moneta]','Complete [Moneta]','Refunded [Moneta]','Voided [Moneta]','Failed [Moneta]') " );
    }
    /**
     * Install Module, will be called when admin click install module
     * @return void
     */
    function install(){
        global $messageStack;
        $this->checktable();
        $this->insert_statuses();
        $isOrdersPHPFileSuccessfullyPatched = $this->doCheckAndPatchOrdersCoreTemplateFile(false);
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) 				VALUES ('Enable Moneta payment module?', 			'MODULE_PAYMENT_MONETA_STATUS', 'False', 'Do you want to enable Moneta payments?', 								'33', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) 				VALUES ('Test Mode?', 			'MODULE_PAYMENT_MONETA_SANDBOX_ACTION', 'True', 'Do you want to enable test mode?', 								'33', '2', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 								VALUES ('Merchant ID',			 					'MODULE_PAYMENT_MONETA_MERCHANT_ID', '', '',				 	'33', '3', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 								VALUES ('Password', 						        'MODULE_PAYMENT_MONETA_MERCHANT_PASSWD', '', '',		 								'33', '4', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 								VALUES ('Brand ID', 								'MODULE_PAYMENT_MONETA_BRAND_ID', '', '', 								'33', '5', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) 								VALUES ('Sort order of display', 					'MODULE_PAYMENT_MONETA_SORT_ORDER', '0', '', 						'33', '6', now())");
        $payment_mode = '';
        if(1){
            $payment_mode .= ",\'redirect\'";
        }
        if(1){
            $payment_mode .= ",\'iframe\'";
        }
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) 	            VALUES ('Payment mode', 							'MODULE_PAYMENT_MONETA_PAYMENT_MODE', 'hostedPayPage', '', 											'33', '7', 'tep_cfg_select_option(array(\'hostedPayPage\' ".$payment_mode."), ', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) 	            VALUES ('Payment action', 							'MODULE_PAYMENT_MONETA_PAYMENT_ACTION', 'purchase', '', 											'33', '8', 'tep_cfg_select_option(array(\'purchase\', \'auth\'), ', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('Set Auth Status', 'MODULE_PAYMENT_MONETA_AUTH_ORDER_STATUS_ID', '" . $this->auth_status_id . "', '', '33', '9', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('Set Success Status', 'MODULE_PAYMENT_MONETA_SUCCESS_ORDER_STATUS_ID', '" . $this->complete_status_id . "', '', '33', '10', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('Set Refunded Status', 'MODULE_PAYMENT_MONETA_REFUND_ORDER_STATUS_ID', '" . $this->refunded_status_id . "', '', '33', '11', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('Set Voided Status', 'MODULE_PAYMENT_MONETA_VOID_ORDER_STATUS_ID', '" . $this->voided_status_id . "', '', '33', '12', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('Set Failed Status', 'MODULE_PAYMENT_MONETA_FAIL_ORDER_STATUS_ID', '" . $this->failed_status_id . "', '', '33', '13', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
        if ($isOrdersPHPFileSuccessfullyPatched) {
            $messageStack->add_session("Module installed successfully", 'success');
        } else {
            $ordersPHPFile = DIR_FS_ADMIN . "orders.php";
            $messageStack->add_session(
                sprintf("Orders Template file could not be modified! " .
                    "Please, give write permission to file \"%s\" and reinstall plugin or contact support for more info!",
                    $ordersPHPFile
                    ),
                'error'
                );
        }
    }
    function checktable(){
        $sql = "
            CREATE TABLE IF NOT EXISTS `moneta_transactions` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
			  `moneta_order_id` INT(11) NOT NULL,
			  `created` DATETIME NOT NULL,
			  `type` ENUM('auth', 'payment', 'refund', 'void') DEFAULT NULL,
			  `amount` DECIMAL( 10, 2 ) NOT NULL,
			  PRIMARY KEY (`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
        tep_db_query($sql);
        $sql_order = "
            CREATE TABLE IF NOT EXISTS `moneta_order` (
			  `moneta_order_id` INT(11) NOT NULL AUTO_INCREMENT,
			  `order_id` INT(11) NOT NULL,
			  `merchant_tx_id` VARCHAR(50) NOT NULL,
              `created` DATETIME NOT NULL,
			  `modified` DATETIME NOT NULL,
              `capture_status` INT(1) DEFAULT NULL,
			  `void_status` INT(1) DEFAULT NULL,
			  `refund_status` INT(1) DEFAULT NULL,
              `currency_code` CHAR(3) NOT NULL,
			  `total` DECIMAL( 10, 2 ) NOT NULL,
			  PRIMARY KEY (`moneta_order_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ";
        tep_db_query($sql_order);
    }
    /**
     * Include Javascript Validations on Checkout Payment Page
     * @return bool
     */
    function javascript_validation() {
        return false;
    }
    /**
     * Modifies Module Listing on the Checkout Page
     * @return array
     */
    function selection()
    {
        global $cart_moneta_ID;
        
        if (tep_session_is_registered('cart_moneta_ID')) {
            $order_id = substr($cart_moneta_ID, strpos($cart_moneta_ID, '-')+1);
            
            $check_query = tep_db_query('select orders_id from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int)$order_id . '" limit 1');
            
            if (tep_db_num_rows($check_query) < 1) {
                tep_db_query('delete from ' . TABLE_ORDERS . ' where orders_id = "' . (int)$order_id . '"');
                tep_db_query('delete from ' . TABLE_ORDERS_TOTAL . ' where orders_id = "' . (int)$order_id . '"');
                tep_db_query('delete from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int)$order_id . '"');
                tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS . ' where orders_id = "' . (int)$order_id . '"');
                tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . ' where orders_id = "' . (int)$order_id . '"');
                tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_DOWNLOAD . ' where orders_id = "' . (int)$order_id . '"');
                
                tep_session_unregister('cart_moneta_ID');
            }
        }
        
        return array('id' => $this->code,
            'module' => $this->public_title);
    }
    /**
     * Confirmation Check mothod for Checkout Payment Page
     * @return bool
     */
    function pre_confirmation_check()
    {
        global $cartID, $cart;
        
        if (empty($cart->cartID)) {
            $cartID = $cart->cartID = $cart->generate_cart_id();
        }
        
        if (!tep_session_is_registered('cartID')) {
            tep_session_register('cartID');
        }
    }
    /**
     * Confirmation Check mothod for Checkout Confirmation Page
     * @return bool
     */
    function confirmation() {
//         return array('title' => MODULE_PAYMENT_MONETA_PUBLIC_CONFIRFMATION_TITLE);
        global $cartID, $cart_moneta_ID, $customer_id, $languages_id, $order, $order_total_modules;
        
        if (tep_session_is_registered('cartID')) {
            $insert_order = false;
            
            if (tep_session_is_registered('cart_moneta_ID')) {
                $order_id = substr($cart_moneta_ID, strpos($cart_moneta_ID, '-')+1);
                
                $curr_check = tep_db_query("select currency from " . TABLE_ORDERS . " where orders_id = '" . (int)$order_id . "'");
                $curr = tep_db_fetch_array($curr_check);
                
                if ( ($curr['currency'] != $order->info['currency']) || ($cartID != substr($cart_moneta_ID, 0, strlen($cartID))) ) {
                    $check_query = tep_db_query('select orders_id from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int)$order_id . '" limit 1');
                    
                    if (tep_db_num_rows($check_query) < 1) {
                        tep_db_query('delete from ' . TABLE_ORDERS . ' where orders_id = "' . (int)$order_id . '"');
                        tep_db_query('delete from ' . TABLE_ORDERS_TOTAL . ' where orders_id = "' . (int)$order_id . '"');
                        tep_db_query('delete from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int)$order_id . '"');
                        tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS . ' where orders_id = "' . (int)$order_id . '"');
                        tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . ' where orders_id = "' . (int)$order_id . '"');
                        tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_DOWNLOAD . ' where orders_id = "' . (int)$order_id . '"');
                    }
                    
                    $insert_order = true;
                }
            } else {
                $insert_order = true;
            }
            
            if ($insert_order == true) {
                $order_totals = array();
                if (is_array($order_total_modules->modules)) {
                    reset($order_total_modules->modules);
                    while (list(, $value) = each($order_total_modules->modules)) {
                        $class = substr($value, 0, strrpos($value, '.'));
                        if ($GLOBALS[$class]->enabled) {
                            for ($i=0, $n=sizeof($GLOBALS[$class]->output); $i<$n; $i++) {
                                if (tep_not_null($GLOBALS[$class]->output[$i]['title']) && tep_not_null($GLOBALS[$class]->output[$i]['text'])) {
                                    $order_totals[] = array('code' => $GLOBALS[$class]->code,
                                        'title' => $GLOBALS[$class]->output[$i]['title'],
                                        'text' => $GLOBALS[$class]->output[$i]['text'],
                                        'value' => $GLOBALS[$class]->output[$i]['value'],
                                        'sort_order' => $GLOBALS[$class]->sort_order);
                                }
                            }
                        }
                    }
                }
                
                $sql_data_array = array('customers_id' => $customer_id,
                    'customers_name' => $order->customer['firstname'] . ' ' . $order->customer['lastname'],
                    'customers_company' => $order->customer['company'],
                    'customers_street_address' => $order->customer['street_address'],
                    'customers_suburb' => $order->customer['suburb'],
                    'customers_city' => $order->customer['city'],
                    'customers_postcode' => $order->customer['postcode'],
                    'customers_state' => $order->customer['state'],
                    'customers_country' => $order->customer['country']['title'],
                    'customers_telephone' => $order->customer['telephone'],
                    'customers_email_address' => $order->customer['email_address'],
                    'customers_address_format_id' => $order->customer['format_id'],
                    'delivery_name' => $order->delivery['firstname'] . ' ' . $order->delivery['lastname'],
                    'delivery_company' => $order->delivery['company'],
                    'delivery_street_address' => $order->delivery['street_address'],
                    'delivery_suburb' => $order->delivery['suburb'],
                    'delivery_city' => $order->delivery['city'],
                    'delivery_postcode' => $order->delivery['postcode'],
                    'delivery_state' => $order->delivery['state'],
                    'delivery_country' => $order->delivery['country']['title'],
                    'delivery_address_format_id' => $order->delivery['format_id'],
                    'billing_name' => $order->billing['firstname'] . ' ' . $order->billing['lastname'],
                    'billing_company' => $order->billing['company'],
                    'billing_street_address' => $order->billing['street_address'],
                    'billing_suburb' => $order->billing['suburb'],
                    'billing_city' => $order->billing['city'],
                    'billing_postcode' => $order->billing['postcode'],
                    'billing_state' => $order->billing['state'],
                    'billing_country' => $order->billing['country']['title'],
                    'billing_address_format_id' => $order->billing['format_id'],
                    'payment_method' => $order->info['payment_method'],
                    'cc_type' => $order->info['cc_type'],
                    'cc_owner' => $order->info['cc_owner'],
                    'cc_number' => $order->info['cc_number'],
                    'cc_expires' => $order->info['cc_expires'],
                    'date_purchased' => 'now()',
                    'orders_status' => $order->info['order_status'],
                    'currency' => $order->info['currency'],
                    'currency_value' => $order->info['currency_value']);
                
                tep_db_perform(TABLE_ORDERS, $sql_data_array);
                
                $insert_id = tep_db_insert_id();
                
                for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
                    $sql_data_array = array('orders_id' => $insert_id,
                        'title' => $order_totals[$i]['title'],
                        'text' => $order_totals[$i]['text'],
                        'value' => $order_totals[$i]['value'],
                        'class' => $order_totals[$i]['code'],
                        'sort_order' => $order_totals[$i]['sort_order']);
                    
                    tep_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
                }
                
                for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
                    $sql_data_array = array('orders_id' => $insert_id,
                        'products_id' => tep_get_prid($order->products[$i]['id']),
                        'products_model' => $order->products[$i]['model'],
                        'products_name' => $order->products[$i]['name'],
                        'products_price' => $order->products[$i]['price'],
                        'final_price' => $order->products[$i]['final_price'],
                        'products_tax' => $order->products[$i]['tax'],
                        'products_quantity' => $order->products[$i]['qty']);
                    
                    tep_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);
                    
                    $order_products_id = tep_db_insert_id();
                    
                    $attributes_exist = '0';
                    if (isset($order->products[$i]['attributes'])) {
                        $attributes_exist = '1';
                        for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
                            if (DOWNLOAD_ENABLED == 'true') {
                                $attributes_query = "select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename
                                       from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                       left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                       on pa.products_attributes_id=pad.products_attributes_id
                                       where pa.products_id = '" . $order->products[$i]['id'] . "'
                                       and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "'
                                       and pa.options_id = popt.products_options_id
                                       and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "'
                                       and pa.options_values_id = poval.products_options_values_id
                                       and popt.language_id = '" . $languages_id . "'
                                       and poval.language_id = '" . $languages_id . "'";
                                $attributes = tep_db_query($attributes_query);
                            } else {
                                $attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa where pa.products_id = '" . $order->products[$i]['id'] . "' and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . $languages_id . "' and poval.language_id = '" . $languages_id . "'");
                            }
                            $attributes_values = tep_db_fetch_array($attributes);
                            
                            $sql_data_array = array('orders_id' => $insert_id,
                                'orders_products_id' => $order_products_id,
                                'products_options' => $attributes_values['products_options_name'],
                                'products_options_values' => $attributes_values['products_options_values_name'],
                                'options_values_price' => $attributes_values['options_values_price'],
                                'price_prefix' => $attributes_values['price_prefix']);
                            
                            tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);
                            
                            if ((DOWNLOAD_ENABLED == 'true') && isset($attributes_values['products_attributes_filename']) && tep_not_null($attributes_values['products_attributes_filename'])) {
                                $sql_data_array = array('orders_id' => $insert_id,
                                    'orders_products_id' => $order_products_id,
                                    'orders_products_filename' => $attributes_values['products_attributes_filename'],
                                    'download_maxdays' => $attributes_values['products_attributes_maxdays'],
                                    'download_count' => $attributes_values['products_attributes_maxcount']);
                                
                                tep_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $sql_data_array);
                            }
                        }
                    }
                }
                
                $cart_moneta_ID = $cartID . '-' . $insert_id;
                tep_session_register('cart_moneta_ID');
            }
        }
        
        return false;
    }
    /**
     * To generate the process order button for customers to pay
     */
    function process_button() {
        global $order,$customer_id,$languages_id,$cart_moneta_ID;
        if(empty($this->merchant_id) || empty($this->merchant_passwd) || empty($this->merchant_brand_id)){
            $payment_error_return = 'payment_error=' . $this->code . '&code=' . 'C';
            ini_set('display_errors', 'Off');
            error_reporting(0);
            $page_to_redirect = tep_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false);
            echo '<meta http-equiv="refresh" content="1;url='. $page_to_redirect.'" />';
            header('Refresh: 1;url=' . $page_to_redirect);
            return false;
        }
        $order_id = substr($cart_moneta_ID, strpos($cart_moneta_ID, '-')+1);
        //to get the current language's code
        $lang_query = tep_db_query("select code from " . TABLE_LANGUAGES . " where languages_id = '" . (int)$languages_id . "'");
        $lang = tep_db_fetch_array($lang_query);
        if(empty($lang) || !isset($lang['code'])){
            $current_language = 'en';
        }else{
            $current_language = strtolower($lang['code']);
        }
        
        $post_data = array();
        $post_data['merchantId'] = trim($this->merchant_id);
        $post_data['merchantTxId'] = substr(md5(uniqid(mt_rand(), true)), 0, 20);
        $post_data['password'] = trim($this->merchant_passwd);
        $post_data['brandId'] = trim($this->merchant_brand_id);
        
        $post_data['customerId'] = $customer_id;
        $post_data['allowOriginUrl'] = $this->get_allow_origin_url();
        $post_data['merchantLandingPageUrl'] = html_entity_decode(tep_href_link('ext/modules/payment/moneta/land.php', tep_session_name().'=' . tep_session_id().'&order_id='.$order_id, 'SSL', false), ENT_QUOTES, 'UTF-8');
        $post_data['merchantNotificationUrl'] = html_entity_decode(tep_href_link('ext/modules/payment/moneta/callback.php', 'order_id='.$order_id, 'SSL', false), ENT_QUOTES, 'UTF-8');
        $post_data['timestamp'] = round(microtime(true) * 1000);
        $post_data['channel'] = 'ECOM';
        $post_data['language'] = $current_language;
        $post_data['amount'] = round($order->info['total'],2);;
        $post_data['paymentSolutionId'] = '';
        $post_data['currency'] = $order->info['currency'];
        $post_data['country'] = html_entity_decode($order->billing['country']['iso_code_2'], ENT_QUOTES, 'UTF-8');
        $post_data['customerFirstName'] = html_entity_decode($order->billing['firstname'], ENT_QUOTES, 'UTF-8');
        $post_data['customerLastName'] = html_entity_decode($order->billing['lastname'], ENT_QUOTES, 'UTF-8');
        $post_data['customerEmail'] = html_entity_decode($order->customer['email_address'], ENT_QUOTES, 'UTF-8');
        $post_data['customerPhone'] = html_entity_decode($order->customer['telephone'], ENT_QUOTES, 'UTF-8');
        $post_data['userDevice'] = 'DESKTOP';
        $post_data['userAgent'] = getenv('HTTP_USER_AGENT');
        $ip = tep_get_ip_address();
        if($ip == '::1' || $ip == null){
            $ip = '127.0.0.1';
        }
        $post_data['customerIPAddress'] = $ip;
        $post_data['customerAddressHouseName'] = substr(html_entity_decode($order->customer['street_address'], ENT_QUOTES, 'UTF-8'),0,45);
        $post_data['customerAddressStreet'] = substr(html_entity_decode($order->customer['street_address'], ENT_QUOTES, 'UTF-8'),0,45);
        $post_data['customerAddressCity'] = html_entity_decode($order->customer['city'], ENT_QUOTES, 'UTF-8');
        $post_data['customerAddressPostalCode'] = html_entity_decode($order->customer['postcode'], ENT_QUOTES, 'UTF-8');
        $post_data['customerAddressCountry'] = html_entity_decode($order->customer['country']['iso_code_2'], ENT_QUOTES, 'UTF-8');
        $post_data['customerAddressState'] = html_entity_decode($order->customer['state'], ENT_QUOTES, 'UTF-8');
        $post_data['customerAddressPhone'] = html_entity_decode($order->customer['telephone'], ENT_QUOTES, 'UTF-8');
        $post_data['merchantChallengeInd'] = '01';
        $post_data['merchantDecReqInd'] = 'N';
        try {
            $this->init_config();
            $payments = (new MonetaPayments\Payments())->environmentUrls($this->environment_params);
            if(MODULE_PAYMENT_MONETA_PAYMENT_ACTION == 'auth'){
                $post_data['action'] = "AUTH";
                $payments_request = $payments->auth();
            }else {
                $post_data['action'] = "PURCHASE";
                $payments_request = $payments->purchase();
            }
            $this->logging(json_encode($post_data));
            $payments_request->merchantTxId($post_data['merchantTxId'])->
            brandId($post_data['brandId'])->
            action($post_data['action'])->
            customerId($post_data['customerId'])->
            allowOriginUrl($post_data['allowOriginUrl'])->
            merchantLandingPageUrl($post_data['merchantLandingPageUrl'])->
            merchantNotificationUrl($post_data['merchantNotificationUrl'])->
            channel($post_data['channel'])->
            language($post_data['language'])->
            amount($post_data['amount'])->
            paymentSolutionId($post_data['paymentSolutionId'])->
            currency($post_data['currency'])->
            country($post_data['country'])->
            customerFirstName($post_data['customerFirstName'])->
            customerLastName($post_data['customerLastName'])->
            customerEmail($post_data['customerEmail'])->
            customerPhone($post_data['customerPhone'])->
            userDevice($post_data['userDevice'])->
            userAgent($post_data['userAgent'])->
            customerIPAddress($post_data['customerIPAddress'])->
            customerAddressHouseName($post_data['customerAddressHouseName'])->
            customerAddressStreet($post_data['customerAddressStreet'])->
            customerAddressCity($post_data['customerAddressHouseName'])->
            customerAddressPostalCode($post_data['customerAddressPostalCode'])->
            customerAddressCountry($post_data['customerAddressCountry'])->
            customerAddressState($post_data['customerAddressState'])->
            customerAddressPhone($post_data['customerAddressPhone'])->
            merchantChallengeInd($post_data['merchantChallengeInd'])->
            merchantDecReqInd($post_data['merchantDecReqInd']);
            $res = $payments_request->token();
        } catch (Exception $e) {
            $this->logging($e->getMessage());
            $payment_error_return = 'payment_error=' . $this->code .'&message='.$e->getMessage();
            ini_set('display_errors', 'Off');
            error_reporting(0);
            $page_to_redirect = tep_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false);
            echo '<meta http-equiv="refresh" content="1;url='. $page_to_redirect.'" />';
            header('Refresh: 1;url=' . $page_to_redirect);
            return false;
        }
        $params = array();
        $params['token'] = $res['token'];
        $params['merchantId'] = $post_data['merchantId'];
        $params['integrationMode'] = $this->get_payment_mode();
        
        $input_form_string = '';
        foreach ($params as $k => $v) {
            $input_form_string.=tep_draw_hidden_field($k,$v);
        }
        
        return $input_form_string;
    }
    /**
     * Before Process Request to the Gateway, check if all the admin settings are good etc
     * @return bool
     */
    public function before_process()
    {
        return false;
    }
    function after_process()
    {
        return false;
    }
    /**
     * Builds Checkout Process Error Message
     * @return array
     */
    function get_error()
    {
        global $HTTP_GET_VARS;
        if(isset($HTTP_GET_VARS['message'])){
            return array(
                'title' => MODULE_PAYMENT_MONETA_CHECKOUT_ERROR_TITLE,
                'error' => $HTTP_GET_VARS['message']
            );
        }
        if ($HTTP_GET_VARS['code'] == 'T') {
            $errorMessage = MODULE_PAYMENT_MONETA_TEXT_ERROR_MESSAGE_T;
        } elseif ($HTTP_GET_VARS['code'] == 'C') {
            $errorMessage = MODULE_PAYMENT_MONETA_TEXT_ERROR_MESSAGE_C;
        } else {
            $errorMessage = MODULE_PAYMENT_MONETA_TEXT_ERROR_MESSAGE;
        }
        return array(
            'title' => MODULE_PAYMENT_MONETA_CHECKOUT_ERROR_TITLE,
            'error' => $errorMessage
        );
    }
    function reset_cart(){
        global $cart;
        $cart->reset(true);
    }
    /**
     * Get the shop's url
     * @return array
     */
    private function get_allow_origin_url(){
        $parse_result = parse_url(HTTPS_SERVER);
        if(isset($parse_result['port'])){
            $allowOriginUrl = $parse_result['scheme']."://".$parse_result['host'].":".$parse_result['port'];
        }else{
            $allowOriginUrl = $parse_result['scheme']."://".$parse_result['host'];
        }
        return $allowOriginUrl;
    }
    
    // init the SDK configuration settings
    private function init_config(){
        $this->environment_params['merchantId'] =  trim($this->merchant_id);
        $this->environment_params['password'] = trim($this->merchant_passwd);
        $testmode = $this->get_sandbox_environment();
        if ($testmode){
            $this->environment_params['tokenURL'] = $this->payment_moneta_test_token_url;
            $this->environment_params['paymentsURL'] = $this->payment_moneta_test_payments_url;
            $this->environment_params['baseUrl'] = $this->payment_moneta_test_cashier_url;
            $this->environment_params['jsApiUrl'] = $this->payment_moneta_test_javascript_url;
        }else{
            $this->environment_params['tokenURL'] = $this->payment_moneta_token_url;
            $this->environment_params['paymentsURL'] = $this->payment_moneta_payments_url;
            $this->environment_params['baseUrl'] = $this->payment_moneta_cashier_url;
            $this->environment_params['jsApiUrl'] = $this->payment_moneta_javascript_url;
        }
    }
    //to get if the environment is for sandbox or live
    public function get_sandbox_environment(){
        if(MODULE_PAYMENT_MONETA_SANDBOX_ACTION == 'True'){
           return true;
        }else{
            return false;
        }
    }
    //to get the gateway's javascript source file
    public function get_javascript_url(){
        if(!$this->get_sandbox_environment()){
            return $this->payment_moneta_javascript_url;
        }else{
            return $this->payment_moneta_test_javascript_url;
        }
    }
    //to get the gateway's cashier url
    public function get_cashier_url(){
        if(!$this->get_sandbox_environment()){
            return $this->payment_moneta_cashier_url;
        }else{
            return $this->payment_moneta_test_cashier_url;
        }
    }
    //to get the payment mode
    public function get_payment_mode(){
        if(MODULE_PAYMENT_MONETA_PAYMENT_MODE == 'hostedPayPage'){
            return 'hostedPayPage';
        }else if(MODULE_PAYMENT_MONETA_PAYMENT_MODE == 'iframe'){
            return 'iframe';
        }else{
            return 'standalone';
        }
    }
    
    //to debug
    public function logging($string = false) {
        $to_log = false ;
        if($to_log != true){
            return;
        }
        if(!$string) {
            $string = PHP_EOL.PHP_EOL;
        } else {
            $string = "[".date('Y-m-d H:i:s')."] ".$string;
        }
        @file_put_contents('ipg.log', PHP_EOL.$string.PHP_EOL, FILE_APPEND);
    }
    //to check the order status
    public function check_status($order_id,$merchant_tx_id,$amount,$session_name=''){
        try {
            $this->init_config();
            $payments = (new MonetaPayments\Payments())->environmentUrls($this->environment_params);
            $status_check = $payments->status_check();
            $status_check->merchantTxId($merchant_tx_id)->
            allowOriginUrl($this->get_allow_origin_url());
            $result = $status_check->execute();
            
            $this->logging('checkStatus for order:'.$order_id.',result :'.$result->result.' Status:'.$result->status.'  '.json_encode($result));
            tep_session_unregister('payment');
            if(!isset($result->result) || $result->result != 'success'){
                tep_session_unregister('payment');
                tep_session_unregister('cart_moneta_ID');
                //the order payment was declined or canceled.
//                 tep_db_query("UPDATE " . TABLE_ORDERS . " SET `orders_status` = '" . (MODULE_PAYMENT_MONETA_FAIL_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_MONETA_FAIL_ORDER_STATUS_ID : (int)DEFAULT_ORDERS_STATUS_ID) . "', `last_modified` = now() WHERE `orders_id` = '" . (int)$order_id . "'");
                $payment_error_return = 'payment_error=' . $this->code;
                tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
            }else{
                if($this->checkTransaction($merchant_tx_id)){
                    global $cart;
                    $cart->reset(true);
                    
                    tep_session_unregister('sendto');
                    tep_session_unregister('billto');
                    tep_session_unregister('shipping');
                    tep_session_unregister('comments');
                    
                    tep_session_unregister('cart_moneta_ID');
                    //the order has been processed by callback notification, then redirect to success page
                    $redirect_url = tep_href_link(FILENAME_CHECKOUT_SUCCESS, tep_session_name() . '=' . $session_name , 'SSL', false);
                    tep_redirect($redirect_url);
                    exit;
                }
                if($result->status == 'SET_FOR_CAPTURE' || $result->status == 'CAPTURED'){
                    //PURCHASE was successful
                    tep_db_query("UPDATE " . TABLE_ORDERS . " SET `orders_status` = '" . (MODULE_PAYMENT_MONETA_SUCCESS_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_MONETA_SUCCESS_ORDER_STATUS_ID : (int)DEFAULT_ORDERS_STATUS_ID) . "', `last_modified` = now() WHERE `orders_id` = '" . (int)$order_id . "'");
                    $comments = '';
                    $sql_data_array = array('orders_id' => $order_id,
                        'orders_status_id' => (MODULE_PAYMENT_MONETA_SUCCESS_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_MONETA_SUCCESS_ORDER_STATUS_ID : (int)DEFAULT_ORDERS_STATUS_ID),
                        'date_added' => 'now()',
                        'customer_notified' => (SEND_EMAILS == 'true') ? '1' : '0',
                        'comments' => $comments);
                    tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
                    
                    $gateway_order_array = array();
                    $gateway_order_array['order_id'] = $order_id;
                    $gateway_order_array['merchant_tx_id'] = $merchant_tx_id;
                    $gateway_order_array['created'] = 'now()';
                    $gateway_order_array['modified'] = 'now()';
                    $gateway_order_array['capture_status'] = 1;
                    $gateway_order_array['currency_code'] = $order_id;
                    $gateway_order_array['total'] = $amount;
                    tep_db_perform('moneta_order', $gateway_order_array);
                    $insert_id = tep_db_insert_id();
                    $this->logging('insert_id:'.$insert_id.' gateway_order_array: '.json_encode($gateway_order_array));
                    $gateway_transaction_array = array();
                    $gateway_transaction_array['moneta_order_id'] = $insert_id;
                    $gateway_transaction_array['created'] = 'now()';
                    $gateway_transaction_array['type'] = 'payment';
                    $gateway_transaction_array['amount'] = $amount;
                    tep_db_perform('moneta_transactions', $gateway_transaction_array);
                    
                    $this->success_process($order_id,$merchant_tx_id);
                    tep_redirect(tep_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));
                }else if($result->status == 'NOT_SET_FOR_CAPTURE'){
                    // AUTH was successful
                    tep_db_query("UPDATE " . TABLE_ORDERS . " SET `orders_status` = '" . (MODULE_PAYMENT_MONETA_AUTH_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_MONETA_AUTH_ORDER_STATUS_ID : (int)DEFAULT_ORDERS_STATUS_ID) . "', `last_modified` = now() WHERE `orders_id` = '" . (int)$order_id . "'");
                    $comments = '';
                    $sql_data_array = array('orders_id' => $order_id,
                        'orders_status_id' => (MODULE_PAYMENT_MONETA_AUTH_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_MONETA_AUTH_ORDER_STATUS_ID : (int)DEFAULT_ORDERS_STATUS_ID),
                        'date_added' => 'now()',
                        'customer_notified' => (SEND_EMAILS == 'true') ? '1' : '0',
                        'comments' => $comments);
                    tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);                        
                    $gateway_order_array = array();
                    $gateway_order_array['order_id'] = $order_id;;
                    $gateway_order_array['merchant_tx_id'] = $merchant_tx_id;
                    $gateway_order_array['created'] = 'now()';
                    $gateway_order_array['modified'] = 'now()';
                    $gateway_order_array['currency_code'] = $order_id;
                    $gateway_order_array['total'] = $amount;
                    tep_db_perform('moneta_order', $gateway_order_array);
                    $insert_id = tep_db_insert_id();
                    $this->logging('insert_id:'.$insert_id.' gateway_order_array: '.json_encode($gateway_order_array));
                    $gateway_transaction_array = array();
                    $gateway_transaction_array['moneta_order_id'] = $insert_id;
                    $gateway_transaction_array['created'] = 'now()';
                    $gateway_transaction_array['type'] = 'auth';
                    $gateway_transaction_array['amount'] = $amount;
                    tep_db_perform('moneta_transactions', $gateway_transaction_array);
                    
                    $this->success_process($order_id,$merchant_tx_id);
                    tep_redirect(tep_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));
                }else{
                    tep_session_unregister('payment');
                    tep_session_unregister('cart_moneta_ID');
                    if($result->status == "STARTED" || $result->status == "WAITING_RESPONSE" || $result->status == "INCOMPLETE"){
                        //Do not handle these order status in the plugin system
                        $payment_error_return = 'payment_error=' . $this->code .'&message=WAITING_RESPONSE';
                        tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
                    }else{
                        $fail_status_query = tep_db_query("SELECT 1 FROM " . TABLE_ORDERS . " WHERE `orders_status`= '".MODULE_PAYMENT_MONETA_FAIL_ORDER_STATUS_ID."' AND `orders_id` = '" . (int)$order_id . "'");
                        //to check if the fail order status has been set already, avoid to repeat setting the status
                        if (tep_db_num_rows($fail_status_query) < 1) {
                            //the order payment was declined or canceled.
                            tep_db_query("UPDATE " . TABLE_ORDERS . " SET `orders_status` = '" . (MODULE_PAYMENT_MONETA_FAIL_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_MONETA_FAIL_ORDER_STATUS_ID : (int)DEFAULT_ORDERS_STATUS_ID) . "', `last_modified` = now() WHERE `orders_id` = '" . (int)$order_id . "'");
                            $sql_data_array = array('orders_id' => $order_id,
                                'orders_status_id' => (MODULE_PAYMENT_MONETA_FAIL_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_MONETA_FAIL_ORDER_STATUS_ID : (int)DEFAULT_ORDERS_STATUS_ID),
                                'date_added' => 'now()',
                                'customer_notified' => (SEND_EMAILS == 'true') ? '1' : '0',
                                'comments' => '');
                            tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
                        }
                        $payment_error_return = 'payment_error=' . $this->code;
                        tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
                        exit;
                    }
                    
                }
            }
        } catch (Exception $e) {
            tep_session_unregister('payment');
            tep_session_unregister('cart_moneta_ID');
            $payment_error_return = 'payment_error=' . $this->code .'&message='.$e->getMessage();
            tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
        }
    }
    
    
    //process the order payment
    function success_process($order_id=0,$transaction_id=0,$sendto=0,$billto=0, $comms='')
    {
        global $customer_id, $order, $order_totals, $languages_id, $payment, $currencies, $cart, $cart_moneta_ID;
        
        require_once(DIR_WS_CLASSES . 'order.php');
        $order = new order($order_id);
        $order_totals = $order->totals;
        $customer_id = $order->customer['id'];
        require_once(DIR_WS_CLASSES . 'language.php');
        
        
        
        
        /* FROM HERE */
        
        
        // initialized for the email confirmation
        $products_ordered = '';
        $subtotal = 0;
        $total_tax = 0;
        $n = count($order->products);
        for ($i = 0 ; $i < $n; $i++) {
            if (STOCK_LIMITED == 'true') {
                if (DOWNLOAD_ENABLED == 'true') {
                    $stock_query_raw = "SELECT products_quantity, pad.products_attributes_filename
                                FROM " . TABLE_PRODUCTS . " p
                                LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                ON p.products_id=pa.products_id
                                LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                ON pa.products_attributes_id=pad.products_attributes_id
                                WHERE p.products_id = '" . tep_get_prid($order->products[$i]['id']) . "'";
                    // Will work with only one option for downloadable products
                    // otherwise, we have to build the query dynamically with a loop
                    $products_attributes = $order->products[$i]['attributes'];
                    if (is_array($products_attributes)) {
                        $stock_query_raw .= " AND pa.options_id = '" . $products_attributes[0]['option_id'] . "' AND pa.options_values_id = '" . $products_attributes[0]['value_id'] . "'";
                    }
                    $stock_query = tep_db_query($stock_query_raw);
                } else {
                    $stock_query = tep_db_query("SELECT `products_quantity` FROM `" . TABLE_PRODUCTS . "` WHERE `products_id` = '" . tep_get_prid($order->products[$i]['id']) . "'");
                }
                if (tep_db_num_rows($stock_query) > 0) {
                    $stock_values = tep_db_fetch_array($stock_query);
                    // do not decrement quantities if products_attributes_filename exists
                    if ((DOWNLOAD_ENABLED != 'true') || (!$stock_values['products_attributes_filename'])) {
                        $stock_left = $stock_values['products_quantity'] - $order->products[$i]['qty'];
                    } else {
                        $stock_left = $stock_values['products_quantity'];
                    }
                    tep_db_query("UPDATE " . TABLE_PRODUCTS . " SET `products_quantity` = '" . $stock_left . "' WHERE `products_id` = '" . tep_get_prid($order->products[$i]['id']) . "'");
                    if (($stock_left < 1) && (STOCK_ALLOW_CHECKOUT == 'false')) {
                        tep_db_query("UPDATE " . TABLE_PRODUCTS . " SET `products_status` = '0' WHERE `products_id` = '" . tep_get_prid($order->products[$i]['id']) . "'");
                    }
                }
            }
            
            // Update products_ordered (for bestsellers list)
            tep_db_query("UPDATE " . TABLE_PRODUCTS . " SET `products_ordered` = `products_ordered` + " . sprintf('%d', $order->products[$i]['qty']) . " WHERE `products_id` = '" . tep_get_prid($order->products[$i]['id']) . "'");
            
            //------insert customer choosen option to order--------
            $attributes_exist = '0';
            $products_ordered_attributes = '';
            if (isset($order->products[$i]['attributes'])) {
                $attributes_exist = '1';
                for ($j = 0, $n2 = sizeof($order->products[$i]['attributes']); $j < $n2; $j++) {
                    if (DOWNLOAD_ENABLED == 'true') {
                        $attributes_query = "SELECT popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename
                                   FROM " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                   LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                   ON pa.products_attributes_id=pad.products_attributes_id
                                   WHERE pa.products_id = '" . $order->products[$i]['id'] . "'
                                   AND pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "'
                                   AND pa.options_id = popt.products_options_id
                                   AND pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "'
                                   AND pa.options_values_id = poval.products_options_values_id
                                   AND popt.language_id = '" . $languages_id . "'
                                   AND poval.language_id = '" . $languages_id . "'";
                        $attributes = tep_db_query($attributes_query);
                    } else {
                        $attributes = tep_db_query("SELECT popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix FROM " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa WHERE pa.products_id = '" . $order->products[$i]['id'] . "' AND pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' AND pa.options_id = popt.products_options_id AND pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' AND pa.options_values_id = poval.products_options_values_id AND popt.language_id = '" . $languages_id . "' AND poval.language_id = '" . $languages_id . "'");
                    }
                    $attributes_values = tep_db_fetch_array($attributes);
                    
                    $products_ordered_attributes .= "\n\t" . $attributes_values['products_options_name'] . ' ' . $attributes_values['products_options_values_name'];
                }
            }
            //------insert customer choosen option eof ----
            $total_weight += ($order->products[$i]['qty'] * $order->products[$i]['weight']);
            $total_tax += tep_calculate_tax($total_products_price, $products_tax) * $order->products[$i]['qty'];
            $total_cost += $total_products_price;
            
            $products_ordered .= $order->products[$i]['qty'] . ' x ' . $order->products[$i]['name'] . ' (' . $order->products[$i]['model'] . ') = ' . $currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']) . $products_ordered_attributes . "\n";
        }
        
        // lets start with the email confirmation
        $email_order = STORE_NAME . "\n" .
            EMAIL_SEPARATOR . "\n" .
            EMAIL_TEXT_ORDER_NUMBER . ' ' . $insert_id . "\n" .
            EMAIL_TEXT_INVOICE_URL . ' ' . tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $insert_id, 'SSL', false) . "\n" .
            EMAIL_TEXT_DATE_ORDERED . ' ' . strftime(DATE_FORMAT_LONG) . "\n\n";
            if ($order->info['comments']) {
                $email_order .= tep_db_output($order->info['comments']) . "\n\n";
            }
            $email_order .= EMAIL_TEXT_PRODUCTS . "\n" .
                EMAIL_SEPARATOR . "\n" .
                $products_ordered .
                EMAIL_SEPARATOR . "\n";
                
                for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
                    $email_order .= strip_tags($order_totals[$i]['title']) . ' ' . strip_tags($order_totals[$i]['text']) . "\n";
                }
                
                if ($order->content_type != 'virtual') {
                    $email_order .= "\n" . EMAIL_TEXT_DELIVERY_ADDRESS . "\n" .
                        EMAIL_SEPARATOR . "\n" .
                        tep_address_label($customer_id, $sendto, 0, '', "\n") . "\n";
                }
                
                $email_order .= "\n" . EMAIL_TEXT_BILLING_ADDRESS . "\n" .
                    EMAIL_SEPARATOR . "\n" .
                    tep_address_label($customer_id, $billto, 0, '', "\n") . "\n\n";
//                     if (is_object($$payment)) {
                        $email_order .= EMAIL_TEXT_PAYMENT_METHOD . "\n" .
                            EMAIL_SEPARATOR . "\n";
                            $email_order .= $order->info['payment_method'] . "\n\n";
                            if ($this->email_footer) {
                                $email_order .= $this->email_footer . "\n\n";
                            }
//                     }
                    tep_mail($order->customer['firstname'] . ' ' . $order->customer['lastname'], $order->customer['email_address'], EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
                    
                    // send emails to other people
                    if (SEND_EXTRA_ORDER_EMAILS_TO != '') {
                        tep_mail('', SEND_EXTRA_ORDER_EMAILS_TO, EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
                    }
                       
                        $cart->reset(true);
                        
                        // unregister session variables used during checkout
                        tep_session_unregister('sendto');
                        tep_session_unregister('billto');
                        tep_session_unregister('shipping');
                        tep_session_unregister('payment');
                        tep_session_unregister('comments');
                        
                        tep_session_unregister('cart_moneta_ID');
                        return true;
    }
    
    // the method to insert the payment gateway related order status into the oscommerce database
    function insert_statuses(){
        //Auth status
        $check_query = tep_db_query("SELECT orders_status_id FROM " . TABLE_ORDERS_STATUS . " WHERE orders_status_name = 'On-hold [Moneta]' limit 1");
        if (tep_db_num_rows($check_query) < 1) {
            $status_query = tep_db_query("SELECT max(orders_status_id) as status_id FROM " . TABLE_ORDERS_STATUS);
            $status = tep_db_fetch_array($status_query);
            
            $this->auth_status_id = $status['status_id'] + 1;
            
            $languages = tep_get_languages();
            
            for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
                tep_db_query("INSERT INTO " . TABLE_ORDERS_STATUS . " (orders_status_id, language_id, orders_status_name) VALUES ('" . $this->auth_status_id . "', '" . $languages[$i]['id'] . "', 'On-hold [Moneta]')");
            }
            
            $flags_query = tep_db_query("DESCRIBE " . TABLE_ORDERS_STATUS . " public_flag");
            if (tep_db_num_rows($flags_query) == 1) {
                tep_db_query("UPDATE " . TABLE_ORDERS_STATUS . " SET `public_flag` = '1',`downloads_flag` = '1' WHERE `orders_status_id` = '" . $this->auth_status_id . "'");
            }
        } else {
            $check = tep_db_fetch_array($check_query);
            
            $this->auth_status_id = $check['orders_status_id'];
        }
        
        //Purchased status
        $check_query = tep_db_query("SELECT orders_status_id FROM " . TABLE_ORDERS_STATUS . " WHERE orders_status_name = 'Complete [Moneta]' limit 1");
        if (tep_db_num_rows($check_query) < 1) {
            $status_query = tep_db_query("SELECT max(orders_status_id) as status_id FROM " . TABLE_ORDERS_STATUS);
            $status = tep_db_fetch_array($status_query);
            
            $this->complete_status_id = $status['status_id'] + 1;
            
            $languages = tep_get_languages();
            
            for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
                tep_db_query("INSERT INTO " . TABLE_ORDERS_STATUS . " (orders_status_id, language_id, orders_status_name) VALUES ('" . $this->complete_status_id . "', '" . $languages[$i]['id'] . "', 'Complete [Moneta]')");
            }
            
            $flags_query = tep_db_query("DESCRIBE " . TABLE_ORDERS_STATUS . " public_flag");
            if (tep_db_num_rows($flags_query) == 1) {
                tep_db_query("UPDATE " . TABLE_ORDERS_STATUS . " SET `public_flag` = '1', `downloads_flag` = '1' WHERE `orders_status_id` = '" . $this->complete_status_id . "'");
            }
        } else {
            $check = tep_db_fetch_array($check_query);
            
            $this->complete_status_id = $check['orders_status_id'];
        }
        
        //Refunded status
        $check_query = tep_db_query("SELECT orders_status_id FROM " . TABLE_ORDERS_STATUS . " WHERE orders_status_name = 'Refunded [Moneta]' limit 1");
        if (tep_db_num_rows($check_query) < 1) {
            $status_query = tep_db_query("SELECT max(orders_status_id) as status_id FROM " . TABLE_ORDERS_STATUS);
            $status = tep_db_fetch_array($status_query);
            
            $this->refunded_status_id = $status['status_id'] + 1;
            
            $languages = tep_get_languages();
            
            for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
                tep_db_query("INSERT INTO " . TABLE_ORDERS_STATUS . " (orders_status_id, language_id, orders_status_name) VALUES ('" . $this->refunded_status_id . "', '" . $languages[$i]['id'] . "', 'Refunded [Moneta]')");
            }
            
            $flags_query = tep_db_query("DESCRIBE " . TABLE_ORDERS_STATUS . " public_flag");
            
            if (tep_db_num_rows($flags_query) == 1) {
                tep_db_query("UPDATE " . TABLE_ORDERS_STATUS . " SET `public_flag` = '1', `downloads_flag` = '1' WHERE `orders_status_id` = '" . $this->refunded_status_id . "'");
            }
        } else {
            $check = tep_db_fetch_array($check_query);
            
            $this->refunded_status_id = $check['orders_status_id'];
        }
        
        //Voided status
        $check_query = tep_db_query("SELECT orders_status_id FROM " . TABLE_ORDERS_STATUS . " WHERE orders_status_name = 'Voided [Moneta]' limit 1");
        if (tep_db_num_rows($check_query) < 1) {
            $status_query = tep_db_query("SELECT max(orders_status_id) as status_id FROM " . TABLE_ORDERS_STATUS);
            $status = tep_db_fetch_array($status_query);
            
            $this->voided_status_id = $status['status_id'] + 1;
            
            $languages = tep_get_languages();
            
            for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
                tep_db_query("INSERT INTO " . TABLE_ORDERS_STATUS . " (orders_status_id, language_id, orders_status_name) VALUES ('" . $this->voided_status_id . "', '" . $languages[$i]['id'] . "', 'Voided [Moneta]')");
            }
            
            $flags_query = tep_db_query("DESCRIBE " . TABLE_ORDERS_STATUS . " public_flag");
            
            if (tep_db_num_rows($flags_query) == 1) {
                tep_db_query("UPDATE " . TABLE_ORDERS_STATUS . " SET `public_flag` = '1', `downloads_flag` = '1' WHERE `orders_status_id` = '" . $this->voided_status_id . "'");
            }
        } else {
            $check = tep_db_fetch_array($check_query);
            
            $this->voided_status_id = $check['orders_status_id'];
        }
        
        //Failed status
        $check_query = tep_db_query("SELECT orders_status_id FROM " . TABLE_ORDERS_STATUS . " WHERE orders_status_name = 'Failed [Moneta]' limit 1");
        if (tep_db_num_rows($check_query) < 1) {
            $status_query = tep_db_query("SELECT max(orders_status_id) as status_id FROM " . TABLE_ORDERS_STATUS);
            $status = tep_db_fetch_array($status_query);
            
            $this->failed_status_id = $status['status_id'] + 1;
            
            $languages = tep_get_languages();
            
            for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
                tep_db_query("INSERT INTO " . TABLE_ORDERS_STATUS . " (orders_status_id, language_id, orders_status_name) VALUES ('" . $this->failed_status_id . "', '" . $languages[$i]['id'] . "', 'Failed [Moneta]')");
            }
            
            $flags_query = tep_db_query("DESCRIBE " . TABLE_ORDERS_STATUS . " public_flag");
            
            if (tep_db_num_rows($flags_query) == 1) {
                tep_db_query("UPDATE " . TABLE_ORDERS_STATUS . " SET `public_flag` = '1', `downloads_flag` = '1' WHERE `orders_status_id` = '" . $this->failed_status_id . "'");
            }
        } else {
            $check = tep_db_fetch_array($check_query);
            
            $this->failed_status_id = $check['orders_status_id'];
        }
        
        
        
    }
    //check if this transaction has been processed or not
    function checkTransaction($merchant_tx_id=0){
        $transaction = tep_db_query("SELECT 1 FROM `moneta_order` WHERE `merchant_tx_id`='" . $merchant_tx_id . "'");
        return tep_db_num_rows($transaction);
    }
    /**
     * Check or Patch Admin Orders File (Allow displaying Order Transactions Panel)
     * @param bool $shouldOnlyCheckIfPatched
     * @return bool
     */
    protected function doCheckAndPatchOrdersCoreTemplateFile($shouldOnlyCheckIfPatched = false)
    {
        
        $orderTransactionsPanelAutoLoadSearchBlock =
        "require_once \$monetaOrderTransactionsPanelFile;";
        
        $orderTransactionsPanelAutoLoadIncludeBlock =
        "\$monetaOrderTransactionsPanelFile = DIR_FS_ADMIN . \"ext/modules/payment/moneta/order_transactions_panel.php\";
  if (file_exists(\$monetaOrderTransactionsPanelFile)) {
      {$orderTransactionsPanelAutoLoadSearchBlock}
  }
  ";
      
      $templateBottomAutoLoad =
      "require(DIR_WS_INCLUDES . 'template_bottom.php');";
      
      $ordersPHPFile = DIR_FS_ADMIN . "orders.php";
      
      $fileContent = $this->getFileContent($ordersPHPFile);
      
      if ($this->getFileContainsText($fileContent, $orderTransactionsPanelAutoLoadSearchBlock)) {
          //orders.php already extended
          return true;
      }
      
      if ($shouldOnlyCheckIfPatched) {
          return false;
      }
      
      if ($this->getFileContainsText($fileContent, $templateBottomAutoLoad)) {
          $fileContent = str_replace(
              $templateBottomAutoLoad,
              $orderTransactionsPanelAutoLoadIncludeBlock . $templateBottomAutoLoad,
              $fileContent
              );
          
          return $this->writeContentToFile($ordersPHPFile, $fileContent);
      }
      
      return false;
    }
    /**
     * Determines if a file can be overriden by the current user
     * @param string $file
     * @return bool
     */
    protected function getIsFileWritable($file)
    {
        return
        file_exists($file) &&
        is_writable($file);
    }
    /**
     * Get File Content
     * @param string $filePath
     * @return null|string
     */
    protected function getFileContent($filePath)
    {
        if (function_exists('file_get_contents')) {
            return file_get_contents($filePath);
        }
        
        return null;
    }
    
    /**
     * Override the Content of a file
     * @param string $filePath
     * @param string $content
     * @return bool
     */
    protected function writeContentToFile($filePath, $content)
    {
        try {
            if (!$this->getIsFileWritable($filePath)) {
                return false;
            }
            $handle = fopen($filePath, 'w');
            try {
                fwrite($handle, $content);
                return true;
            } finally {
                fclose($handle);
            }
        } catch (Exception $e) {
            return false;
        }
        
    }
    
    /**
     * Determines if a text fragment exists in the content of a file
     * @param string $fileContent
     * @param string $searchText
     * @return bool
     */
    protected function getFileContainsText($fileContent, $searchText)
    {
        $pattern = preg_quote($searchText, '/');
        
        $pattern = "/^.*$pattern.*\$/m";
        
        return
        preg_match_all($pattern, $fileContent, $matches) &&
        (count($matches) > 0);
    }
    
    //Capture an Auth payment
    public function capture($merchant_tx_id, $capture_amount,$order_id,$moneta_order_id) {
        try {
            $this->init_config();
            $payments = (new MonetaPayments\Payments())->environmentUrls($this->environment_params);
            $capture = $payments->capture();
            $capture->originalMerchantTxId($merchant_tx_id)->
            amount($capture_amount)->
            allowOriginUrl($this->get_allow_origin_url());
            $result = $capture->execute();
            $this->logging('capture transaction ID: '.$merchant_tx_id.' . Result:'.$result->result);
            if(!isset($result->result) || $result->result != 'success'){
                $this->logging('Error message:'.json_encode($result->errors));
                return false;
            }else{
                $gateway_transaction_array = array();
                $gateway_transaction_array['moneta_order_id'] = $moneta_order_id;
                $gateway_transaction_array['created'] = 'now()';
                $gateway_transaction_array['type'] = 'payment';
                $gateway_transaction_array['amount'] = $capture_amount;
                tep_db_perform('moneta_transactions', $gateway_transaction_array);
                tep_db_query("UPDATE `moneta_order` SET `capture_status` = '1' WHERE `order_id` = '" . $order_id . "'");
                tep_db_query("UPDATE " . TABLE_ORDERS . " SET `orders_status` = '" . (MODULE_PAYMENT_MONETA_SUCCESS_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_MONETA_SUCCESS_ORDER_STATUS_ID : (int)DEFAULT_ORDERS_STATUS_ID) . "', `last_modified` = now() WHERE `orders_id` = '" . (int)$order_id . "'");
                return true;
            }
        } catch (Exception $e) {
            $this->logging('capture transaction ID: '.$merchant_tx_id.'.Processing error:'.$e->getMessage());
            return false;
        }
    }
    //Void an Auth payment
    public function void($merchant_tx_id,$void_amount,$order_id,$moneta_order_id) {
        try {
            $this->init_config();
            $payments = (new MonetaPayments\Payments())->environmentUrls($this->environment_params);
            $void = $payments->void();
            $void->originalMerchantTxId($merchant_tx_id)->
            allowOriginUrl($this->get_allow_origin_url());
            $result = $void->execute();
            $this->logging('void transaction ID: '.$merchant_tx_id.' . Result:'.$result->result);
            if(!isset($result->result) || $result->result != 'success'){
                $this->logging('Error message:'.json_encode($result->errors));
                return false;
            }else{
                $gateway_transaction_array = array();
                $gateway_transaction_array['moneta_order_id'] = $moneta_order_id;
                $gateway_transaction_array['created'] = 'now()';
                $gateway_transaction_array['type'] = 'void';
                $gateway_transaction_array['amount'] = $void_amount;
                tep_db_perform('moneta_transactions', $gateway_transaction_array);
                tep_db_query("UPDATE `moneta_order` SET `void_status` = '1' WHERE `order_id` = '" . $order_id . "'");
                tep_db_query("UPDATE " . TABLE_ORDERS . " SET `orders_status` = '" . (MODULE_PAYMENT_MONETA_VOID_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_MONETA_VOID_ORDER_STATUS_ID : (int)DEFAULT_ORDERS_STATUS_ID) . "', `last_modified` = now() WHERE `orders_id` = '" . (int)$order_id . "'");
                return true;
            }
        } catch (Exception $e) {
            $this->logging('void transaction ID: '.$merchant_tx_id.'.Processing error:'.$e->getMessage());
            return false;
        }
    }
    //Refund or Partial refund a Purchase payment
    public function refund($merchant_tx_id, $total, $refund_amount,$order_id,$moneta_order_id) {
//         return true;
        try {
            $this->init_config();
            $payments = (new MonetaPayments\Payments())->environmentUrls($this->environment_params);
            $refund = $payments->refund();
            $refund->originalMerchantTxId($merchant_tx_id)->
            amount($refund_amount)->
            allowOriginUrl($this->get_allow_origin_url());
            $result = $refund->execute();
            $this->logging('refund transaction ID: '.$merchant_tx_id.' . Amount:'.$refund_amount.' . Result:'.$result->result.'** Total:'.$total);
            if(!isset($result->result) || $result->result != 'success'){
                if (!is_array($result->errors) && strpos($result->errors, 'Transaction not refundable: Original transaction not SUCCESS') !== false) {
                    //if the order was authorized + captured, the status in the Gateway system is still showing NOT_SET_FOR_CAPTURE, the refund can not be excuted
                    $this->logging('The refund is not available now: '.json_encode($result->errors));
                    return false;
                }else{
                    $this->logging('Error message:'.json_encode($result->errors));
                    return false;
                }
            }else{
                $gateway_transaction_array = array();
                $gateway_transaction_array['moneta_order_id'] = $moneta_order_id;
                $gateway_transaction_array['created'] = 'now()';
                $gateway_transaction_array['type'] = 'refund';
                $gateway_transaction_array['amount'] = $refund_amount;
                tep_db_perform('moneta_transactions', $gateway_transaction_array);
                
                $query = tep_db_query("SELECT SUM(`amount`) AS `total` FROM `moneta_transactions` WHERE `moneta_order_id` = '" . (int)$moneta_order_id . "' AND `type` = 'refund'");
                $order = tep_db_fetch_array($query);
                $total_refunded = $order['total'];
                $this->logging('total_refunded: '.$total_refunded);
                if($total == $total_refunded){
                    tep_db_query("UPDATE `moneta_order` SET `refund_status` = '1' WHERE `order_id` = '" . $order_id . "'");
                    tep_db_query("UPDATE " . TABLE_ORDERS . " SET `orders_status` = '" . (MODULE_PAYMENT_MONETA_REFUND_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_MONETA_REFUND_ORDER_STATUS_ID : (int)DEFAULT_ORDERS_STATUS_ID) . "', `last_modified` = now() WHERE `orders_id` = '" . (int)$order_id . "'");
                }
                
                return true;
            }
        } catch (Exception $e) {
            $this->logging('refund transaction ID: '.$merchant_tx_id.'.Processing error:'.$e->getMessage());
            return false;
        }
        
    }
 
}
