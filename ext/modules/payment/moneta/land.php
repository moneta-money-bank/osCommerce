<?php
chdir('../../../../');
  require('includes/application_top.php');
  ini_set('display_errors', 'Off');
  error_reporting(0);
  require(DIR_WS_INCLUDES . 'modules/payment/moneta.php');
  $payment = new moneta();
  $payment->logging('land-POST:'.json_encode($_POST));
  $payment->logging('land-GET:'.json_encode($_GET));
  $session_name = tep_session_name();
  if(!empty($_POST) && isset($HTTP_POST_VARS['merchantTxId']) && isset($HTTP_GET_VARS[$session_name]) && isset($HTTP_GET_VARS['order_id'])){
      $payment->check_status($HTTP_GET_VARS['order_id'], $HTTP_POST_VARS['merchantTxId'],$HTTP_POST_VARS['amount'],$HTTP_GET_VARS[$session_name]);
  } else {
      tep_redirect(tep_href_link(FILENAME_SHOPPING_CART, '', 'SSL', false));
      exit;
  }
  exit;
  
  
