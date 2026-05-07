<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('organization_name');
            $table->string('contacts')->nullable();
            $table->timestamps();
        });

        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->unsignedInteger('capacity');
            $table->decimal('hourly_fee', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('workshops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedInteger('max_participants');
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->boolean('paid')->default(false);
            $table->timestamps();

            $table->unique(
                ['workshop_id', 'participant_user_id'],
                'unique_active_registration'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
        Schema::dropIfExists('workshops');
        Schema::dropIfExists('venues');
        Schema::dropIfExists('organizers');
    }
};