<?php
try {
    DB::statement("ALTER TABLE users ADD COLUMN role ENUM('customer', 'seller') DEFAULT 'customer' AFTER password");
    echo "Column 'role' added successfully.\n";
} catch (\Exception $e) {
    echo "Error or column exists: " . $e->getMessage() . "\n";
}
