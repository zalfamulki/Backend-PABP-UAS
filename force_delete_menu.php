<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$item = App\Models\MenuItem::where('name', 'like', '%baso%')->first();
if ($item) {
    // Menghapus record yang bergantung pada menu item ini
    \App\Models\OrderItem::where('menu_id', $item->id)->delete();
    $item->delete();
    echo "Menu '{$item->name}' dan histori pesanannya telah berhasil dihapus.";
} else {
    echo "Menu tidak ditemukan.";
}
