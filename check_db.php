<?php
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "ORDERS TABLE:\n";
$columns = DB::select('SHOW COLUMNS FROM orders');
foreach($columns as $col) {
    echo $col->Field . " - " . $col->Type . "\n";
}

echo "\nQUEUE TABLE:\n";
$columns = DB::select('SHOW COLUMNS FROM queue');
foreach($columns as $col) {
    echo $col->Field . " - " . $col->Type . "\n";
}
