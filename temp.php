<?php
$tables = DB::select('SHOW TABLES');
foreach($tables as $table) {
    foreach($table as $key => $value) {
        echo $value . "\n";
    }
}
