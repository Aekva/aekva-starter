<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('hero_image_path')->nullable();

            $table->integer('hero_image_zoom')->default(100);
            $table->integer('hero_image_position_x')->default(50);
            $table->integer('hero_image_position_y')->default(50);
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'hero_image_path',
                'hero_image_zoom',
                'hero_image_position_x',
                'hero_image_position_y',
            ]);
        });
    }
};