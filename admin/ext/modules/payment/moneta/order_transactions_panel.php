<?php
if (isset($_GET['oID'])) {
    $order_id = tep_db_prepare_input(
        trim($_GET['oID'])
    );
}
if (isset($order_id) && tep_not_null($order_id) && isset($_GET['action']) && $_GET['action']=='edit') {
    //check if the plugin has been installed or not
    $table_name = 'moneta_order';
    $query = tep_db_query(
        sprintf(
            "show tables like '%s'",
            $table_name
            )
        );
    if(tep_db_num_rows($query) < 1){
        return;
    }
    
    //only show it when the order is done by this Gateway
    $query = tep_db_query("SELECT * FROM `moneta_order` WHERE `order_id`='" . $order_id . "'");
    $order = tep_db_fetch_array($query);
//     var_dump($order);
    if($order){
        $transactions_query = tep_db_query("SELECT * FROM `moneta_transactions` WHERE `moneta_order_id`='" . $order['moneta_order_id'] . "'");
        $total = $order['total'];
        $refund_query = tep_db_query("SELECT SUM(`amount`) AS `refund_total` FROM `moneta_transactions` WHERE `moneta_order_id` = '" . $order['moneta_order_id'] . "' AND `type` = 'refund'");
        $refund_order = tep_db_fetch_array($refund_query);
        $total_refunded = $refund_order['refund_total'];
        $amount_unrefunded = number_format($total - $total_refunded,2,'.','');
        echo '</br>';
        echo '<img src="images/pixel_black.gif" border="0" alt="" width="100%" height="1">';
        echo '<h1>Moneta Payment</h1>';
        echo '<table border="1px" width="50%" cellspacing="0" cellpadding="15">';
        
        echo    '<tr>';
        echo        '<td class="smallText" align="center"><strong>Moneta ID</strong></td>';
        echo        '<td colspan="2" align="center">';
        echo            $order['merchant_tx_id'];
        echo        '</td>';
        echo    '</tr>';
        
        echo    '<tr>';
        echo        '<td class="smallText" align="center"><strong>Payment captured</strong></td>';
        echo        '<td colspan="2" align="center">';
        if($order['capture_status']){
            echo '<img src="images/icons/tick.gif" border="0" alt="True" title="True">';
        }else{
            echo 'NO&nbsp;';
            if($order['void_status'] == 0){
                echo '<button id="payment_capture_button" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary ui-priority-primary" role="button" ><span class="ui-button-icon-primary ui-icon ui-icon-document"></span><span class="ui-button-text">Capture</span></button>';
                echo '<span id="capture-load" class="loader-01" style="display:none"></span>';
            }
        }
        echo        '</td>';
        echo    '</tr>';
        
        echo    '<tr>';
        echo        '<td class="smallText" align="center"><strong>Payment voided</strong></td>';
        echo        '<td colspan="2" align="center">';
        if($order['void_status']){
            echo '<img src="images/icons/tick.gif" border="0" alt="True" title="True">';
        }else{
            echo 'NO&nbsp;';
            if($order['capture_status'] == 0){
                echo '<button id="payment_void_button" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary ui-priority-primary" role="button" ><span class="ui-button-icon-primary ui-icon ui-icon-document"></span><span class="ui-button-text">Void</span></button>';
                echo '<span id="void-load" class="loader-01" style="display:none"></span>';
            }
        }
        echo        '</td>';
        echo    '</tr>';
        
        echo    '<tr>';
        echo        '<td class="smallText" align="center"><strong>Payment refunded</strong></td>';
        echo        '<td colspan="2" align="center">';
        if($order['refund_status']){
            echo '<img src="images/icons/tick.gif" border="0" alt="True" title="True">';
        }else{
            if($order['capture_status'] == 1 && $order['void_status'] == 0){
                echo '<input type="text" width="5" id="refund-amount" value="'.$amount_unrefunded.'"/>';
                echo '&nbsp';
                echo '<button id="payment_refund_button" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary ui-priority-primary" role="button" ><span class="ui-button-icon-primary ui-icon ui-icon-document"></span><span class="ui-button-text">Refund</span></button>';
                echo '<span id="refund-load" class="loader-01" style="display:none"></span>';
            }else{
                echo 'NO';
                echo '&nbsp;';
            }
        }
        echo        '</td>';
        echo    '</tr>';
        
        
        echo '<tr>';
        echo    '<td class="smallText" align="center"><strong>Transactions</strong></td>';
        echo    '<td colspan="2" align="center" style="padding:0;">';
        echo        '<table border="1px" width="100%" cellspacing="0" cellpadding="5">';
        echo            '<thread>';
        echo                '<tr align="center">';
        echo                    '<td><strong>Created</strong></td>';
        echo                    '<td><strong>Type</strong></td>';
        echo                    '<td><strong>Amount</strong></td>';
        echo                '</tr>';
        echo            '</thread>';
        echo            '<tbody>';
        while ($transaction = tep_db_fetch_array($transactions_query)) {
            echo        '<tr><td class="smallText" align="center">'.$transaction['created'].'</td><td class="smallText" align="center">'.$transaction['type'].'</td><td class="smallText" align="center">'.$transaction['amount'].'</td></tr>';
        }
        echo            '</tbody>';
        echo            '<tr>';
        echo        '</table>';
        echo    '</td>';
        echo '</tr>';
        
        echo '</table>';
        echo '</br>';

    ?>
    <style type="text/css">
		.loader-01 {
			color:#197bb0;
            border: .2em dotted currentcolor;
            border-radius: 50%;
            -webkit-animation: 2s loader-01 linear infinite;
            animation: 2s loader-01 linear infinite;
            display: inline-block;
            width: 1em;
            height: 1em;
            vertical-align: middle;
            pointer-events: none;
        }
        @-webkit-keyframes loader-01 {
            0% {
                -webkit-transform: rotate(0deg);
                transform: rotate(0deg);
            }
            100% {
                -webkit-transform: rotate(360deg);
                transform: rotate(360deg);
            }
        }
        @keyframes loader-01 {
            0% {
                -webkit-transform: rotate(0deg);
                transform: rotate(0deg);
            }
            100% {
                -webkit-transform: rotate(360deg);
                transform: rotate(360deg);
            }
        }
	</style>
    <script type="text/javascript">
    var action_url = "ext/modules/payment/moneta/actions.php";
    $(document).on('click', '#payment_capture_button', function(){
        if(window.confirm("Are you sure you want to capture transaction ?")){
            $('#payment_capture_button').hide();
            $('#capture-load').show();
            $.ajax({
                type:'POST',
                dataType: 'json',
                data: {'order_id' : <?php echo $order_id;?>,'action':'capture'},
	        url: action_url,
	        success: function(data) {
	        	if(data.code == 0){
		        	location.reload();
		        }else{
		        	alert(data.message);
		        	location.reload();
		        }
	        },
	        error: function(xhr, ajaxOptions, thrownError) {
                console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
	      });
    	}
    });
    $(document).on('click', '#payment_void_button', function(){
        if(window.confirm("Are you sure you want to void transaction ?")){
            $('#payment_void_button').hide();
            $('#void-load').show();
            $.ajax({
                type:'POST',
                dataType: 'json',
                data: {'order_id' : <?php echo $order_id;?>,'action':'void'},
	        url: action_url,
	        success: function(data) {
	        	if(data.code == 0){
		        	location.reload();
		        }else{
		        	alert(data.message);
		        	location.reload();
		        }   
	        },
	        error: function(xhr, ajaxOptions, thrownError) {
                console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
	      });
    	}
    });
    $(document).on('click', '#payment_refund_button', function(){
        if(window.confirm("Are you sure you want to refund transaction ?")){
            $('#payment_refund_button').hide();
            $('#refund-load').show();
            $.ajax({
                type:'POST',
                dataType: 'json',
                data: {'order_id' : <?php echo $order_id;?>,'action':'refund','refund_amount':$('#refund-amount').val()},
	        url: action_url,
	        success: function(data) {
	          if(data.code == 0){
	        	  location.reload();
	          }else{
	        	  alert(data.message);
	        	  location.reload();
	          }    
	        },
	        error: function(xhr, ajaxOptions, thrownError) {
                console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
	      });
    	}
    });
</script>

<?php
    }
}
?>
