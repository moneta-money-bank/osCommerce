<?php
chdir('../../../../');
require('includes/application_top.php');
require(DIR_WS_INCLUDES . 'modules/payment/moneta.php');
$payment = new moneta();
$payment->logging('callback-POST:'.json_encode($_POST));
$payment->logging('callback-GET:'.json_encode($_GET));

if(!empty($_POST) && isset($HTTP_POST_VARS['merchantTxId'])  && isset($HTTP_GET_VARS['order_id'])){
    //the server will also call back the notification when  refund are made, this is to ignore the other action, only purchase
    if($HTTP_POST_VARS['action'] != 'PURCHASE' && $HTTP_POST_VARS['action'] != 'AUTH' && $HTTP_POST_VARS['action'] != 'CAPTURE'){
        exit;
    }
    $payment->check_status($HTTP_GET_VARS['order_id'], $HTTP_POST_VARS['merchantTxId'],$HTTP_POST_VARS['amount']);
} else {
    tep_redirect(tep_href_link(FILENAME_SHOPPING_CART, '', 'SSL', false));
    exit;
}
exit;