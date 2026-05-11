<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$item = App\Models\MenuItem::where('name', 'like', '%baso%')->first();
if ($item) {
    $item->update(['is_available' => false]);
    echo "Item '{$item->name}' marked as unavailable.";
} else {
    echo "Item not found.";
}
