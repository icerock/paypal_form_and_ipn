
<div class="container">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>

            <li class="breadcrumb-item active" aria-current="page">Payment</li>
        </ol>
    </nav>
</div>
<?php

if(!empty($_SESSION['cart'])){

        $promo = addslashes(htmlspecialchars($_POST['promo']));
            
        $promo = mb_strtoupper($promo, 'UTF-8');

        if(!empty($promo)){
            $query = "SELECT * FROM `promo` WHERE `code` = '$promo'";
            $result = mysql_query($query);
            $num = mysql_numrows($result);

            if($num == 1){
                $discount = mysql_result($result,0,'discount');
                $free_delivery = mysql_result($result,0,'free_delivery');
            }
        }
    

		$client_id = $_SESSION['client_id'];

		$date = date('Y-m-d');
        $time = date('H:i:s');
        
		$query = "INSERT INTO `retail_orders` VALUES ('','','$date','$time','$client_id','','','','','','','','','','','','','','',1,'','','','','','','','','','$promo','$discount','$free_delivery','','','')";
		mysql_query($query) or die ($query);

		$order_id = mysql_insert_id();
        
		//getting order number
		
		$order_number = $order_id+1130;
		$order_number = $index->numberFormat($order_number,5);

		//saving order number

		$query = "UPDATE `retail_orders` SET `order_number` = '$order_number' WHERE `id` = '$order_id'";
		mysql_query($query) or die ($query);

        $cart = $_SESSION['cart'];
        $total = 0;

        foreach($cart as $cart_id => $values){
            foreach($values as $size => $cart_q)    
            {
                $query = "SELECT * FROM `items` WHERE id = '$cart_id'";
                $result = mysql_query($query);

                $name = mysql_result($result, 0, 'name');
                $price = mysql_result($result, 0, 'price');
                $new_price = mysql_result($result, 0, 'new_price');

                if ($new_price > 0) {
                    $sub_subtotal = $new_price * $cart_q;
                    $price = $new_price;
                } else {
                    $sub_subtotal = $price * $cart_q;
                }

                $subtotal = $subtotal + $sub_subtotal;

                //pushing each item into order       
                $query = "INSERT INTO `retail_order_items` VALUES ('','$order_id','$cart_id','$size','$price','$cart_q')";
                $result = mysql_query($query);
            }
        }
    
        if($free_delivery == 0){
            $free_delivery_sum = $index->returnValue(23);

            if ($subtotal >= $free_delivery_sum) {
                $delivery = 0;
            } else {
                $delivery = $index->returnValue(24);
                
            }    
        }
        else {
            $delivery = 0;    
        }

        $total = $subtotal + $delivery;
    
        if($discount > 0){
            $total = $total - ($total * $discount)/100;
            $total = number_format($total, 2);
        }          
        

        $query = "UPDATE `retail_orders` SET `subtotal` = '$subtotal',`delivery` = '$delivery',`total` = '$total' WHERE `id` = '$order_id'";
        mysql_query($query) or die($query);

        
                    
        // PAYPAL

        $paypalEmail = "siberiaspirit@gmail.com";
        $paypalURL = "https://www.paypal.com/cgi-bin/webscr";

        $itemName = "Order #".$order_number;
        $returnUrl = "https://siberiaspirit.com/payment-success?key=spirit";
        $cancelUrl = "https://siberiaspirit.com/cart?key=spirit";
        $notifyUrl = "https://siberiaspirit.com/ipn/cl.php";


    ?>


<div class="container">
    <h1 class="public-inner-title product-title">Payment</h1>
    
    <p class="thankyou">Thank you for your order! Now you will be redirected to the payment page. If you don't want to wait press the button.</p>

    <form action="<?php echo $paypalURL; ?>" method="post" target="_top" class="df-form" id="topaypal">
        <!-- Identify your business so that you can collect the payments. -->
        <input type="hidden" name="business" value="<?=$paypalEmail?>">

        <!-- Specify a Buy Now button. -->
        <input type="hidden" name="cmd" value="_xclick">

        <!-- Specify details about the item that buyers will purchase. -->
        <input type="hidden" name="item_name" value="<?=$itemName?>">
        <input type="hidden" name="amount" value="<?=$total?>">
        <input type="hidden" name="currency_code" value="USD">
        <input type="hidden" name="notify_url" value="<?=$notifyUrl?>">
        <input type="hidden" name="custom" value="<?=$order_id?>">
        <input type="hidden" name="no_shipping" value="2">

        <!-- Display the payment button. -->
        <!-- <input type="image" name="submit" border="0"
                    src="https://www.paypalobjects.com/en_US/i/btn/btn_buynow_LG.gif"
                    alt="Buy Now"> -->

        <div class="row">
            <div class="col-sm-5">
                <button type="submit" class="df-btn accent-btn submit">Continue to payment</button>
            </div>
        </div>

        <img alt="" border="0" width="1" height="1" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif">
    </form>

</div>

<?php

    //clearing cart

    unset ($_SESSION['cart']);
    unset ($_SESSION['s_quantity']);
    unset ($_SESSION['s_pairs']);
}
else {
    ?>


<div class="container">
    <h1 class="public-inner-title product-title">Order payment</h1>

    <div class="cart-block">
        <p class="empty">Your cart is empty :(</p>
    </div>
</div>
<?php
}
?>
