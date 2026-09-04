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
        Schema::create('geofences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedBigInteger('traccar_geofence_id')
                ->nullable()
                ->unique();

            $table->string('name');

            $table->text('description')
                ->nullable();

            $table->text('area');

            $table->boolean('is_active')
                ->default(true);

            $table->timestamp('last_sync_at')
                ->nullable();

            $table->timestamps();

            $table->index('company_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('geofences');
    }
};
