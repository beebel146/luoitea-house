<?php
$c = new \mysqli('localhost', 'root', '', 'luoitea_house_2');
$r = $c->query('DESCRIBE orders');
while($row = $r->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
