<?php

use App\Enums\DeviceStatus;
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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('vehicle_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->unsignedBigInteger('traccar_device_id')
                ->nullable()
                ->unique();

            $table->string('name');

            $table->string('unique_id')
                ->unique();

            $table->string('model')
                ->nullable();

            $table->string('phone')
                ->nullable();

            $table->enum('status', DeviceStatus::values())
                ->default(DeviceStatus::ACTIVE->value);

            $table->timestamp('last_sync_at')
                ->nullable();

            $table->timestamps();

            $table->index('company_id');
            $table->index('vehicle_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};