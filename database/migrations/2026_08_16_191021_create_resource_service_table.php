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
        Schema::create('resource_service', function (Blueprint $table) {
            $table->id();
        
            $table->foreignId('resource_id')
                ->constrained()
                ->cascadeOnDelete();
        
            $table->foreignId('service_id')
                ->constrained()
                ->cascadeOnDelete();
        
            $table->boolean('is_active')->default(true);
        
            $table->json('metadata')->nullable();
        
            $table->timestamps();
        
            $table->unique(['resource_id', 'service_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_service');
    }
};
