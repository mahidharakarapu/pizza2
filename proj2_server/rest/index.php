<?php
$request_uri = $_SERVER['REQUEST_URI'];
$doc_root = filter_input(INPUT_SERVER, 'DOCUMENT_ROOT');
$dirs = explode(DIRECTORY_SEPARATOR, __DIR__);
array_pop($dirs); // remove last element
$project_root = implode('/', $dirs) . '/';
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', '0'); // displayed errors would mess up response
ini_set('log_errors', 1);
// the following file needs to exist, be accessible to apache
// and writable (chmod 777 php-server-errors.log)
ini_set('error_log', $project_root . 'php-server-errors.log');
set_include_path($project_root);
// app_path is the part of $project_root past $doc_root
$app_path = substr($project_root, strlen($doc_root));
// project uri is the part of $request_uri past $app_path, not counting its last /
$project_uri = substr($request_uri, strlen($app_path) - 1);
$parts = explode('/', $project_uri);
// like  /rest/product/1 ;
//     0    1     2    3    

// tell database.php not to send HTML error page
$in_webservice_code = true;
require_once('../model/database.php');
require_once('../model/product_db.php');
require_once('../model/day_db.php');
require_once('../model/order_db.php');

$server = $_SERVER['HTTP_HOST'];
$method = $_SERVER['REQUEST_METHOD'];
$proto = isset($_SERVER['HTTPS']) ? 'https:' : 'http:';
$url = $proto . '//' . $server . $request_uri;
$resource = trim($parts[2]);
if (isset($parts[3])) {
   $id = $parts[3];
}
error_log('starting REST server request, method=' . $method . ', uri = ...'. $project_uri);

switch ($resource) {
    // Access the specified product
    case 'products':
        error_log('request at case product');
        switch ($method) {
            case 'GET':
                handle_get_product($id);
                break;
            case 'POST':
                handle_post_product($url);
                break;
            default:
                $error_message = 'bad HTTP method : ' . $method;
                include_once('errors/server_error.php');
                server_error(405, $error_message);
                break;
        }
        break;
    case 'day':
        error_log('request at case day');
        switch ($method) {
            case 'GET':
                $new_day = handle_get_day($day);    
                echo $new_day;
                break;
            case 'POST':
                handle_post_day();
                break;
            default:
                $error_message = 'bad HTTP method : ' . $method;
                include_once('errors/server_error.php');
                server_error(405, $error_message);
                break;
        }
        break;
    case 'orders':
        error_log('request at case orders');
        switch ($method) {
            case 'GET':
                handle_get_order($id);
                break;
            case 'POST':
                handle_post_order($url);
                break;
            default:
                $error_message = 'bad HTTP method : ' . $method;
                include_once('errors/server_error.php');
                server_error(405, $error_message);
                break;
        }
        break;  
    default:
        $error_message = 'Unknown REST resource: ' . $resource;
        include_once('errors/server_error.php');
        server_error(400, $error_message);  // blame client (but might be server's fault)
        break;
}

function handle_get_product($product_id) {
    try {
        if (!(is_numeric($product_id) && $product_id > 0)) {
           $error_message = 'Bad product_id in handle_get_product: ' . $product_id;
           include_once('errors/server_error.php');
           server_error(400, $error_message);  // bad client URL
           return; 
        }
        $product = get_product($product_id); 
        if (empty($product)) {  // no data found
            $error_message = 'failed to find product';
            include_once('errors/server_error.php');
            server_error(404, $error_message);
            return; 
        }
        $data = json_encode($product);
        error_log('in handle_get_product, $product = ' . print_r($product, true));
        if ($data === FALSE) {  // failure of json_encode
            $error_message = 'JSON encode error' . json_last_error_msg();
            include_once('errors/server_error.php');
            server_error(500, $error_message);  // server problem
            return; 
        }        
    } catch (Exception $e) {
        $error_message = 'exception trying to get product' . $e->getMessage();
        include_once('errors/server_error.php');
        server_error(500, $error_message);  // server problem
        return; 
    }
    echo $data;
}

function handle_post_product($url) {
    $bodyJson = file_get_contents('php://input');
    error_log('Server saw post data' . $bodyJson);
    $body = json_decode($bodyJson, true);
    if ($body === NULL) {  // failure of json_decode 
        $error_message = 'JSON decode error' . json_last_error_msg();
        include_once('errors/server_error.php');
        server_error(400, $error_message);  // client problem: sent bad JSON
        return;
    }
    try {
        $product_id = add_product($body['categoryID'], $body['productCode'], $body['productName'], $body['description'], $body['listPrice'], $body['discountPercent']);
        // return new URI in Location header
        $locHeader = 'Location: ' . $url . $product_id;
        header($locHeader, true, 201);  // needs 3 args to set code 201 (Created)
        error_log('hi from handle_post_product, header = ' . $locHeader);
    } catch (Exception $e) {
        $error_message = 'Insert failed: ' . $e->getMessage();
        include_once('errors/server_error.php');
        server_error(500, $error_message);  // probably server error
    }
}

function handle_get_day($day) {
    $day = get_day();
    error_log('rest server in handle_get_day, day = ' . $day);
    // echo 'Hell1';
    echo $day;
}

function handle_post_day() {
    error_log('rest server in handle_post_day');
    $day = file_get_contents('php://input');  // just a digit string
    if (!(is_numeric($day) && $day >= 0)) {
        $error_message = 'Bad day number in handle_post_day: ' . $day;
        include_once('errors/server_error.php');
        server_error(400, $error_message);  // bad client data
        return;
    }
    error_log('Server saw POSTed day = ' . $day);

    if ($day == 1) {
        update_systemday($day);
        reinitialize_orders();
    } else {
        update_systemday($day);
    }
    return $day;
}

function handle_get_order($id) {
    try {
        if (!(is_numeric($id) && $id > 0)) {
           try {
                $orders = get_orders();
                $current_day = get_day();
                if (empty($orders)) {  // no data found
                    $error_message = 'failed to find product';
                    include_once('errors/server_error.php');
                    server_error(404, $error_message);
                    return; 
                }
                $aOrders = array();
                for ($i = 0; $i < count($orders); $i++) {
                     $orderI = array('customerID' => $orders[$i]['customerID'], 
                                    'orderID' => $orders[$i]['orderID'], 
                                    'delivered' => ($orders[$i]['deliveryDay'] <= $current_day) ? true : false);

                    $orderItems = get_order_items($orders[$i]['orderID']);

                    $items = array();
                    foreach ($orderItems as $orderItem) {
                        $item = array('productID' => $orderItem['productID'],
                                        'quantity' => $orderItem['quantity']);
                        array_push($items, $item);
                    }

                    $order = array($orderI, $items);
                    array_push($aOrders, $order);
                }
               

                $data = json_encode($aOrders);
                error_log('in handle_get_order, $order = ' . print_r($order, true));
                if ($data === FALSE) {  // failure of json_encode
                    $error_message = 'JSON encode error' . json_last_error_msg();
                    include_once('errors/server_error.php');
                    server_error(500, $error_message);  // server problem
                    return; 
                }        
            } catch (Exception $e) {
                $error_message = 'exception trying to get order' . $e->getMessage();
                include_once('errors/server_error.php');
                server_error(500, $error_message);  // server problem
                return; 
            }
        } else {
            $order = get_order($id); 
            if (empty($order)) {  // no data found
                $error_message = 'failed to find product';
                include_once('errors/server_error.php');
                server_error(404, $error_message);
                return; 
            }
            $orderI = array('customerID' => $order['customerID'], 'orderID' => $order['orderID'], 
                'delivered' => ($order['deliveryDay'] <= $current_day) ? true : false);

            $orderItems = get_order_items($id);
            $items = array();
            foreach ($orderItems as $orderItem) {
                $item = array('productID' => $orderItem['productID'],
                                'quantity' => $orderItem['quantity']);
                array_push($items, $item);
            }

            $order = array($orderI, $items);

            $data = json_encode($order);
            error_log('in handle_get_order, $order = ' . print_r($order, true));
            if ($data === FALSE) {  // failure of json_encode
                $error_message = 'JSON encode error' . json_last_error_msg();
                include_once('errors/server_error.php');
                server_error(500, $error_message);  // server problem
                return; 
            }
        }        
    } catch (Exception $e) {
        $error_message = 'exception trying to get order' . $e->getMessage();
        include_once('errors/server_error.php');
        server_error(500, $error_message);  // server problem
        return; 
    }
    echo $data;
}

function handle_post_order($url) {
    $bodyJson = file_get_contents('php://input');
    error_log('Server saw post data' . $bodyJson);
    $body = json_decode($bodyJson, true);
    if ($body === NULL) {  // failure of json_decode 
        $error_message = 'JSON decode error' . json_last_error_msg();
        include_once('errors/server_error.php');
        server_error(400, $error_message);  // client problem: sent bad JSON
        return;
    }
    try {
        $customerID = $body['customerID'];
        $orderDate = date("Y-m-d H:i:s");
        $day = get_day();
        if ($day % 2 == 1) {
            $deliveryDay = $day + 1;
        } else {
            $deliveryDay = $day + 2;
        }
        $orderID = add_order($customerID, $orderDate, $deliveryDay);
        foreach ($body[items] as $orderItems) {
            $product = get_product($orderItems['productID']);
            add_order_item($orderID, $orderItems['productID'], $product['listPrice'], $product['discountPercent'], $orderItems['quantity']);
        }
        // return new URI in Location header
        $locHeader = 'Location: ' . $url . $orderID;
        header($locHeader, true, 201);  // needs 3 args to set code 201 (Created)
        error_log('hi from handle_post_order, header = ' . $locHeader);
    } catch (Exception $e) {
        $error_message = 'Insert failed: ' . $e->getMessage();
        include_once('errors/server_error.php');
        server_error(500, $error_message);  // probably server error
    }
}