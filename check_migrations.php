<?php
try {
    $migrations = DB::table('migrations')->get();
    foreach($migrations as $m) {
        echo $m->migration . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
