<?php
$columns = DB::select('SHOW COLUMNS FROM users');
foreach($columns as $col) {
    echo $col->Field . " - " . $col->Type . "\n";
}
