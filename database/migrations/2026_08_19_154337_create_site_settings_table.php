<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();

            // Identité
            $table->string('business_name')->default('Votre établissement');

            // Hero
            $table->string('hero_eyebrow')->default('Bienvenue');
            $table->string('hero_title')->default('Prenez rendez-vous');
            $table->string('hero_highlight')->default('simplement.');
            $table->text('hero_description')->nullable();

            // Boutons
            $table->string('booking_button_label')->default('Prendre rendez-vous');

            // Prestations
            $table->string('services_title')->default('Choisissez votre prestation');
            $table->text('services_description')->nullable();

            // Coordonnées
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};