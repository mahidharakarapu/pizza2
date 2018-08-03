<?php

function get_inventory_detail($db)
{
    $query='SELECT * from inventory';
    $statement=$db->prepare($query);
    $statement->execute();
    $inventory=$statement->fetchAll();
    $statement->closeCursor();
    return $inventory;
}
function get_undelivered_ord($db)
{
    $query='SELECT * FROM undelivered_orders';
    $statement=$db->prepare($query);
    $statement->execute();
    $undelivered_orders=$statement->fetchAll();
    $statement->closeCursor();
    return $undelivered_orders;
    
}
function start_inventory($db)
{
    $query='select * from start_inventory';
     $statement=$db->prepare($query);
    $statement->execute();
    $inventory=$statement->fetchAll();
    $statement->closeCursor();
    return $inventory;
}
function update_inventory($db,$update)
{
    $query='update inventory SET quantity=quantity - :update';
    $statement->bindValue(':update',$update);
    $statement=$db->prepare($query);
    $statement->execute();
    //$undelivered_orders=$statement->fetchAll();
    $statement->closeCursor();
    //return $undelivered_orders;
}
function reduce_inventory($db)
{
    $query='update inventory SET quantity=quantity-1';
     $statement=$db->prepare($query);
    $statement->execute();
    //$undelivered_orders=$statement->fetchAll();
    $statement->closeCursor();
}
function autosave_inventory($db)
{
    $query='DELETE from start_inventory;'
            .'INSERT into start_inventory'
            . 'select * from inventory';
    $statement=$db->prepare($query);
    $statement->execute();
    $statement->closeCursor();
}
function insert_unorderdetails($orderID,$flour_qty,$cheese_qty) {
    global $db;
    $query='insert into undelivered_orders values(:order_id, :flour, :cheese)';
    $smt=$db->prepare($query);
    $smt->bindValue(':order_id', $orderID);
    $smt->bindValue(':flour', $flour_qty);
    $smt->bindValue(':cheese', $cheese_qty);
    $smt->execute();
    $smt->closeCursor();
}
function delete_unorderdetails($db,$orderID)
{
    $query='delete from undelivered_orders WHERE orderID=:order_id';
    $statement=$db->prepare($query);
    $statement->bindValue(':order_id',$orderID);
    $statement->execute();
    $statement->closeCursor();
}
function restock_flour_inventory($db,$update)
{
    $query='update inventory SET quantity=quantity +:update where
            productName=:productName';
    $statement=$db->prepare($query);
    $statement->bindValue(':update',$update);
    $statement->bindValue(':productName','flour');
    $statement->execute();
    $statement->closeCursor();
    
}
function restock_cheese_inventory($db,$update)
{
    $query='update inventory SET quantity=quantity +:update where
            productName=:productName';
    $statement=$db->prepare($query);        
    $statement->bindValue(':update',$update);
    $statement->bindValue(':productName','cheese');
    $statement->execute();
    $statement->closeCursor();
    
}
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

