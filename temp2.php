<?php
$columns = DB::select('SHOW COLUMNS FROM queue');
foreach($columns as $col) {
    echo $col->Field . " - " . $col->Type . "\n";
}
