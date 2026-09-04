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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
        
            // Compte client éventuel
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
        
            // Prestation réservée
            $table->foreignId('service_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
        
            // Professionnel / table / cabine / poste / salle...
            $table->foreignId('resource_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
        
            // Date et heure de la réservation
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
        
            // Ex : 1 client, 4 personnes au restaurant, etc.
            $table->unsignedInteger('quantity')->default(1);
        
            // Coordonnées conservées avec la réservation
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();
        
            // Prix au moment de la réservation
            $table->decimal('total_price', 10, 2)->nullable();
        
            // pending / confirmed / cancelled / declined...
            $table->string('status')->default('pending');
        
            $table->text('notes')->nullable();
        
            // Données supplémentaires facultatives
            $table->json('metadata')->nullable();
        
            $table->timestamps();
        
            $table->index(['starts_at', 'ends_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
