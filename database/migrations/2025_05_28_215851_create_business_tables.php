<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Esta migración será creada automáticamente por spatie/laravel-permission
        // Pero necesitamos crear las tablas específicas del negocio

        // Tabla de clientes
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->text('address');
            $table->string('identification_type')->default('CC'); // CC, CE, NIT
            $table->string('identification_number')->unique();
            $table->timestamps();
        });

        // Tabla de mascotas
        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('species'); // perro, gato, etc.
            $table->string('breed');
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female']);
            $table->decimal('weight', 8, 2)->nullable();
            $table->text('medical_observations')->nullable();
            $table->string('photo')->nullable();
            $table->timestamps();
        });

        // Tabla de categorías de servicios
        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('segment'); // clinic, hotel, spa
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Tabla de servicios
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_category_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tabla de citas/reservas
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('pet_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // empleado asignado
            $table->datetime('appointment_date');
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->decimal('final_price', 10, 2)->nullable();
            $table->timestamps();
        });

        // Tabla de historiales médicos
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pet_id')->constrained()->onDelete('cascade');
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            $table->foreignId('veterinarian_id')->constrained('users')->onDelete('cascade');
            $table->text('diagnosis');
            $table->text('treatment');
            $table->text('medications')->nullable();
            $table->text('observations')->nullable();
            $table->date('next_visit')->nullable();
            $table->timestamps();
        });

        // Tabla de vacunas
        Schema::create('vaccinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pet_id')->constrained()->onDelete('cascade');
            $table->string('vaccine_name');
            $table->date('application_date');
            $table->date('next_dose_date')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
        });

        // Tabla de hospedaje
        Schema::create('hotel_stays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pet_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->enum('room_type', ['standard', 'premium', 'deluxe']);
            $table->text('special_requirements')->nullable();
            $table->decimal('daily_rate', 8, 2);
            $table->decimal('total_cost', 10, 2);
            $table->enum('status', ['reserved', 'active', 'completed', 'cancelled'])->default('reserved');
            $table->timestamps();
        });

        // Tabla de PQRs
        Schema::create('pqrs', function (Blueprint $table) {
            $table->id();
            $table->string('client_name');
            $table->string('client_email');
            $table->string('client_phone')->nullable();
            $table->enum('type', ['peticion', 'queja', 'reclamo', 'sugerencia']);
            $table->string('subject');
            $table->text('description');
            $table->enum('status', ['pending', 'in_process', 'resolved', 'closed'])->default('pending');
            $table->text('response')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pqrs');
        Schema::dropIfExists('hotel_stays');
        Schema::dropIfExists('vaccinations');
        Schema::dropIfExists('medical_records');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('services');
        Schema::dropIfExists('service_categories');
        Schema::dropIfExists('pets');
        Schema::dropIfExists('clients');
    }
};
