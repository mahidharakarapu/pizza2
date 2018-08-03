<?php include '../../view/header.php'; ?>
<main>
    <section>
        <h1>Today is day <?php echo $current_day; ?></h1>
        <form action="index.php" method="post">
            <input type="hidden" name="action" value="next_day">
            <input type="submit" value="Advance to day <?php echo $current_day + 1; ?>" />
        </form>

        <form  action="index.php" method="post">
            <input type="hidden" name="action" value="initial_db">           
            <input type="submit" value="Initialize DB (making day = 1)" />
            <br>
        </form>
        <br>
        <h2>Today's Orders</h2>
        <?php if (count($todays_orders) > 0): ?>
            <table>
                <tr>
                    <th>Order ID</th>
                    <th>Room No</th>
                    <th>Status</th>
                </tr>

                <?php foreach ($todays_orders as $todays_order) : ?>
                    <tr>
                        <td><?php echo $todays_order['id']; ?> </td>
                        <td><?php echo $todays_order['room_number']; ?> </td>  
                        <td><?php echo $todays_order['status']; ?> </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No Orders Today </p>
        <?php endif; ?>

     

            <h2>On Order: Undelivered Supply Orders</h2>
            <table>
                <tr>
                    <th>Order ID</th>
                    <th>Flour Quantity</th>
                    <th>Cheese Quantity   
                    </th>
                </tr>
                <?php for($i=0; $i <count($undelivered_orders);
                $i++):?>
                <tr>
                    <td>
                        <?php echo $undelivered_orders[$i]['orderID'];?>
                    </td>
                    <td>
                        <?php echo $undelivered_orders[$i]['flour_qty'];?>
                    </td>
                    <td>
                        <?php echo $undelivered_orders[$i]['cheese_qty'];?>
                    </td>
                </tr>
                <?php endfor; ?>
            </table>
            <h2>
                Current Inventory
            </h2>
            <table>
                <tr>
                    <th>
                        Product ID
                    </th>
                    <th>Product Name</th>
                    <th>Quantity</th>
                      
                </tr>
                <?php foreach ($inventory as $invent):?>
                <tr>
                    <td>
                        <?php echo $invent['productID'];?>
                    </td>
                    <td>
                         <?php echo $invent['productName'];?>
                    </td>
                    <td>
                         <?php echo $invent['quantity'];?>
                    </td>
                </tr>
                <?php endforeach;?>
            </table>
            
            <h2>TEST</h2>
            <table>
                <tr>
                    <th>Order ID</th>
                    <th>Flour Quantity</th>
                    <th>Cheese Quantity</th>
                    <th>Delivered</th>
                </tr>
                <?php 
                    foreach ($server_orders as $server_order):
                ?>
                <tr>
                    <td><?php echo $server_order[0]['orderID'];?></td>
                    <?php $items = $server_order[1]; ?>
                    <td><?php echo $items[0]['quantity'];?></td>
                    <td><?php echo $items[1]['quantity'];?></td>
                    <td><?php 
                    if ($server_order[0]['delivered'] == false) {
                        echo 'false';
                    } else {
                        echo 'true';
                    };
                    ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
    </section>

</main>
<?php include '../../view/footer.php'; ?>