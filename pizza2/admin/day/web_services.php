<?php
require '../../vendor/autoload.php';
// Functions to do the base web services needed
// Note that all needed web services are sent from this day directory
// The functions here should throw up to their callers, just like
// the functions in model.
//
// Post day number to server
// Returns if successful, or throws if not
function post_day($httpClient, $base_url, $dayNumber) {
    error_log('post_day to server: ' . $dayNumber);
    $url = $base_url . '/day/';
    $httpClient->request('POST', $url, ['json' => $dayNumber]);
}

// TODO: POST order and get back location (i.e., get new id), get all orders 
// in server and/or get a specific order by orderid

function post_order($httpClient, $base_url, $order) {
    error_log('post_order to server');
    $url = 'http://' . $base_url . '/orders/';
    $response = $httpClient->request('POST', $url, ['json' => $order]);
    $location = $response->getHeader('Location');
    $parts = explode('/', $location[0]);
    $id = $parts[count($parts)-1];
    return $id;
}

function get_supply_order($httpClient, $base_url) {
    error_log('get_order to server:');
    $url = $base_url . '/orders/';
	$response = $httpClient->request('GET', $url);
	$prodJson = $response->getBody()->getContents();
	$order = json_decode($prodJson, true);
	return $order;
}