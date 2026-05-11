<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', ['pending', 'preparing', 'ready', 'completed', 'cancelled'])->change();
        });
        Schema::table('queue', function (Blueprint $table) {
            $table->enum('status', ['waiting', 'processing', 'completed', 'cancelled'])->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', ['pending', 'processing', 'done', 'cancelled'])->change();
        });
        Schema::table('queue', function (Blueprint $table) {
            $table->enum('status', ['waiting', 'processing', 'completed'])->change();
        });
    }
};
