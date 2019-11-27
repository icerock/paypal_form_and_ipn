<?php
// STEP 1: read POST data
// Reading POSTed data directly from $_POST causes serialization issues with array data in the POST.
// Instead, read raw POST data from the input stream. 
$raw_post_data = file_get_contents('php://input');
$raw_post_array = explode('&', $raw_post_data);
$myPost = array();
foreach ($raw_post_array as $keyval) {
  $keyval = explode ('=', $keyval);
  if (count($keyval) == 2)
     $myPost[$keyval[0]] = urldecode($keyval[1]);
}
// read the IPN message sent from PayPal and prepend 'cmd=_notify-validate'
$req = 'cmd=_notify-validate';
if(function_exists('get_magic_quotes_gpc')) {
   $get_magic_quotes_exists = true;
} 
foreach ($myPost as $key => $value) {        
   if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) { 
        $value = urlencode(stripslashes($value)); 
   } else {
        $value = urlencode($value);
   }
   $req .= "&$key=$value";
}
 
// STEP 2: POST IPN data back to PayPal to validate
$ch = curl_init('https://www.paypal.com/cgi-bin/webscr');
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
// In wamp-like environments that do not come bundled with root authority certificates,
// please download 'cacert.pem' from "http://curl.haxx.se/docs/caextract.html" and set 
// the directory path of the certificate as shown below:
// curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem');
if( !($res = curl_exec($ch)) ) {
    // error_log("Got " . curl_error($ch) . " when processing IPN data");
    curl_close($ch);
    exit;
}
curl_close($ch);
 
// STEP 3: Inspect IPN validation result and act accordingly
if (strcmp ($res, "VERIFIED") == 0) {
    // The IPN is verified, process it:
    // check whether the payment_status is Completed
    // check that txn_id has not been previously processed
    // check that receiver_email is your Primary PayPal email
    // check that payment_amount/payment_currency are correct
    // process the notification
    // assign posted variables to local variables
    $txn_id = $_POST['txn_id'];
    $order_number = $_POST['item_name'];
    $payment_amount = $_POST['mc_gross'];
    $payment_currency = $_POST['mc_currency'];
    $payment_status = $_POST['payment_status'];
    $receiver_email = $_POST['receiver_email'];
    $payer_email = $_POST['payer_email'];
    $order_id = $_POST['custom'];

    $full_name = $_POST['address_name'];
    $names = explode (" ",$full_name);
    
    $first_name = $names[0];
    $last_name = $names[1];
    $address = $_POST['address_street'];
    $city = $_POST['address_city'];
    $state = $_POST['address_state'];
    $query = "SELECT `id` FROM `states` WHERE `abbr` = '$state'";
    $result = mysql_query($query);
    $state = mysql_result($result, 0, 'id');

    $zip = $_POST['address_zip'];

    $date = date('Y-m-d H:i:s');

    include $_SERVER["DOCUMENT_ROOT"].'/classes/db.php';
    $database = new database();
    $database->connect();

    $query = "SELECT * FROM `transactions` WHERE `txn_id` = '$txn_id'";
    $result = mysql_query($query);
    if (mysql_num_rows($result) == 0){
        $query = "INSERT INTO `transactions` VALUES ('','$txn_id','$payment_amount','$payment_currency','$date','$payment_status','$receiver_email','$payer_email','$order_id',1)";
        mysql_query($query);

        $query = "UPDATE `retail_orders` SET `first_name` = '$first_name',`last_name` = '$last_name',`address_1` = '$address',`city` = '$city',`state` = '$state',`zip` = '$zip'  WHERE `id` = '$order_id'";
        $result = mysql_query($query);
    

    $query = "SELECT * FROM `retail_orders` WHERE `id` = '$order_id'";
    $result = mysql_query($query);
    $order_total = mysql_result($result,0,'total');
    

    if ($payment_amount == $order_total){
        $query = "UPDATE `retail_orders` SET `payment` = 1 WHERE `id` = '$order_id'";
        $result = mysql_query($query);

    // IPN message values depend upon the type of notification sent.
    // To loop through the &_POST array and print the NV pairs to the screen:

    foreach($_POST as $key => $value) {
      echo $key." = ". $value."<br>";
    }
  }
}
} else if (strcmp ($res, "INVALID") == 0) {
    // IPN invalid, log for manual investigation
    echo "The response from IPN was: <b>" .$res ."</b>";
}
?>