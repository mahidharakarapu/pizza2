<?php
require('../../util/main.php');
require('../../model/database.php');
require('../../model/day_db.php');
require('../../model/initial.php');
require('../../model/inventory_db.php');
require('../../model/order_db.php');
require('day_helpers.php');
require ('web_services.php');

// Note that you don't have to put all your code in this file.
// You can use another file day_helpers.php to hold helper functions
// and call them from here.
$spot = strpos($app_path, 'pizza2');    
$part = substr($app_path, 0, $spot);
$httpClient = new GuzzleHttp\Client();
$base_url = $_SERVER['SERVER_NAME'] . $part . 'proj2_server/rest';

$action = filter_input(INPUT_POST, 'action');
if ($action == NULL) {
    $action = filter_input(INPUT_GET, 'action');
    if ($action == NULL) {
        $action = 'list';
    }
}
$current_day = get_current_day($db);
if ($action == 'list') {
    try {
        $current_day= get_current_day($db);
       // $todays_orders= get_orders_for_day($db, $current_day);
        // TODO:
        // Load variables for displayed info on supplies on order and inventory
        $todays_orders = get_orders_for_day($db, $current_day);
        // $error_message=null;
        $undelivered_orders= get_undelivered_ord($db);
        $inventory= get_inventory_detail($db);
        $server_orders = get_supply_order($httpClient, $base_url, 1);
    } catch (Exception $e) {
        include('../../errors/error.php');
        exit();
    }
    include('day_list.php');
} else if ($action == 'next_day') {
    try {
        finish_orders_for_day($db, $current_day);
        increment_day($db,$current_day);
        $current_day++;
    } catch (Exception $e) {
        include('../../errors/error.php');
        exit();
    }
    // TODO: without putting a huge amount of code here: 
    //   see day_helpers.php for some starter code, add other functions there
    // POST the new day number to the server by calling post_day in web_services.php
    post_day($httpClient, $base_url, $current_day);

    // Get the undelivered orders from pizza2's database
    $undelivered_orders= get_undelivered_ord($db);
    $inventory= get_inventory_detail($db);
    // Get the supply order status from the server by calling into web_services.php
    // Determine new deliveries by analyzing undelivered orders and server status info.
    $server_orders = get_supply_order($httpClient, $base_url);
    // Add any newly delivered order amounts to inventory
    // Remove processed orders from undelivered orders table
    record_deliveries($db, $server_orders, $undelivered_orders);
    // Place a new supply order if necessary, via web_services.php
    $inventory= get_inventory_detail($db);
    // Add any new supply order to undelivered orders table
    order_inventory_supplies($inventory, $httpClient, $base_url);
    // Load variables for displayed info on supplies on order and inventory
    $undelivered_orders= get_undelivered_ord($db);
    $inventory= get_inventory_detail($db);
    $server_orders = get_supply_order($httpClient, $base_url);
    // Avoiding redirect here for easier debugging: set up needed variables for day_list
    $todays_orders = array(); // new day: no customer orders yet
    include('day_list.php');
} else if ($action == 'initial_db') {
    try {
        initial_db($db);
        // TODO: 
        // POST day 0 to the server. 
        post_day($httpClient, $base_url, 1);

        // Get the current inventory info
        $inventory= get_inventory_detail($db);
        // Place new supply order as required by same algorithm as above
        // add order to undelivered orders table
        order_inventory_supplies($inventory, $httpClient, $base_url);
        //post_day($error_message, $base_url, 0);
        header("Location: .");
    } catch (Exception $e) {
        $error_message=$e->getMessage();
        include ('../../errors/error.php');
        exit();
    } 
}
?>