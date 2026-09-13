<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('vehicle_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('device_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('geofence_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('type');
            $table->string('severity')->default('info');

            $table->string('title');
            $table->text('message')->nullable();

            $table->unsignedBigInteger('traccar_event_id')->nullable();

            $table->timestamp('occurred_at');
            $table->timestamp('acknowledged_at')->nullable();

            $table->foreignId('acknowledged_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['company_id', 'occurred_at']);
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'acknowledged_at']);

            $table->unique(
                ['company_id', 'traccar_event_id'],
                'alerts_company_traccar_event_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
