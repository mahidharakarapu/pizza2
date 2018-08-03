<?php
function get_day(){
    global $db;
    $query = 'SELECT * FROM systemday';
    $statement = $db->prepare($query);
    $statement->execute();
    $day = $statement->fetch();
    $statement->closeCursor();   
    return $day['dayNumber'];
}

function update_systemday($day) {
    global $db;
    $query = 'UPDATE systemday SET dayNumber = :dayNumber';
    $statement = $db->prepare($query);
    $statement->bindValue(':dayNumber', $day);
    $statement->execute();
    $statement->closeCursor();
}
