<?php

    include $_SERVER["DOCUMENT_ROOT"].'/classes/db.php';
    $database = new database();
    $database->connect();

    $paypalEmail = "sb-43dogc425949@business.example.com";
    $paypalURL = "https://ipnpb.sandbox.paypal.com/cgi-bin/webscr";
    
    $raw_post_data = file_get_contents('php://input');
    $raw_post_array = explode('&', $raw_post_data);
    $myPost = array();
    foreach ($raw_post_array as $keyval) {
        $keyval = explode('=', $keyval);
        if (count($keyval) == 2) {
            $myPost[$keyval[0]] = urldecode($keyval[1]);
        }
    }

    
//здесь мы можем уже сохранить данные в бд и потом проверить правильность 'txn_id'

    $req = 'cmd=_notify-validate';
    if (function_exists('get_magic_quotes_gpc')) {
        $get_magic_quotes_exists = true;
    }
    foreach ($myPost as $key => $value) {
        if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
            $value = urlencode(stripslashes($value));
        }else {
            $value = urlencode($value);
        }
        $req .= "&$key=$value";
    }

    $ch = curl_init($paypalURL);
    if ($ch == FALSE) {
        return FALSE;
    }
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
    curl_setopt($ch, CURLOPT_SSLVERSION, 6);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);

    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: PHP-IPN-Verification-Script',
            'Connection: Close',
    ));
    $res = curl_exec($ch);
        if ( ! ($res)) {
            $errno = curl_errno($ch);
            $errstr = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL error: [$errno] $errstr");
        }
        $info = curl_getinfo($ch);
        $http_code = $info['http_code'];
        if ($http_code != 200) {
            throw new Exception("PayPal responded with http code $http_code");
        }
        curl_close($ch);

    $tokens = explode("\r\n\r\n", trim($res));
    $res = trim(end($tokens));
    if (strcmp($res, "VERIFIED") == 0 || strcasecmp($res, "VERIFIED") == 0){

        // Payment data
        $item_number = $_POST['item_number'];
        $txn_id = $_POST['txn_id'];
        $payment_gross = $_POST['mc_gross'];
        $currency_code = $_POST['mc_currency'];
        $payment_status = $_POST['payment_status'];

        // если мы сохранили данные в бд то тут нужно проверить валидность TXN ID.
        // Check if payment data exists with the same TXN ID.
        // $valid_txnid = check_txnid($txn_id);

        $txn_id = $_POST['txn_id'];
        $order_number = $_POST['item_name'];
        $payment_amount = $_POST['mc_gross'];
        $payment_currency = $_POST['mc_currency'];
        $payment_status = $_POST['payment_status'];
        $receiver_email = $_POST['receiver_email'];
        $payer_email = $_POST['payer_email'];
        $order_id = $_POST['custom'];

        $date = date('Y-m-d H:i:s');
        
        //$full_ipn = json_encode($myPost);

        $query = "SELECT * FROM `transactions` WHERE `txn_id` = '$txn_id'";
        $result = mysql_query($query);
        if (mysql_num_rows($result) == 0){
            $query = "INSERT INTO `transactions` VALUES ('$txn_id','$payment_amount','$payment_currency','$date','$payment_status','$receiver_email','$payer_email','$order_id')";
            mysql_query($query) or die ($query);
        }

        $query = "SELECT * FROM `retail_orders` WHERE `id` = '$order_id'";
        $result = mysql_query($query);
        $order_total = mysql_result($result,0,'total');
        

        if ($payment_amount == $order_total){
            $query = "UPDATE `retail_orders` SET `payment` = 1 WHERE `id` = '$order_id'";
            $result = mysql_query($query) or die ($query);

            if ($result){
                // дальше изменяем группу пользователя в upgraded в бд
                // но конечно нужно проверить сначала если есть такои пользователь и может изменить больше данных
                
                // тут должны присылать письмо пользователю что все в порядке и админу тоже
            }else{
                // сообщаем админу
                // Error inserting into DB
                // E-mail admin or alert user
            }
        }else{
            // сообщаем админу
        }
    }else{
        // сообщаем админу
    }
    
    $txn_id = $_POST['txn_id'];
    $order_number = $_POST['item_name'];
    $payment_amount = $_POST['mc_gross'];
    $payment_currency = $_POST['mc_currency'];
    $payment_status = $_POST['payment_status'];
    $receiver_email = $_POST['receiver_email'];
    $payer_email = $_POST['payer_email'];
    $order_id = $_POST['custom'];

    $date = date('Y-m-d H:i:s');
    
    //$full_ipn = json_encode($myPost);

    $query = "SELECT * FROM `transactions` WHERE `txn_id` = '$txn_id'";
    $result = mysql_query($query);
    if (mysql_num_rows($result) == 0){
        $query = "INSERT INTO `transactions` VALUES ('$txn_id','$payment_amount','$payment_currency','$date','$payment_status','$receiver_email','$payer_email','$order_id')";
        mysql_query($query) or die ($query);
    }
    
?>