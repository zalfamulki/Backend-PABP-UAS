<?php
$tables = ['orders', 'order_items', 'menu_items'];
foreach($tables as $table) {
    echo "\nTABLE: $table\n";
    $columns = DB::select("SHOW COLUMNS FROM $table");
    foreach($columns as $col) {
        echo $col->Field . " - " . $col->Type . "\n";
    }
}
