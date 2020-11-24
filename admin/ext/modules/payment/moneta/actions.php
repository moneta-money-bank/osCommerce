<?php
/*
    process Capture/Void/Refund actions for the admin panel
 */
chdir('../../../../');
require('includes/application_top.php');
require('../includes/modules/payment/moneta.php');
$payment = new moneta();
$response = array();
if(!isset($_POST['order_id'])){
    $response['code'] = -1;
    $response['message'] = 'miss order id';
}else if(!isset($_POST['action'])){
    $response['code'] = -2;
    $response['message'] = 'miss action code';
}else{
    $query = tep_db_query("SELECT * FROM `moneta_order` WHERE `order_id`='" . $_POST['order_id'] . "'");
    $order = tep_db_fetch_array($query);
    if(empty($order)){
        $response['code'] = -9;
        $response['message'] = 'order does not exist';
        echo json_encode($response);
        exit;
    }
    $moneta_order_id = $order['moneta_order_id'];
    switch($_POST['action']){
        case 'refund':
            if(!isset($_POST['refund_amount']) || $_POST['refund_amount'] <0){
                $response['code'] = -3;
                $response['message'] = 'refund amount is not correct';
            }else{
                $result = $payment->refund($order['merchant_tx_id'],$order['total'],$_POST['refund_amount'],$_POST['order_id'],$moneta_order_id);
                if($result){
                    $response['code'] = 0;
                    $response['message'] = 'success';
                }else{
                    $response['code'] = -4;
                    $response['message'] = 'refund error';
                }
            }
            break;
        case 'capture':
            $result = $payment->capture($order['merchant_tx_id'],$order['total'],$_POST['order_id'],$moneta_order_id);
            if($result){
                $response['code'] = 0;
                $response['message'] = 'success';
            }else{
                $response['code'] = -5;
                $response['message'] = 'capture error';
            }
            break;
        case 'void':
            $result = $payment->void($order['merchant_tx_id'],$order['total'],$_POST['order_id'],$moneta_order_id);
            if($result){
                $response['code'] = 0;
                $response['message'] = 'success';
            }else{
                $response['code'] = -6;
                $response['message'] = 'void error';
            }
            break;
    }
}

echo json_encode($response);
exit;


?>