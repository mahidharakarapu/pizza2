<?php

// Use $server_orders vs. $undelivered_orders to find newly delivered orders
// Credit their newly delivered orders to inventory and delete such orders from 
// the undelivered_orders table
// $server_orders: array of orders from server
// $undelivered orders: array of orders from undelivered orders table
function record_deliveries($db, $server_orders, $undelivered_orders) {
    $delivered_orders = array();  // build set of delivered orders
    for ($i = 0; $i < count($server_orders); $i++) {
        $orderid = $server_orders[$i][0]['orderID'];
        $delivered = $server_orders[$i][0]['delivered'];
        if ($delivered === true) {
            $delivered_orders[$orderid] = $server_orders[$i][0];  // remember order by id
        }
    }
    error_log('server orders: ' . print_r($server_orders, true));
    error_log('delivered: ' . print_r($delivered_orders, true));   
    
    for ($j = 0; $j < count($undelivered_orders); $j++) {
        $orderID = $undelivered_orders[$j]['orderID'];
        error_log('looking at undel order ' . print_r($undelivered_orders[$j], true));
        if (array_key_exists($orderID, $delivered_orders)) {
            error_log("found newly delivered order $orderID");
            $order = $delivered_orders[$orderID];  // the full order info

            $flour_quantity = $undelivered_orders[$j]['flour_qty'];
            $cheese_quantity = $undelivered_orders[$j]['cheese_qty'];
            restock_cheese_inventory($db, $cheese_quantity);
            restock_flour_inventory($db, $flour_quantity);
            delete_unorderdetails($db, $orderID);
        }
    }
}

function order_inventory_supplies($inventory, $httpClient, $base_url) {
    if ($inventory[0]['quantity'] < 150) {
        $flour_required_quantity = 150 - $inventory[0]['quantity'];
        $flour_order_quantity = 0;
        $flour_unit_bags = 1;
        while ($flour_order_quantity < $flour_required_quantity) {
            $flour_order_quantity = 100 * $flour_unit_bags;
            $flour_unit_bags += 1;
        }
    } else {
        $flour_order_quantity = 0;
    }

    if ($inventory[1]['quantity'] < 150) {
        $cheese_order_quantity = 150 - $inventory[1]['quantity'];
    } else {
        $cheese_order_quantity = 0;
    }

    if (($flour_order_quantity > 0) || ($cheese_order_quantity > 0)) {
        $item1 = array('productID' => 11, 'quantity' => $flour_order_quantity);
        $item2 = array('productID' => 12, 'quantity' => $cheese_order_quantity);
        $order = array('customerID' => 1, 'items' => array($item1,$item2));
        $id = post_order($httpClient, $base_url, $order);
        insert_unorderdetails($id, $flour_order_quantity, $cheese_order_quantity);
    }
}
// Put other helpers here, to keep only top-level code in index.php