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
        Schema::table('menu_items', function (Blueprint $table) {
            if (!Schema::hasColumn('menu_items', 'category')) {
                $table->string('category')->nullable();
            }
            if (!Schema::hasColumn('menu_items', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('menu_items', 'image_url')) {
                $table->string('image_url')->nullable();
            }
            if (!Schema::hasColumn('menu_items', 'is_available')) {
                $table->boolean('is_available')->default(true);
            }
            if (!Schema::hasColumn('menu_items', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn(['category', 'description', 'image_url', 'is_available', 'updated_at']);
        });
    }
};
