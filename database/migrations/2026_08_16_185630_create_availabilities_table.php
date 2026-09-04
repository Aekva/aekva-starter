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
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
        
            $table->foreignId('resource_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();
        
            $table->foreignId('service_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();
        
            // 0 = dimanche, 1 = lundi ... 6 = samedi
            $table->unsignedTinyInteger('day_of_week')->nullable();
        
            // Pour une exception sur une date précise
            $table->date('specific_date')->nullable();
        
            $table->time('start_time');
            $table->time('end_time');
        
            $table->boolean('is_available')->default(true);
        
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
        
            $table->unsignedInteger('capacity')->nullable();
        
            $table->json('metadata')->nullable();
        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availabilities');
    }
};
