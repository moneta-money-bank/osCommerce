<?php
chdir('../../../../');
  require('includes/application_top.php');
  ini_set('display_errors', 'Off');
  error_reporting(0);
  require(DIR_WS_INCLUDES . 'modules/payment/moneta.php');
  
  
  // if the customer is not logged on, redirect them to the shopping cart page
  if (!tep_session_is_registered('customer_id')) {
      tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
  }
  if(!isset($HTTP_POST_VARS['token']) && !isset($HTTP_POST_VARS['merchantId'])){
      tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
  }
  $payment = new moneta();

  require(DIR_WS_INCLUDES . 'template_top.php');
?>



<div class="contentContainer">

<hr>  
<div id="ipgCashierDiv"  style="height:700px;"></div>



</div>
<?php
  require(DIR_WS_INCLUDES . 'template_bottom.php');
  require(DIR_WS_INCLUDES . 'application_bottom.php');
?>
<script src="<?php echo $payment->get_javascript_url();?>"></script>
<script>
function handleResult(result,data){
	return;
	console.log(data);
}
function pay() {
    var token = "<?php echo $HTTP_POST_VARS['token']; ?>";
    var merchantId = "<?php echo $HTTP_POST_VARS['merchantId']; ?>";
    var cashier = com.myriadpayments.api.cashier();
	cashier.init({ baseUrl: '<?php echo $payment->get_cashier_url();?>' });
    cashier.show({ containerId: "ipgCashierDiv", merchantId: merchantId, token: token, successCallback: handleResult, failureCallback: handleResult, cancelCallback: handleResult });
};
pay();
</script>

